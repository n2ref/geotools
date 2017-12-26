<?php

namespace GeoTools;

require_once 'Coordinate.php';


/**
 * Class Line
 */
class Line {

    const ERROR_ARGUMENT_TYPE = 20;
    const CROSS_ALL    = 11;
    const CROSS_VERTEX = 12;
    const CROSS_BORDER = 13;

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
     * @return Coordinate
     */
    public function getMiddle() {

        $point1_lat = $this->point1->getLat();
        $point2_lat = $this->point2->getLat();

        $point1_lng = $this->point1->getLng();
        $point2_lng = $this->point2->getLng();

        $lat = $point1_lat >= $point2_lat
            ? $point1_lat - ($point1_lat - $point2_lat) / 2
            : $point2_lat - ($point2_lat - $point1_lat) / 2;

        $lng = $point1_lng >= $point2_lng
            ? $point1_lng - ($point1_lng - $point2_lng) / 2
            : $point2_lng - ($point2_lng - $point1_lng) / 2;

        return $this->toCoordinate([$lat, $lng]);
    }


    /**
     * Returns an array containing the two points.
     * @return array
     */
    public function getCoordinates() {
        return [$this->point1->getCoordinates(), $this->point2->getCoordinates()];
    }


    /**
     * Calculates the length of the line (distance between the two coordinates).
     * @return float
     */
    public function getLength() {

        $lat1 = deg2rad($this->point1->getLat());
        $lng1 = deg2rad($this->point1->getLng());
        $lat2 = deg2rad($this->point2->getLat());
        $lng2 = deg2rad($this->point2->getLng());

        $x = ($lng2 - $lng1) * cos(($lat1 + $lat2) / 2);
        $y = $lat2 - $lat1;
        $d = sqrt(($x * $x) + ($y * $y)) * 6378136.0;

        return $d;
    }


    /**
     * @param Coordinate|array $point
     * @return bool
     */
    public function isContains($point) {

        $point = $this->toCoordinate($point);

        $lat = $point->getLat();
        $lng = $point->getLng();

        $line = $this->getCoordinates();

        return round(($lat - $line[0][0]) / ($line[1][0] - $line[0][0]), 10) == round(($lng - $line[0][1]) / ($line[1][1] - $line[0][1]), 10);
    }



    /**
     * @param Line|array $line
     * @param int        $cross_rule
     * @return bool
     */
    public function isCross($line, $cross_rule = null) {

        $line = $this->toLine($line);

        $line1 = $this->getCoordinates();
        $line2 = $line->getCoordinates();

        $v1 = ($line2[1][0] - $line2[0][0]) * ($line1[0][1] - $line2[0][1]) - ($line2[1][1] - $line2[0][1]) * ($line1[0][0] - $line2[0][0]);
        $v2 = ($line2[1][0] - $line2[0][0]) * ($line1[1][1] - $line2[0][1]) - ($line2[1][1] - $line2[0][1]) * ($line1[1][0] - $line2[0][0]);
        $v3 = ($line1[1][0] - $line1[0][0]) * ($line2[0][1] - $line1[0][1]) - ($line1[1][1] - $line1[0][1]) * ($line2[0][0] - $line1[0][0]);
        $v4 = ($line1[1][0] - $line1[0][0]) * ($line2[1][1] - $line1[0][1]) - ($line1[1][1] - $line1[0][1]) * ($line2[1][0] - $line1[0][0]);

        $is_cross = ($v1 * $v2 < 0) && ($v3 * $v4 < 0);

        if ( ! $is_cross && $cross_rule) {
            switch ($cross_rule) {
                case self::CROSS_ALL:
                    if (($line1[0][0] == $line2[0][0] && $line1[0][1] == $line2[0][1]) ||
                        ($line1[0][0] == $line2[1][0] && $line1[0][1] == $line2[1][1]) ||
                        ($line1[1][0] == $line2[0][0] && $line1[1][1] == $line2[0][1]) ||
                        ($line1[1][0] == $line2[1][0] && $line1[1][1] == $line2[1][1])
                    ) {
                        $is_cross = true;
                    }
                    if ($this->isContains([$line2[0][0], $line2[0][1]]) || $this->isContains([$line2[1][0], $line2[1][1]])) {
                        $is_cross = true;
                    }
                    break;

                case self::CROSS_VERTEX:
                    if (($line1[0][0] == $line2[0][0] && $line1[0][1] == $line2[0][1]) ||
                        ($line1[0][0] == $line2[1][0] && $line1[0][1] == $line2[1][1]) ||
                        ($line1[1][0] == $line2[0][0] && $line1[1][1] == $line2[0][1]) ||
                        ($line1[1][0] == $line2[1][0] && $line1[1][1] == $line2[1][1])
                    ) {
                        $is_cross = true;
                    }
                    break;

                case self::CROSS_BORDER:
                    if ($this->isContains([$line2[0][0], $line2[0][1]]) || $this->isContains([$line2[1][0], $line2[1][1]])) {
                        $is_cross = true;
                    }
                    break;
            }
        }

        return $is_cross;
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