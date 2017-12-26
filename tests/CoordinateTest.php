<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Coordinate.php';

/**
 * Class CoordinateTest
 * @covers Coordinate
 */
class CoordinateTest extends TestCase {


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerValidCoordinates
     */
    public function test__construct($lat, $lng) {

        $coordinate = new \GeoTools\Coordinate($lat, $lng);
        $this->assertContainsOnlyInstancesOf('\GeoTools\Coordinate', [$coordinate]);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerFailCoordinates
     */
    public function test__constructFail($lat, $lng) {

        try {
            new \GeoTools\Coordinate($lat, $lng);
            $this->fail('valid error coordinate');

        } catch (\InvalidArgumentException $e) {
            $this->assertContainsOnlyInstancesOf('\InvalidArgumentException', [$e]);
        }
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerValidCoordinates
     */
    public function testGetLat($lat, $lng) {

        $coordinate = new \GeoTools\Coordinate($lat, $lng);

        $get_lat = $coordinate->getLat();
        $this->assertEquals($lat, $get_lat);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerValidCoordinates
     */
    public function testGetLng($lat, $lng) {

        $coordinate = new \GeoTools\Coordinate($lat, $lng);

        $get_lng = $coordinate->getLng();
        $this->assertEquals($lng, $get_lng);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerValidCoordinates
     */
    public function testGetCoordinates($lat, $lng) {

        $coordinate = new \GeoTools\Coordinate($lat, $lng);

        $get_coordinates = $coordinate->getCoordinates();
        $this->assertEquals([$lat, $lng], $get_coordinates);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerValidCoordinates
     */
    public function testIsValidLatitude($lat, $lng) {

        $isValid = \GeoTools\Coordinate::isValidLatitude($lat);
        $this->assertEquals(true, $isValid);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerFailCoordinates
     */
    public function testIsValidLatitudeFail($lat, $lng) {

        $isValid = \GeoTools\Coordinate::isValidLatitude($lat);
        $this->assertEquals(false, $isValid);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerValidCoordinates
     */
    public function testIsValidLongitude($lat, $lng) {

        $isValid = \GeoTools\Coordinate::isValidLongitude($lng);
        $this->assertEquals(true, $isValid);
    }


    /**
     * @param string $lat
     * @param string $lng
     * @dataProvider providerFailCoordinates
     */
    public function testIsValidLongitudeFail($lat, $lng) {

        $isValid = \GeoTools\Coordinate::isValidLongitude($lng);
        $this->assertEquals(false, $isValid);
    }


    /**
     * @return array
     */
    public function providerValidCoordinates() {

        return [
            [53.874743895707, 27.459158983398],
            ['53.877888496093', '27.46018895166'],
            ['53.871801742606', '27.464308824707'],
            ['-53.874743895707', '-27.459158983398'],
            ['53.8664250186436767234', '27.462592210956456453751'],
            [-53.874743895707, -27.459158983398],
            [-1, 2],
            [90, 180],
            [-90, -180],
            [-50, 123],
        ];
    }


    /**
     * @return array
     */
    public function providerFailCoordinates() {

        return [
            [532.874743895707, 273.459158983398],
            ['53,874743895707', '27,459158983398'],
            ['553.877888496093', '247.46018895166'],
            ['+53.8778884960932', ''],
            ['', ''],
            ['53.8773dsd8884960932', 'fjovhfdjvdcfd'],
            ['53.866425018ss6436767234', '27.46259221095645fff6453751'],
            [91, 181],
            [-91, -181],
            [-12334, 909],
        ];
    }
}
