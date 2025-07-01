<?php

declare(strict_types=1);

namespace Peso\Services\Tests;

use Arokettu\Date\Date;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Services\OpenExchangeRatesService;
use Peso\Services\OpenExchangeRatesService\AppType;
use PHPUnit\Framework\TestCase;
use stdClass;

final class SupportTest extends TestCase
{
    public function testRequestsFree(): void
    {
        $service = new OpenExchangeRatesService('xxxfreexxx', AppType::Free);

        self::assertTrue($service->supports(new CurrentExchangeRateRequest('USD', 'EUR')));
        self::assertTrue($service->supports(new HistoricalExchangeRateRequest('USD', 'EUR', Date::today())));
        self::assertFalse($service->supports(new CurrentExchangeRateRequest('EUR', 'USD')));
        self::assertFalse($service->supports(new HistoricalExchangeRateRequest('EUR', 'USD', Date::today())));
        self::assertFalse($service->supports(new stdClass()));
    }

    public function testRequests(): void
    {
        $service = new OpenExchangeRatesService('xxxpaidxxx', AppType::Subscription);

        self::assertTrue($service->supports(new CurrentExchangeRateRequest('USD', 'EUR')));
        self::assertTrue($service->supports(new HistoricalExchangeRateRequest('USD', 'EUR', Date::today())));
        self::assertTrue($service->supports(new CurrentExchangeRateRequest('EUR', 'USD')));
        self::assertTrue($service->supports(new HistoricalExchangeRateRequest('EUR', 'USD', Date::today())));
        self::assertFalse($service->supports(new stdClass()));
    }
}
