<?php

declare(strict_types=1);

namespace Peso\Services\Tests\Helpers;

use GuzzleHttp\Psr7\Response;
use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use Psr\Http\Message\RequestInterface;

final readonly class MockClient
{
    public static function get(): Client
    {
        $client = new Client();

        $client->on(
            new RequestMatcher('/api/latest.json', 'openexchangerates.org', ['GET'], ['https']),
            static function (RequestInterface $request) {
                $query = $request->getUri()->getQuery();
                switch ($request->getUri()->getQuery()) {
                    case 'app_id=xxxfreexxx&base=USD':
                        return new Response(body: fopen(__DIR__ . '/../data/latest.json', 'r'));

                    case 'app_id=xxxfreexxx&base=PHP':
                        return new Response(403, body: fopen(__DIR__ . '/../data/latest-php-free.json', 'r'));

                    case 'app_id=xxxfreexxx&base=USD&symbols=GBP%2CCZK%2CRUB%2CEUR%2CPHP%2CUSD':
                        return new Response(body: fopen(__DIR__ . '/../data/latest-symbols.json', 'r'));

                    default:
                        throw new \LogicException('Non-mocked query: ' . $query);
                }
            },
        );
        $client->on(
            new RequestMatcher('/api/historical/2025-06-13.json', 'openexchangerates.org', ['GET'], ['https']),
            static function (RequestInterface $request) {
                $query = $request->getUri()->getQuery();
                switch ($request->getUri()->getQuery()) {
                    case 'app_id=xxxfreexxx&base=USD':
                        return new Response(body: fopen(__DIR__ . '/../data/2025-06-13.json', 'r'));

                    case 'app_id=xxxfreexxx&base=PHP':
                        return new Response(403, body: fopen(__DIR__ . '/../data/2025-06-13-php-free.json', 'r'));

                    case 'app_id=xxxfreexxx&base=USD&symbols=GBP%2CPHP%2CRUB%2CUSD%2CZAR%2CEUR':
                        return new Response(body: fopen(__DIR__ . '/../data/2025-06-13-symbols.json', 'r'));

                    default:
                        throw new \LogicException('Non-mocked query: ' . $query);
                }
            },
        );
        $client->on(
            new RequestMatcher('/api/historical/2035-01-01.json', 'openexchangerates.org', ['GET'], ['https']),
            static function (RequestInterface $request) {
                $query = $request->getUri()->getQuery();
                switch ($request->getUri()->getQuery()) {
                    case 'app_id=xxxfreexxx&base=USD':
                        return new Response(400, body: fopen(__DIR__ . '/../data/2035-01-01.json', 'r'));

                    default:
                        throw new \LogicException('Non-mocked query: ' . $query);
                }
            },
        );

        return $client;
    }
}
