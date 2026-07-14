<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller responsavel por servir os arquivos de configuracao do Universal Links (iOS) e App Links (Android).
 */
class WellKnownController
{
    /**
     * Retorna a configuracao do Apple App Site Association (AASA) para iOS.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function appleAppSiteAssociation(Request $request, Response $response): Response
    {
        $data = [
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => 'YOUR_APPLE_TEAM_ID.br.dev.davidfreitas.habitus',
                        'paths' => ['/verify-email'],
                    ],
                ],
            ],
        ];

        $response->getBody()->write((string)\json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Retorna a configuracao do Digital Asset Links para Android.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function assetLinks(Request $request, Response $response): Response
    {
        $data = [
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => 'br.dev.davidfreitas.habitus',
                    'sha256_cert_fingerprints' => [
                        'YOUR_ANDROID_SHA256_FINGERPRINT',
                    ],
                ],
            ],
        ];

        $response->getBody()->write((string)\json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
