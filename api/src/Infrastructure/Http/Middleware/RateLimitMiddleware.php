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
        if (!$this->settings['enabled']) {
            return $handler->handle($request);
        }

        $identifier = $this->getIdentifier($request);
        $key = 'rate_limit:' . $identifier;

        $maxRequests = $this->settings['max_requests'];
        $window = $this->settings['window'];

        // Atomic increment
        $current = $this->cache->incr($key);

        if ($current === 1) {
            // First request in this window, set expiration
            $this->cache->expire($key, $window);
        }

        $ttl = $this->cache->ttl($key);
        $resetTime = \time() + ($ttl > 0 ? $ttl : $window);
        $remaining = $maxRequests - $current;

        if ($current > $maxRequests) {
            // Rate limit exceeded
            return $this->buildRateLimitResponse($maxRequests, 0, $resetTime);
        }

        $response = $handler->handle($request);

        // Add rate limit headers
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string)\max(0, $remaining))
            ->withHeader('X-RateLimit-Reset', (string)$resetTime)
        ;
    }

    private function getIdentifier(ServerRequestInterface $request): string
    {
        // Try to get user ID from token (if authenticated)
        $token = $this->extractToken($request);
        if ($token) {
            try {
                $decodedToken = $this->jwtService->validateToken($token);
                if ($decodedToken && isset($decodedToken->sub)) {
                    // Use user ID (sub claim) as identifier
                    return 'user:' . $decodedToken->sub;
                }
            } catch (Exception) {
                // Token is invalid or expired, fall through to IP-based rate limiting
            }
        }

        // Fallback to IP address
        $serverParams = $request->getServerParams();
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? 'unknown';

        // Identify real IP (Cloudflare + Trusted Proxies)
        if ($this->isTrustedProxy($remoteAddr)) {
            // Prioritize Cloudflare's connecting IP if from a trusted proxy
            if (!empty($serverParams['HTTP_CF_CONNECTING_IP'])) {
                return 'ip:' . $serverParams['HTTP_CF_CONNECTING_IP'];
            }

            // Fallback to X-Forwarded-For if trusted
            if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
                $ips = \explode(',', (string) $serverParams['HTTP_X_FORWARDED_FOR']);
                return 'ip:' . \trim($ips[0]);
            }
        }

        return 'ip:' . $remoteAddr;
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
            ->withHeader('Retry-After', (string)($reset - \time()))
        ;
    }
}
