<?php

declare(strict_types=1);

namespace Peso\Services\Tests;

use GuzzleHttp\Psr7\Response;
use Http\Mock\Client;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Services\SDK\Exceptions\HttpFailureException;
use Peso\Services\OpenExchangeRatesService;
use Peso\Services\OpenExchangeRatesService\AppType;
use PHPUnit\Framework\TestCase;
use stdClass;

final class EdgeCasesTest extends TestCase
{
    public function testInvalidRequest(): void
    {
        $service = new OpenExchangeRatesService('xxx', AppType::Free);

        $response = $service->send(new stdClass());
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(RequestNotSupportedException::class, $response->exception);
        self::assertEquals('Unsupported request type: "stdClass"', $response->exception->getMessage());
    }

    public function testHttpFailure(): void
    {
        $http = new Client();
        $http->setDefaultResponse(new Response(500, body: 'Server error or something'));

        $service = new OpenExchangeRatesService('xxx', AppType::Free, httpClient: $http);

        self::expectException(HttpFailureException::class);
        self::expectExceptionMessage('HTTP error 500. Response is "Server error or something"');
        $service->send(new CurrentExchangeRateRequest('USD', 'EUR'));
    }
}
