<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Line.php';


/**
 * Class LineTest
 */
class LineTest extends TestCase {


    public function test__construct() {

    }


    public function testSetPoint1() {

    }


    public function testSetPoint2() {

    }


    public function testGetPoint1() {

    }


    public function testGetPoint2() {

    }


    /**
     * @param array $point1
     * @param array $point2
     * @param array $point_middle
     * @dataProvider providerLinesMiddle
     */
    public function testGetMiddle($point1, $point2, $point_middle) {

        $line = new \GeoTools\Line($point1, $point2);
        $result_point = $line->getMiddle();

        $this->assertEquals($point_middle, $result_point->getCoordinates());
    }


    public function testGetCoordinates() {

    }


    public function testGetDistance() {

    }


    /**
     * @param array $point1
     * @param array $point2
     * @param array $point_contains
     * @dataProvider providerLinesContains
     */
    public function testIsContains($point1, $point2, $point_contains) {

        $line = new \GeoTools\Line($point1, $point2);
        $is_contains = $line->isContains($point_contains);

        $this->assertEquals(true, $is_contains);
    }


    /**
     *
     */
    public function testIsCross() {

    }


    /**
     * @return array
     */
    public function providerLinesContains() {

        return [
            [['53.88460788194942', '27.44767853502029'] , ['53.88204703873302', '27.452141730821076'], ['53.883327460341221', '27.449910132920685']],
            [['53.88460788194942', '27.44767853502029'] , ['53.88204703873302', '27.452141730821076'], ['53.88460788194942', '27.44767853502029']],
            [['53.88460788194942', '27.44767853502029'] , ['53.88204703873302', '27.452141730821076'], ['53.88204703873302', '27.452141730821076']],
        ];
    }


    /**
     * @return array
     */
    public function providerLinesMiddle() {

        return [
            [['53.88460788194942', '27.44767853502029'] , ['53.88204703873302', '27.452141730821076'], ['53.883327460341221', '27.449910132920685']]
        ];
    }
}
