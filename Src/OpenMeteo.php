<?php
declare(strict_types=1);

namespace PhpWeather\Provider\OpenMeteo;

use DateInterval;
use DateTime;
use DateTimeZone;
use PhpWeather\Common\Source;
use PhpWeather\Common\UnitConverter;
use PhpWeather\Constants\Type;
use PhpWeather\Constants\Unit;
use PhpWeather\HttpProvider\AbstractHttpProvider;
use PhpWeather\Weather;
use PhpWeather\WeatherCollection;
use PhpWeather\WeatherQuery;

class OpenMeteo extends AbstractHttpProvider
{
    /**
     * @var Source[]|null
     */
    private ?array $sources = null;

    protected function getForecastWeatherQueryString(WeatherQuery $query): string
    {
        return sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&hourly=temperature_2m,relativehumidity_2m,dewpoint_2m,apparent_temperature,pressure_msl,precipitation,weathercode,cloudcover,windspeed_10m,winddirection_10m&timezone=UTC&current_weather=true',
            $query->getLatitude(),
            $query->getLongitude()
        );
    }

    protected function getHistoricalWeatherQueryString(WeatherQuery $query): string
    {
        return $this->getCurrentWeatherQueryString($query);
    }

    protected function getCurrentWeatherQueryString(WeatherQuery $query): string
    {
        $startDate = $query->getDateTime();
        if ($startDate === null) {
            $startDate = (new DateTime())
                ->setTimezone(new DateTimeZone('UTC'))
                ->setTime(0, 0);
        }

        $endDate = DateTime::createFromInterface($startDate)->add(new DateInterval('P1D'));

        return sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&hourly=temperature_2m,relativehumidity_2m,dewpoint_2m,apparent_temperature,pressure_msl,precipitation,weathercode,cloudcover,windspeed_10m,winddirection_10m&timezone=UTC&current_weather=true&start_date=%s&end_date=%s',
            $query->getLatitude(),
            $query->getLongitude(),
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    protected function getHistoricalTimeLineWeatherQueryString(WeatherQuery $query): string
    {
        return $this->getCurrentWeatherQueryString($query);
    }

    protected function mapRawData(float $latitude, float $longitude, array $rawData, ?string $type = null, ?string $units = null): Weather|WeatherCollection
    {
        if ($units === null) {
            $units = Unit::METRIC;
        }

        $utc = new DateTimeZone('UTC');
        $currentDateTime = (new DateTime())->setTimezone($utc);
        $currentWeather = null;
        if (
            array_key_exists('current_weather', $rawData) &&
            is_array($rawData['current_weather']) &&
            array_key_exists('time', $rawData['current_weather'])
        ) {
            $currentTimestamp = strtotime($rawData['current_weather']['time']);
            if (is_int($currentTimestamp)) {
                $currentDateTime->setTimestamp($currentTimestamp);
            }
            $currentWeather = (new \PhpWeather\Common\Weather())
                ->setLatitude($latitude)
                ->setLongitude($longitude);
            foreach ($this->getSources() as $source) {
                $currentWeather->addSource($source);
            }
            $currentWeather->setUtcDateTime($currentDateTime);
            $currentWeather->setType(Type::CURRENT);
            if (array_key_exists('temperature', $rawData['current_weather'])) {
                $currentWeather->setTemperature(
                    UnitConverter::mapTemperature($rawData['current_weather']['temperature'], Unit::TEMPERATURE_CELSIUS, $units)
                );
            }
            if (array_key_exists('windspeed', $rawData['current_weather'])) {
                $currentWeather->setWindSpeed(
                    UnitConverter::mapSpeed($rawData['current_weather']['windspeed'], Unit::SPEED_KMH, $units)
                );
            }
            if (array_key_exists('winddirection', $rawData['current_weather'])) {
                $currentWeather->setWindDirection(
                    $rawData['current_weather']['winddirection']
                );
            }
            if (array_key_exists('weathercode', $rawData['current_weather'])) {
                $currentWeather->setWeathercode((int)$rawData['current_weather']['weathercode']);
                $currentWeather->setIcon($this->mapIcon((int)$rawData['current_weather']['weathercode'], $currentDateTime, $latitude, $longitude));
            }
        }


        $weatherCollection = new \PhpWeather\Common\WeatherCollection();
        if ($currentWeather !== null) {
            $weatherCollection->add($currentWeather);
        }
        if (
            array_key_exists('hourly', $rawData) &&
            is_array($rawData['hourly']) &&
            array_key_exists('time', $rawData['hourly'])
        ) {
            $itemCount = count($rawData['hourly']['time']);

            for ($i = 0; $i < $itemCount; $i++) {
                $weather = (new \PhpWeather\Common\Weather())
                    ->setLatitude($latitude)
                    ->setLongitude($longitude);
                foreach ($this->getSources() as $source) {
                    $weather->addSource($source);
                }

                $weatherDateTime = (new DateTime())->setTimezone($utc);
                $weatherTimeStamp = strtotime($rawData['hourly']['time'][$i]);
                if (!is_int($weatherTimeStamp)) {
                    continue;
                }
                $weatherDateTime->setTimestamp($weatherTimeStamp);
                $weather->setUtcDateTime($weatherDateTime);

                if ($weatherDateTime < $currentDateTime) {
                    $weather->setType(Type::HISTORICAL);
                } elseif ($weatherDateTime === $currentDateTime) {
                    $weather->setType(Type::CURRENT);
                } else {
                    $weather->setType(Type::FORECAST);
                }
                $weather->setTemperature(UnitConverter::mapTemperature($rawData['hourly']['temperature_2m'][$i], Unit::TEMPERATURE_CELSIUS, $units));
                $weather->setFeelsLike(UnitConverter::mapTemperature($rawData['hourly']['apparent_temperature'][$i], Unit::TEMPERATURE_CELSIUS, $units));
                $weather->setDewPoint(UnitConverter::mapTemperature($rawData['hourly']['dewpoint_2m'][$i], Unit::TEMPERATURE_CELSIUS, $units));
                $weather->setHumidity($rawData['hourly']['relativehumidity_2m'][$i]);
                $weather->setPressure(UnitConverter::mapPressure($rawData['hourly']['pressure_msl'][$i], Unit::PRESSURE_HPA, $units));
                $weather->setWindSpeed(UnitConverter::mapSpeed($rawData['hourly']['windspeed_10m'][$i], Unit::SPEED_KMH, $units));
                $weather->setWindDirection($rawData['hourly']['winddirection_10m'][$i]);
                $weather->setPrecipitation(UnitConverter::mapPrecipitation($rawData['hourly']['precipitation'][$i], Unit::PRECIPITATION_MM, $units));
                $weather->setCloudCover($rawData['hourly']['cloudcover'][$i]);
                $weather->setWeathercode((int)$rawData['hourly']['weathercode'][$i]);
                $weather->setIcon($this->mapIcon((int)$rawData['hourly']['weathercode'][$i], $weatherDateTime, $latitude, $longitude));

                $weatherCollection->add($weather);
            }
        }

        return $weatherCollection;
    }

    public function getSources(): array
    {
        if ($this->sources === null) {
            $this->sources = [
                new Source(
                    'open-meteo',
                    'Open-Meteo',
                    'https://open-meteo.com/'
                ),
            ];
        }

        return $this->sources;
    }

    private function mapIcon(int $weatherCode, DateTime $weatherDateTime, float $latitude, float $longitude): ?string
    {
        $dateSunInfo = date_sun_info($weatherDateTime->getTimestamp(), $latitude, $longitude);
        $isNight = $weatherDateTime->getTimestamp() < $dateSunInfo['sunrise'] || $weatherDateTime->getTimestamp() > $dateSunInfo['sunset'];

        return match ($weatherCode) {
            0, 1 => $isNight ? 'night-clear' : 'day-sunny',
            2 => $isNight ? 'night-partly-cloudy' : 'day-sunny-overcast',
            3 => $isNight ? 'night-cloudy' : 'day-cloudy',
            45, 48 => $isNight ? 'night-fog' : 'day-fog',
            51, 53, 55, 56, 57 => $isNight ? 'night-sprinkle' : 'day-sprinkle',
            61, 63, 65, 66, 67 => $isNight ? 'night-rain' : 'day-rain',
            71, 73, 75, 77, 85, 86 => $isNight ? 'night-snow' : 'day-snow',
            80, 81, 82 => $isNight ? 'night-showers' : 'day-showers',
            95, 96, 99 => $isNight ? 'night-thunderstorm' : 'day-thunderstorm',
            default => null,
        };
    }
}