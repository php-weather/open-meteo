# PHP Weather Provider for Open Meteo

![Packagist Version](https://img.shields.io/packagist/v/php-weather/open-meteo)  
![GitHub Release Date](https://img.shields.io/github/release-date/php-weather/open-meteo)
![GitHub commits since tagged version](https://img.shields.io/github/commits-since/php-weather/open-meteo/0.1.0)
![GitHub last commit](https://img.shields.io/github/last-commit/php-weather/open-meteo)  
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/php-weather/open-meteo/PHP%20Composer)
![GitHub](https://img.shields.io/github/license/php-weather/open-meteo)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/php-weather/open-meteo)

This is the [Open Meteo](https://open-meteo.com/) provider from PHP Weather.

> Open-Meteo collaborates with National Weather Services providing Open Data with 11 to 2 km resolution. Our high performance APIs select the best weather model for your location and provide data as a simple JSON API.  
> APIs are free without any API key for open-source developers and non-commercial use.

## Installation

Via Composer

```shell
composer require php-weather/open-meteo
```

## Usage

```php
$httpClient = new \Http\Adapter\Guzzle7\Client();
$openMeteo = new \PhpWeather\Provider\OpenMeteo\OpenMeteo($httpClient);

$latitude = 47.873;
$longitude = 8.004;

$currentWeatherQuery = \PhpWeather\Common\WeatherQuery::create($latitude, $longitude);
$currentWeather = $openMeteo->getCurrentWeather($currentWeatherQuery);
```