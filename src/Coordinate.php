<?php

namespace GeoTools;


/**
 * Class Coordinate
 */
class Coordinate {

    const ERROR_COORDINATES = 10;

    /**
     * @var string
     */
    protected $lat;


    /**
     * @var string
     */
    protected $lng;


    /**
     * @param float $lat -90.0 .. +90.0
     * @param float $lng -180.0 .. +180.0
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($lat, $lng) {

        if ( ! self::isValidLatitude($lat)) {
            throw new \InvalidArgumentException("Latitude value must be numeric -90.0 .. +90.0 (given: {$lat})", self::ERROR_COORDINATES);
        }

        if ( ! self::isValidLongitude($lng)) {
            throw new \InvalidArgumentException("Longitude value must be numeric -180.0 .. +180.0 (given: {$lng})", self::ERROR_COORDINATES);
        }

        $this->lat = (string)$lat;
        $this->lng = (string)$lng;
    }


    /**
     * @return string
     */
    public function getLat() {
        return $this->lat;
    }


    /**
     * @return string
     */
    public function getLng() {
        return $this->lng;
    }


    /**
     * @return array
     */
    public function getCoordinates() {
        return [$this->lat, $this->lng];
    }


    /**
     * Validates latitude
     * @param float|string $latitude
     * @return bool
     */
    public static function isValidLatitude($latitude) {
        return self::isNumericInBounds($latitude, -90.0, 90.0);
    }


    /**
     * Validates longitude
     * @param float|string $longitude
     * @return bool
     */
    public static function isValidLongitude($longitude) {
        return self::isNumericInBounds($longitude, -180.0, 180.0);
    }


    /**
     * Checks if the given value is (1) numeric, and (2) between lower
     * and upper bounds (including the bounds values).
     * @param float $value
     * @param float $lower
     * @param float $upper
     * @return bool
     */
    protected static function isNumericInBounds($value, $lower, $upper) {

        if ( ! is_numeric($value)) {
            return false;
        }

        if ( ! preg_match('~^\-?\d[\d\.]*$~', $value)) {
            return false;
        }

        if ($value < $lower || $value > $upper) {
            return false;
        }

        return true;
    }
}