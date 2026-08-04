<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Infrastructure\Persistence\Redis\RedisCache;
use App\Infrastructure\Security\JwtService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RedisCache $cache,
        private readonly JwtService $jwtService,
        private array $settings,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (empty($this->settings['enabled'])) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        $isLogin = \str_contains($path, '/login');

        $identifiers = $this->getIdentifiers($request, $isLogin);
        $maxRequests = (int)($this->settings['max_requests'] ?? 5);
        $baseWindow = (int)($this->settings['window'] ?? 60);

        if ($isLogin) {
            // For login, we check if limit is exceeded BEFORE running the handler
            foreach ($identifiers as $identifier) {
                $key = 'rate_limit:' . $identifier;
                $current = (int)$this->cache->getRaw($key);

                if ($current >= $maxRequests) {
                    $ttl = $this->cache->ttl($key);
                    $resetTime = \time() + ($ttl > 0 ? $ttl : $baseWindow);
                    return $this->buildRateLimitResponse($maxRequests, 0, $resetTime);
                }
            }

            // Execute handler
            $response = $handler->handle($request);
            $statusCode = $response->getStatusCode();

            // Reset on successful login
            if ($statusCode >= 200 && $statusCode < 300) {
                foreach ($identifiers as $identifier) {
                    $this->cache->delete('rate_limit:' . $identifier);
                    $this->cache->delete('rate_limit_blocks:' . $identifier);
                }
                return $response
                    ->withHeader('X-RateLimit-Limit', (string)$maxRequests)
                    ->withHeader('X-RateLimit-Remaining', (string)$maxRequests)
                    ->withHeader('X-RateLimit-Reset', (string)(\time() + $baseWindow));
            }

            // Increment on failure
            $primaryCurrent = 0;
            $primaryTtl = $baseWindow;
            foreach ($identifiers as $index => $identifier) {
                $key = 'rate_limit:' . $identifier;
                $current = $this->cache->incr($key);
                
                $blockCountKey = 'rate_limit_blocks:' . $identifier;
                $blockCount = (int)$this->cache->getRaw($blockCountKey);

                if ($current === 1) {
                    $penaltyWindow = (int)($baseWindow * (2 ** $blockCount));
                    $this->cache->expire($key, $penaltyWindow);
                }

                if ($current === $maxRequests) {
                    $this->cache->incr($blockCountKey);
                    $this->cache->expire($blockCountKey, 86400); // Retain blocks for 24h
                }

                if ($index === 0) {
                    $primaryCurrent = $current;
                    $primaryTtl = $this->cache->ttl($key);
                }
            }

            $remaining = \max(0, $maxRequests - $primaryCurrent);
            $resetTime = \time() + ($primaryTtl > 0 ? $primaryTtl : $baseWindow);

            return $response
                ->withHeader('X-RateLimit-Limit', (string)$maxRequests)
                ->withHeader('X-RateLimit-Remaining', (string)$remaining)
                ->withHeader('X-RateLimit-Reset', (string)$resetTime);
        }

        // --- NON-LOGIN ROUTES (Standard Atomic Limit) ---
        $identifier = $identifiers[0];
        $key = 'rate_limit:' . $identifier;

        $current = $this->cache->incr($key);
        
        $blockCountKey = 'rate_limit_blocks:' . $identifier;
        $blockCount = (int)$this->cache->getRaw($blockCountKey);

        if ($current === 1) {
            $penaltyWindow = (int)($baseWindow * (2 ** $blockCount));
            $this->cache->expire($key, $penaltyWindow);
        }

        if ($current === $maxRequests) {
            $this->cache->incr($blockCountKey);
            $this->cache->expire($blockCountKey, 86400);
        }

        $ttl = $this->cache->ttl($key);
        $resetTime = \time() + ($ttl > 0 ? $ttl : $baseWindow);
        $remaining = $maxRequests - $current;

        if ($current > $maxRequests) {
            return $this->buildRateLimitResponse($maxRequests, 0, $resetTime);
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string)$maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string)\max(0, $remaining))
            ->withHeader('X-RateLimit-Reset', (string)$resetTime);
    }

    private function getIdentifiers(ServerRequestInterface $request, bool $isLogin): array
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? 'unknown';

        if ($this->isTrustedProxy($remoteAddr)) {
            if (!empty($serverParams['HTTP_CF_CONNECTING_IP'])) {
                $ip = $serverParams['HTTP_CF_CONNECTING_IP'];
            } elseif (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
                $ips = \explode(',', (string) $serverParams['HTTP_X_FORWARDED_FOR']);
                $ip = \trim($ips[0]);
            } else {
                $ip = $remoteAddr;
            }
        } else {
            $ip = $remoteAddr;
        }

        $identifiers = [];

        if ($isLogin) {
            $identifiers[] = 'login:ip:' . $ip;
            $body = $request->getParsedBody();
            if (\is_array($body) && !empty($body['email'])) {
                $identifiers[] = 'login:email:' . $body['email'];
            }
            return $identifiers;
        }

        $token = $this->extractToken($request);
        if ($token) {
            try {
                $decodedToken = $this->jwtService->validateToken($token);
                if ($decodedToken && isset($decodedToken->sub)) {
                    $identifiers[] = 'user:' . $decodedToken->sub;
                    return $identifiers;
                }
            } catch (Exception) {
                // Token invalid/expired
            }
        }

        $identifiers[] = 'ip:' . $ip;
        return $identifiers;
    }

    private function isTrustedProxy(string $ip): bool
    {
        $trustedProxies = $this->settings['trusted_proxies'] ?? [];
        if (empty($trustedProxies)) {
            return false;
        }

        foreach ($trustedProxies as $trustedProxy) {
            if (\str_contains((string) $trustedProxy, '/')) {
                if ($this->ipInCidr($ip, $trustedProxy)) {
                    return true;
                }
            } elseif ($ip === $trustedProxy) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = \explode('/', $cidr);
        $mask = (int)$mask;
        if ($mask === 0) {
            return false;
        }
        $ipLong = \ip2long($ip);
        $subnetLong = \ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        $netmask = ~((1 << (32 - $mask)) - 1);
        return ($ipLong & $netmask) === ($subnetLong & $netmask);
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (\preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function buildRateLimitResponse(int $limit, int $remaining, int $reset): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(\json_encode([
            'error' => 'Excesso de Requisições',
            'message' => 'Limite de requisições excedido. Por favor, tente novamente mais tarde.',
            'retry_after' => $reset - \time(),
        ]));

        return $response
            ->withStatus(429)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-RateLimit-Limit', (string)$limit)
            ->withHeader('X-RateLimit-Remaining', (string)$remaining)
            ->withHeader('X-RateLimit-Reset', (string)$reset)
            ->withHeader('Retry-After', (string)($reset - \time()));
    }
}
