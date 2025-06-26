# Open Exchange Rates Client for Peso

[![Packagist]][Packagist Link]
[![PHP]][Packagist Link]
[![License]][License Link]

[Packagist]: https://img.shields.io/packagist/v/peso/openexchangerates-service.svg?style=flat-square
[PHP]: https://img.shields.io/packagist/php-v/peso/openexchangerates-service.svg?style=flat-square
[License]: https://img.shields.io/packagist/l/peso/openexchangerates-service.svg?style=flat-square

[Packagist Link]: https://packagist.org/packages/peso/openexchangerates-service
[License Link]: LICENSE.md

This is an exchange data provider for Peso that retrieves data from
[Open Exchange Rates](https://openexchangerates.org/).

## Installation

```bash
composer require peso/openexchangerates-service
```

Install the service with all recommended dependencies:

```bash
composer install peso/openexchangerates-service php-http/discovery guzzlehttp/guzzle symfony/cache
```

## Example

```php
<?php

use Peso\Peso\CurrencyConverter;
use Peso\Services\OpenExchangeRatesService;
use Peso\Services\OpenExchangeRatesService\AppType;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require __DIR__ . '/../vendor/autoload.php';

$cache = new Psr16Cache(new FilesystemAdapter(directory: __DIR__ . '/cache'));
$service = new OpenExchangeRatesService('...', AppType::Free, cache: $cache);
$converter = new CurrencyConverter($service);

// 10664.96 as of 2025-06-26
echo $converter->convert('12500', 'USD', 'EUR', 2), PHP_EOL;
```

## Documentation

Read the full documentation here: <https://phpeso.org/v0.x/services/openexchangerates.html>

## Support

Please file issues on our main repo at GitHub: <https://github.com/phpeso/openexchangerates-service/issues>

## License

The library is available as open source under the terms of the [MIT License][License Link].
