<?php

declare(strict_types=1);

namespace App\Test\Unit\Infrastructure\Http\Middleware;

use App\Infrastructure\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Persistence\Redis\RedisCache;
use App\Infrastructure\Security\JwtService;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Response;
use stdClass;

/**
 * @covers \App\Infrastructure\Http\Middleware\RateLimitMiddleware
 */
final class RateLimitMiddlewareTest extends TestCase
{
    private array $defaultSettings = [
        'enabled' => true,
        'max_requests' => 5,
        'window' => 60,
        'trusted_proxies' => ['10.0.0.1'],
    ];

    private function createMiddleware(RedisCache $redisCache, JwtService $jwtService, array $settings = []): RateLimitMiddleware
    {
        return new RateLimitMiddleware(
            $redisCache,
            $jwtService,
            array_merge($this->defaultSettings, $settings),
        );
    }

    private function createRequestMock(
        string $method = 'GET',
        string $uriString = '/',
        array $headers = [],
        array $serverParams = []
    ): ServerRequestInterface {
        $request = $this->createMock(ServerRequestInterface::class);

        $request->method('getMethod')->willReturn($method);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('__toString')->willReturn($uriString);
        $request->method('getUri')->willReturn($uri);

        $request->method('getHeaderLine')
            ->willReturnCallback(fn(string $name) => $headers[$name] ?? '');

        $defaultServerParams = ['REMOTE_ADDR' => '127.0.0.1'];
        $fullServerParams = array_merge($defaultServerParams, $serverParams);
        $request->method('getServerParams')->willReturn($fullServerParams);

        return $request;
    }

    public function testMiddlewareDisabled(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService, ['enabled' => false]);
        $request = $this->createRequestMock();
        $expectedResponse = new Response();

        $requestHandler->expects($this->once())
            ->method('handle')
            ->with($this->identicalTo($request))
            ->willReturn($expectedResponse);

        $redisCache->expects($this->never())->method('incr');

        $response = $middleware->process($request, $requestHandler);

        self::assertSame($expectedResponse, $response);
    }

    public function testFirstRequestInWindow(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $request = $this->createRequestMock();
        $initialResponse = new Response();

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(1);

        $redisCache->expects($this->once())
            ->method('expire')
            ->with('rate_limit:ip:127.0.0.1', 60);

        $redisCache->expects($this->once())
            ->method('ttl')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(60);

        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn($initialResponse);

        $response = $middleware->process($request, $requestHandler);

        self::assertSame('5', $response->getHeaderLine('X-RateLimit-Limit'));
        self::assertSame('4', $response->getHeaderLine('X-RateLimit-Remaining'));
        self::assertNotEmpty($response->getHeaderLine('X-RateLimit-Reset'));
    }

    public function testSubsequentRequestsWithinLimit(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $request = $this->createRequestMock();
        $initialResponse = new Response();

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(3);

        $redisCache->expects($this->never())->method('expire');

        $redisCache->expects($this->once())
            ->method('ttl')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(30);

        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn($initialResponse);

        $response = $middleware->process($request, $requestHandler);

        self::assertSame('5', $response->getHeaderLine('X-RateLimit-Limit'));
        self::assertSame('2', $response->getHeaderLine('X-RateLimit-Remaining'));
        self::assertNotEmpty($response->getHeaderLine('X-RateLimit-Reset'));
    }

    public function testExceedingRateLimit(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $request = $this->createRequestMock();

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(6);

        $redisCache->expects($this->once())
            ->method('ttl')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(10);

        $requestHandler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $requestHandler);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('5', $response->getHeaderLine('X-RateLimit-Limit'));
        self::assertSame('0', $response->getHeaderLine('X-RateLimit-Remaining'));
        
        $responseData = json_decode((string)$response->getBody(), true);
        self::assertSame('Excesso de Requisições', $responseData['error']);
    }

    public function testRateLimitWithAuthenticatedUser(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $token = 'some.jwt.token';
        $userId = 123;
        $decodedToken = (object)['sub' => $userId];

        $request = $this->createRequestMock('GET', '/', ['Authorization' => 'Bearer ' . $token]);

        $jwtService->expects($this->once())
            ->method('validateToken')
            ->with($token)
            ->willReturn($decodedToken);

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:user:' . $userId)
            ->willReturn(1);

        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $requestHandler);
    }

    public function testRateLimitFallsBackToIpWhenInvalidToken(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $token = 'invalid.jwt.token';

        $request = $this->createRequestMock('GET', '/', ['Authorization' => 'Bearer ' . $token]);

        $jwtService->expects($this->once())
            ->method('validateToken')
            ->with($token)
            ->willThrowException(new Exception('Token inválido'));

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:ip:127.0.0.1')
            ->willReturn(1);

        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $requestHandler);
    }

    public function testIdentifierFromXForwardedForFromTrustedProxy(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $clientIp = '192.168.1.100';
        // REMOTE_ADDR is 10.0.0.1 (trusted proxy)
        $request = $this->createRequestMock('GET', '/', [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => $clientIp . ', 10.0.0.1'
        ]);

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:ip:' . $clientIp)
            ->willReturn(1);

        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $requestHandler);
    }

    public function testIdentifierIgnoresXForwardedForFromUntrustedProxy(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);

        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $clientIp = '192.168.1.100';
        $untrustedProxy = '203.0.113.1';
        $request = $this->createRequestMock('GET', '/', [], [
            'REMOTE_ADDR' => $untrustedProxy,
            'HTTP_X_FORWARDED_FOR' => $clientIp
        ]);

        $redisCache->expects($this->once())
            ->method('incr')
            ->with('rate_limit:ip:' . $untrustedProxy)
            ->willReturn(1);

        $requestHandler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $middleware->process($request, $requestHandler);
    }
}
