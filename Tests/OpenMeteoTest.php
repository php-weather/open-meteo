<?php
declare(strict_types=1);

namespace PhpWeather\Provider\OpenMeteo;

use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PhpWeather\Common\WeatherQuery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OpenMeteoTest extends TestCase
{
    private MockObject|ClientInterface $client;
    private MockObject|RequestFactoryInterface $requestFactory;
    private OpenMeteo $provider;

    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);

        $this->provider = new OpenMeteo($this->client, $this->requestFactory);
    }

    public function testCurrentWeather(): void
    {
        $latitude = 47.8739259;
        $longitude = 8.0043961;
        $datetime = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'))->setDate(2022, 07, 31)->setTime(16, 00);
        $testQuery = WeatherQuery::create($latitude, $longitude, $datetime);
        $testString = 'https://api.open-meteo.com/v1/forecast?latitude=47.8739259&longitude=8.0043961&hourly=temperature_2m,relativehumidity_2m,dewpoint_2m,apparent_temperature,pressure_msl,precipitation,weathercode,cloudcover,windspeed_10m,winddirection_10m&timezone=UTC&current_weather=true&start_date=2022-07-31&end_date=2022-08-01';

        $request = $this->createMock(RequestInterface::class);
        $this->requestFactory->expects(self::once())->method('createRequest')->with('GET', $testString)->willReturn($request);

        $responseBodyString = file_get_contents(__DIR__.'/resources/currentWeather.json');
        $response = $this->createMock(ResponseInterface::class);
        if ($resource = fopen('data://text/plain,'.$responseBodyString, 'rb')) {
            $reponseStream = new Stream($resource);
        } else {
            $this->fail();
        }
        $response->expects(self::once())->method('getBody')->willReturn($reponseStream);
        $this->client->expects(self::once())->method('sendRequest')->with($request)->willReturn($response);

        $currentWeather = $this->provider->getCurrentWeather($testQuery);
        self::assertSame($latitude, $currentWeather->getLatitude());
        self::assertSame(19.1, $currentWeather->getTemperature());
        self::assertSame('day-sunny-overcast', $currentWeather->getIcon());
        self::assertCount(1, $currentWeather->getSources());

    }
}