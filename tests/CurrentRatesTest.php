<?php

declare(strict_types=1);

namespace Peso\Services\Tests;

use Arokettu\Date\Date;
use Peso\Core\Exceptions\ConversionRateNotFoundException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\SuccessResponse;
use Peso\Core\Services\SDK\Exceptions\HttpFailureException;
use Peso\Services\OpenExchangeRatesService;
use Peso\Services\OpenExchangeRatesService\AppType;
use Peso\Services\Tests\Helpers\MockClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

// phpcs:disable Generic.Files.LineLength.TooLong
class CurrentRatesTest extends TestCase
{
    public function testRateFree(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, cache: $cache, httpClient: $http);

        $response = $service->send(new CurrentExchangeRateRequest('USD', 'EUR'));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('0.857649', $response->rate->value);
        self::assertEquals(Date::today(), $response->date);

        $response = $service->send(new CurrentExchangeRateRequest('USD', 'PHP'));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('56.742298', $response->rate->value);
        self::assertEquals(Date::today(), $response->date->toString());

        $response = $service->send(new CurrentExchangeRateRequest('USD', 'JPY'));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('145.24400539', $response->rate->value);
        self::assertEquals(Date::today(), $response->date->toString());

        self::assertCount(1, $http->getRequests()); // subsequent requests are cached
    }

    public function testRatePaid(): void
    {
        self::markTestSkipped("I don't have a paid key");
    }

    public function testRateFreeWithBase(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, cache: $cache, httpClient: $http);

        $response = $service->send(new CurrentExchangeRateRequest('PHP', 'USD'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/USD', $response->exception->getMessage());

        $response = $service->send(new CurrentExchangeRateRequest('PHP', 'EUR'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/EUR', $response->exception->getMessage());

        $response = $service->send(new CurrentExchangeRateRequest('PHP', 'JPY'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/JPY', $response->exception->getMessage());

        self::assertCount(0, $http->getRequests()); // no requests
    }

    public function testRateFreeWithBaseButMarkedAsSubscription(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Subscription, cache: $cache, httpClient: $http);

        self::expectException(HttpFailureException::class);
        self::expectExceptionMessage(
            '"Changing the API `base` currency is available for Developer, Enterprise and Unlimited plan clients. Please upgrade, or contact support@openexchangerates.org with any questions."'
        );
        $service->send(new CurrentExchangeRateRequest('PHP', 'USD'));
    }

    public function testRatePaidWithBase(): void
    {
        self::markTestSkipped("I don't have a paid key");
    }

    public function testRateFreeWithSymbols(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, symbols: [
            'GBP', 'CZK', 'RUB', 'EUR', 'PHP', 'USD',
        ], cache: $cache, httpClient: $http);

        $response = $service->send(new CurrentExchangeRateRequest('USD', 'EUR'));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('0.857649', $response->rate->value);
        self::assertEquals(Date::today(), $response->date);

        $response = $service->send(new CurrentExchangeRateRequest('USD', 'PHP'));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('56.742298', $response->rate->value);
        self::assertEquals(Date::today(), $response->date);

        // not included
        $response = $service->send(new CurrentExchangeRateRequest('USD', 'JPY'));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for USD/JPY', $response->exception->getMessage());

        self::assertCount(1, $http->getRequests()); // subsequent requests are cached
    }

    public function testRatePaidWithSymbols(): void
    {
        self::markTestSkipped("I don't have a paid key");
    }

    public function testRatePaidWithBaseWithSymbols(): void
    {
        self::markTestSkipped("I don't have a paid key");
    }
}
