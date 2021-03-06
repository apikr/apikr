<?php
namespace Apikr\SKPlanet\TMap;

use Apikr\SKPlanet\TMap\Contracts\SpatialPoint;
use Apikr\SKPlanet\TMap\Exception\ApiException;
use Apikr\SKPlanet\TMap\Exception\TMapException;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class TmapTest extends TestCase
{
    /** @var \Apikr\SKPlanet\TMap\TMap $tmap*/
    protected $tmap;

    public function setUp()
    {
        $this->tmap = new TMap(new Api(new Client(), new Configuration([
            'apiKey' => 'c2e0150d-a4ba-391e-89ad-d8f74f167432',
        ])));
    }

    public function testGetDistance()
    {
        $actual = $this->tmap->getDistance(
            new LatLng('37.55510690', '126.97069110'), // 서울역
            new LatLng('35.87143540', '128.60144500'), // 대구
            [
                'searchOption' => Configuration::OPTION_SHORTEST,
            ]
        );

        static::assertTrue(is_int($actual));

        // 오차범위 10%
        static::assertGreaterThan(277922 * 0.9, $actual);
        static::assertLessThan(277922 * 1.9, $actual);
    }

    public function testCalculateMany()
    {
        $actual = $this->tmap->getDistances([
            new LatLng('37.55510690', '126.97069110'), // 서울역
            new LatLng('37.54053970', '127.07185000'), // 건국대병원
            new LatLng('36.32598610', '127.42067930'), // 대전
            new LatLng('35.87143540', '128.60144500'), // 대구
        ], [
            'searchOption' => Configuration::OPTION_SHORTEST,
        ]);

        static::assertTrue(is_array($actual));
        static::assertEquals(3, count($actual));

        static::assertGreaterThan(11767 * 0.9, $actual[0]);
        static::assertLessThan(11767 * 1.9, $actual[0]);
        
        static::assertGreaterThan(157973 * 0.9, $actual[1]);
        static::assertLessThan(157973 * 1.9, $actual[1]);

        static::assertGreaterThan(137077 * 0.9, $actual[2]);
        static::assertLessThan(137077 * 1.9, $actual[2]);
    }
    
    public function testGeocodingSuccess()
    {
        $result = $this->tmap->geocoding("서울 노원구 상계6동 746-3"); // 구길주소
        
        static::assertInstanceOf(SpatialPoint::class, $result);
        static::assertEquals('37.650592', $result->getSpatialLat());
        static::assertEquals('127.061217', $result->getSpatialLng());
        
        $result = $this->tmap->geocoding("서울 중구 명동길 14 7층"); // 신길주소

        static::assertInstanceOf(SpatialPoint::class, $result);
        static::assertEquals('37.563411', $result->getSpatialLat());
        static::assertEquals('126.982886', $result->getSpatialLng());
    }

    public function testGeocodingFail()
    {
        try {
            $this->tmap->geocoding("모름 알수 없음.");
            static::fail();
        } catch (TMapException $e) {
            static::assertEquals('요청 데이터 오류입니다.([A2C521]주소 형식 오류입니다.)', $e->getMessage());
            static::assertEquals(ApiException::CODE_BAD_REQUEST, $e->getCode());
        }
    }

    public function testReverseGeocodingSuccess()
    {
        $result = $this->tmap->reverseGeocoding(new LatLng('37.563411', '127.061217'));
        
        static::assertEquals('서울특별시 동대문구 한천로6길 36', $result);
    }

    public function testReverseGeocodingFail()
    {
        try {
            $this->tmap->reverseGeocoding(new LatLng('40.563411', '127.061217'));
            static::fail();
        } catch (TMapException $e) {
            static::assertEquals('처리중 에러가 발생하였습니다.', $e->getMessage());
            static::assertEquals(ApiException::CODE_NULL_RESPONSE, $e->getCode());
        }
    }

    public function testConvertAddressToNew()
    {
        static::assertEquals(
            '경북 울진군 후포면 울진대게로 169-69',
            $this->tmap->convertAddress('경상북도 울진군 후포면 후포리 581-15')
        );
        static::assertEquals(
            '경북 울진군 후포면 울진대게로 169-69',
            $this->tmap->convertAddress('경상북도 울진군 후포면 후포리 581번지 15')
        );
        static::assertEquals(
            '전북 고창군 아산면 선운사로 39-8',
            $this->tmap->convertAddress('전라북도 고창군 아산면 삼인리 45번지')
        );
        static::assertEquals(
            '세종시 장군면 풍덕골길 7-6',
            $this->tmap->convertAddress('세종특별자치시 장군면 도계리 19-11')
        );
    }

    public function testConvertAddressToOld()
    {
        static::assertEquals(
            '경북 울진군 후포면 후포리 581-15',
            $this->tmap->convertAddress('경북 울진군 후포면 울진대게로 169-69', TMap::ADDRESS_OLD)
        );
        static::assertEquals(
            '전북 고창군 아산면 삼인리 45',
            $this->tmap->convertAddress('전북 고창군 아산면 선운사로 39-8', TMap::ADDRESS_OLD)
        );
    }
}
