<?php

declare(strict_types=1);

namespace Peso\Services;

use Arokettu\Date\Date;
use DateInterval;
use Error;
use Override;
use Peso\Core\Exceptions\ExchangeRateNotFoundException;
use Peso\Core\Exceptions\RequestNotSupportedException;
use Peso\Core\Requests\CurrentExchangeRateRequest;
use Peso\Core\Requests\HistoricalExchangeRateRequest;
use Peso\Core\Responses\ErrorResponse;
use Peso\Core\Responses\ExchangeRateResponse;
use Peso\Core\Services\PesoServiceInterface;
use Peso\Core\Services\SDK\Cache\NullCache;
use Peso\Core\Services\SDK\Exceptions\HttpFailureException;
use Peso\Core\Services\SDK\HTTP\DiscoveredHttpClient;
use Peso\Core\Services\SDK\HTTP\DiscoveredRequestFactory;
use Peso\Core\Services\SDK\HTTP\UserAgentHelper;
use Peso\Core\Types\Decimal;
use Peso\Services\OpenExchangeRatesService\AppType;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class OpenExchangeRatesService implements PesoServiceInterface
{
    private const ENDPOINT_LATEST = 'https://openexchangerates.org/api/latest.json?%s';
    private const ENDPOINT_HISTORICAL = 'https://openexchangerates.org/api/historical/%s.json?%s';

    public function __construct(
        private string $appId,
        private AppType $appType,
        private array|null $symbols = null,
        private CacheInterface $cache = new NullCache(),
        private DateInterval $ttl = new DateInterval('PT1H'),
        private ClientInterface $httpClient = new DiscoveredHttpClient(),
        private RequestFactoryInterface $requestFactory = new DiscoveredRequestFactory(),
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function send(object $request): ExchangeRateResponse|ErrorResponse
    {
        if ($request instanceof CurrentExchangeRateRequest) {
            return self::performCurrentRequest($request);
        }
        if ($request instanceof HistoricalExchangeRateRequest) {
            return self::performHistoricalRequest($request);
        }
        return new ErrorResponse(RequestNotSupportedException::fromRequest($request));
    }

    private function performCurrentRequest(CurrentExchangeRateRequest $request): ErrorResponse|ExchangeRateResponse
    {
        if ($this->appType === AppType::Free && $request->baseCurrency !== 'USD') {
            return new ErrorResponse(ExchangeRateNotFoundException::fromRequest($request));
        }

        $query = [
            'app_id' => $this->appId,
            'base' => $request->baseCurrency,
            'symbols' => $this->symbols === null ? null : implode(',', $this->symbols),
        ];

        $url = \sprintf(self::ENDPOINT_LATEST, http_build_query($query, encoding_type: PHP_QUERY_RFC3986));

        $rates = $this->retrieveRates($url);

        return isset($rates[$request->quoteCurrency]) ?
            new ExchangeRateResponse(Decimal::init($rates[$request->quoteCurrency]), Date::today()) :
            new ErrorResponse(ExchangeRateNotFoundException::fromRequest($request));
    }

    private function performHistoricalRequest(
        HistoricalExchangeRateRequest $request,
    ): ErrorResponse|ExchangeRateResponse {
        if ($this->appType === AppType::Free && $request->baseCurrency !== 'USD') {
            return new ErrorResponse(ExchangeRateNotFoundException::fromRequest($request));
        }

        $query = [
            'app_id' => $this->appId,
            'base' => $request->baseCurrency,
            'symbols' => $this->symbols === null ? null : implode(',', $this->symbols),
        ];

        $url = \sprintf(
            self::ENDPOINT_HISTORICAL,
            $request->date->toString(),
            http_build_query($query, encoding_type: PHP_QUERY_RFC3986),
        );

        $rates = $this->retrieveRates($url);

        return isset($rates[$request->quoteCurrency]) ?
            new ExchangeRateResponse(Decimal::init($rates[$request->quoteCurrency]), $request->date) :
            new ErrorResponse(ExchangeRateNotFoundException::fromRequest($request));
    }

    private function retrieveRates(string $url): array
    {
        $cacheKey = hash('sha1', $url);

        $rates = $this->cache->get($cacheKey);

        if ($rates !== null) {
            return $rates;
        }

        $request = $this->requestFactory->createRequest('GET', $url);
        $request = $request->withHeader('User-Agent', UserAgentHelper::buildUserAgentString(
            'OpenExchangeRatesClient',
            'peso/openexchangerates-service',
            $request->hasHeader('User-Agent') ? $request->getHeaderLine('User-Agent') : null,
        ));
        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 400) {
            return []; // historical data missing
        }
        if ($response->getStatusCode() !== 200) {
            throw HttpFailureException::fromResponse($request, $response);
        }

        $rates = json_decode(
            (string)$response->getBody(),
            flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY,
        )['rates'] ?? throw new Error('No rates in the response');

        $this->cache->set($cacheKey, $rates, $this->ttl);

        return $rates;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function supports(object $request): bool
    {
        if (!$request instanceof CurrentExchangeRateRequest && !$request instanceof HistoricalExchangeRateRequest) {
            return false;
        }

        if ($this->appType === AppType::Free && $request->baseCurrency !== 'USD') {
            return false;
        }

        return true;
    }
}
