<?php

namespace GeoTools;

require_once 'Coordinate.php';


/**
 * Class Line
 */
class Line {

    const ERROR_ARGUMENT_TYPE = 20;

    /**
     * @var Coordinate
     */
    protected $point1;


    /**
     * @var Coordinate
     */
    protected $point2;


    /**
     * Line constructor.
     * @param Coordinate|array $point1
     * @param Coordinate|array $point2
     * @throws \InvalidArgumentException
     */
    public function __construct($point1, $point2) {

        $this->point1 = $this->toCoordinate($point1);
        $this->point2 = $this->toCoordinate($point2);
    }


    /**
     * @param Coordinate|array $point1
     * @throws \InvalidArgumentException
     */
    public function setPoint1(Coordinate $point1) {

        $this->point1 = $this->toCoordinate($point1);
    }


    /**
     * @param Coordinate|array $point2
     * @throws \InvalidArgumentException
     */
    public function setPoint2($point2) {

        $this->point2 = $this->toCoordinate($point2);
    }


    /**
     * @return Coordinate
     */
    public function getPoint1() {
        return $this->point1;
    }


    /**
     * @return Coordinate
     */
    public function getPoint2() {
        return $this->point2;
    }


    /**
     * Returns an array containing the two points.
     * @return array
     */
    public function getCoordinates() {
        return [$this->point1->getCoordinates(), $this->point2->getCoordinates()];
    }


    /**
     * @param Line|array $line
     * @return bool
     */
    public function isCross($line) {

        $line = $this->toLine($line);

        $line1 = $this->getCoordinates();
        $line2 = $line->getCoordinates();

        $v1 = ($line2[1][0]-$line2[0][0])*($line1[0][1]-$line2[0][1])-($line2[1][1]-$line2[0][1])*($line1[0][0]-$line2[0][0]);
        $v2 = ($line2[1][0]-$line2[0][0])*($line1[1][1]-$line2[0][1])-($line2[1][1]-$line2[0][1])*($line1[1][0]-$line2[0][0]);
        $v3 = ($line1[1][0]-$line1[0][0])*($line2[0][1]-$line1[0][1])-($line1[1][1]-$line1[0][1])*($line2[0][0]-$line1[0][0]);
        $v4 = ($line1[1][0]-$line1[0][0])*($line2[1][1]-$line1[0][1])-($line1[1][1]-$line1[0][1])*($line2[1][0]-$line1[0][0]);

        return ($v1*$v2<0) && ($v3*$v4<0);
    }


    /**
     * @param array|Coordinate $point
     * @return Coordinate
     * @throws \InvalidArgumentException
     */
    private function toCoordinate($point) {

        if ($point instanceof Coordinate) {
            return $point;

        } elseif (is_array($point) &&
            isset($point[0]) &&
            isset($point[1]) &&
            is_numeric($point[0]) &&
            is_numeric($point[1])
        ) {
            return new Coordinate($point[0], $point[1]);

        } else {
            throw new \InvalidArgumentException('Error argument type', self::ERROR_ARGUMENT_TYPE);
        }
    }


    /**
     * @param array|Line $line
     * @return Line
     * @throws \InvalidArgumentException
     */
    private function toLine($line) {

        if ($line instanceof Line) {
            return $line;

        } elseif (is_array($line) &&
            isset($line[0]) &&
            isset($line[0][0]) &&
            isset($line[0][1]) &&
            isset($line[1]) &&
            isset($line[1][0]) &&
            isset($line[1][1]) &&

            is_numeric($line[0][0]) &&
            is_numeric($line[0][1]) &&

            is_numeric($line[1][0]) &&
            is_numeric($line[1][1])
        ) {
            return new Line($line[0], $line[1]);

        } else {
            throw new \InvalidArgumentException('Error argument type', self::ERROR_ARGUMENT_TYPE);
        }
    }
}