<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Http\Middleware;

use App\Infrastructure\Http\Middleware\RateLimitMiddleware;
use App\Infrastructure\Persistence\Redis\RedisCache;
use App\Infrastructure\Security\JwtService;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Response;

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
        array $serverParams = [],
        ?array $parsedBody = null
    ): ServerRequestInterface {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn($method);

        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($uriString);
        $request->method('getUri')->willReturn($uri);

        $request->method('getHeaderLine')
            ->willReturnCallback(fn(string $name) => $headers[$name] ?? '');

        $defaultServerParams = ['REMOTE_ADDR' => '127.0.0.1'];
        $request->method('getServerParams')->willReturn(array_merge($defaultServerParams, $serverParams));
        $request->method('getParsedBody')->willReturn($parsedBody);

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
        
        $requestHandler->expects($this->once())->method('handle')->willReturn($expectedResponse);
        $redisCache->expects($this->never())->method('incr');
        
        $response = $middleware->process($request, $requestHandler);
        self::assertSame($expectedResponse, $response);
    }

    public function testNonLoginFirstRequest(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        
        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $request = $this->createRequestMock('GET', '/api/v1/habits');
        
        $redisCache->expects($this->once())->method('incr')->with('rate_limit:ip:127.0.0.1')->willReturn(1);
        $redisCache->method('getRaw')->with('rate_limit_blocks:ip:127.0.0.1')->willReturn(null);
        $redisCache->expects($this->once())->method('expire')->with('rate_limit:ip:127.0.0.1', 60);
        
        $requestHandler->expects($this->once())->method('handle')->willReturn(new Response());
        
        $response = $middleware->process($request, $requestHandler);
        self::assertSame('5', $response->getHeaderLine('X-RateLimit-Limit'));
    }

    public function testLoginSuccessfulResetsCounters(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        
        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $request = $this->createRequestMock('POST', '/api/v1/auth/login', [], [], ['email' => 'test@test.com']);
        
        $redisCache->method('getRaw')->willReturn(0);
        
        $successResponse = new Response(200);
        $requestHandler->expects($this->once())->method('handle')->willReturn($successResponse);
        
        $redisCache->expects($this->exactly(4))->method('delete');
        
        $response = $middleware->process($request, $requestHandler);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testLoginFailedIncrementsCounters(): void
    {
        $redisCache = $this->createMock(RedisCache::class);
        $jwtService = $this->createMock(JwtService::class);
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        
        $middleware = $this->createMiddleware($redisCache, $jwtService);
        $request = $this->createRequestMock('POST', '/api/v1/auth/login', [], [], ['email' => 'test@test.com']);
        
        $redisCache->method('getRaw')->willReturn(0);
        
        $failResponse = new Response(401);
        $requestHandler->expects($this->once())->method('handle')->willReturn($failResponse);
        
        $redisCache->expects($this->exactly(2))->method('incr')->willReturnMap([
            ['rate_limit:login:ip:127.0.0.1', 1],
            ['rate_limit:login:email:test@test.com', 1]
        ]);
        
        $response = $middleware->process($request, $requestHandler);
        self::assertSame(401, $response->getStatusCode());
    }
}
