<?php

declare(strict_types=1);

namespace Peso\Services\Tests;

use Arokettu\Date\Calendar;
use Peso\Core\Exceptions\ConversionRateNotFoundException;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
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
class HistoricalRatesTest extends TestCase
{
    public function testRateFree(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();
        $date = Calendar::parse('2025-06-13');

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, cache: $cache, httpClient: $http);

        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'EUR', $date));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('0.865838', $response->rate->value);
        self::assertEquals('2025-06-13', $response->date->toString());

        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'PHP', $date));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('56.08', $response->rate->value);
        self::assertEquals('2025-06-13', $response->date->toString());

        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'JPY', $date));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('144.11', $response->rate->value);
        self::assertEquals('2025-06-13', $response->date->toString());

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
        $date = Calendar::parse('2025-06-13');

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, cache: $cache, httpClient: $http);

        $response = $service->send(new HistoricalExchangeRateRequest('PHP', 'EUR', $date));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/EUR on 2025-06-13', $response->exception->getMessage());

        $response = $service->send(new HistoricalExchangeRateRequest('PHP', 'USD', $date));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/USD on 2025-06-13', $response->exception->getMessage());

        $response = $service->send(new HistoricalExchangeRateRequest('PHP', 'JPY', $date));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for PHP/JPY on 2025-06-13', $response->exception->getMessage());

        self::assertCount(0, $http->getRequests()); // no requests
    }

    public function testRateFreeWithBaseButMarkedAsSubscription(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();
        $date = Calendar::parse('2025-06-13');

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Subscription, cache: $cache, httpClient: $http);

        self::expectException(HttpFailureException::class);
        self::expectExceptionMessage(
            '"Changing the API `base` currency is available for Developer, Enterprise and Unlimited plan clients. Please upgrade, or contact support@openexchangerates.org with any questions."'
        );
        $service->send(new HistoricalExchangeRateRequest('PHP', 'EUR', $date));
    }

    public function testRatePaidWithBase(): void
    {
        self::markTestSkipped("I don't have a paid key");
    }

    public function testRateFreeWithSymbols(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();
        $date = Calendar::parse('2025-06-13');

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, symbols: [
            'GBP', 'PHP', 'RUB', 'USD', 'ZAR', 'EUR',
        ], cache: $cache, httpClient: $http);

        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'EUR', $date));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('0.865838', $response->rate->value);
        self::assertEquals('2025-06-13', $response->date->toString());

        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'PHP', $date));
        self::assertInstanceOf(SuccessResponse::class, $response);
        self::assertEquals('56.08', $response->rate->value);
        self::assertEquals('2025-06-13', $response->date->toString());

        // not included
        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'JPY', $date));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for USD/JPY on 2025-06-13', $response->exception->getMessage());

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

    public function testFutureDate(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());
        $http = MockClient::get();
        $date = Calendar::parse('2035-01-01');

        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free, cache: $cache, httpClient: $http);

        $response = $service->send(new HistoricalExchangeRateRequest('USD', 'JPY', $date));
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertInstanceOf(ConversionRateNotFoundException::class, $response->exception);
        self::assertEquals('Unable to find exchange rate for USD/JPY on 2035-01-01', $response->exception->getMessage());
    }
}
