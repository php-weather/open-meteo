# PHP Weather Provider for Open Meteo

![Packagist Version](https://img.shields.io/packagist/v/php-weather/open-meteo)  
![PHP Weather Common Version](https://img.shields.io/badge/phpweather--core-0.4.*-brightgreen)
![PHP Weather HTTP Provider Version](https://img.shields.io/badge/phpweather--http--provider-0.6.*-brightgreen)  
![GitHub Release Date](https://img.shields.io/github/release-date/php-weather/open-meteo)
![GitHub commits since tagged version](https://img.shields.io/github/commits-since/php-weather/open-meteo/0.3.2)
![GitHub last commit](https://img.shields.io/github/last-commit/php-weather/open-meteo)  
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/php-weather/open-meteo/php.yml?branch=main)
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
