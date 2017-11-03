<?php

namespace GeoTools;


/**
 * Class Coordinate
 */
class Coordinate {

    const ERROR_COORDINATES = 10;

    /**
     * @var float
     */
    protected $lat;


    /**
     * @var float
     */
    protected $lng;


    /**
     * @param float $lat -90.0 .. +90.0
     * @param float $lng -180.0 .. +180.0
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($lat, $lng) {

        if ( ! $this->isValidLatitude($lat)) {
            throw new \InvalidArgumentException("Latitude value must be numeric -90.0 .. +90.0 (given: {$lat})", self::ERROR_COORDINATES);
        }

        if ( ! $this->isValidLongitude($lng)) {
            throw new \InvalidArgumentException("Longitude value must be numeric -180.0 .. +180.0 (given: {$lng})", self::ERROR_COORDINATES);
        }

        $this->lat = doubleval($lat);
        $this->lng = doubleval($lng);
    }


    /**
     * @return float
     */
    public function getLat() {
        return $this->lat;
    }


    /**
     * @return float
     */
    public function getLng() {
        return $this->lng;
    }


    /**
     * @return array
     */
    public function getCoordinates() {
        return [$this->lat, $this->getLng()];
    }


    /**
     * Validates latitude
     * @param float $latitude
     * @return bool
     */
    public function isValidLatitude($latitude) {
        return $this->isNumericInBounds($latitude, -90.0, 90.0);
    }


    /**
     * Validates longitude
     * @param float $longitude
     * @return bool
     */
    public function isValidLongitude($longitude) {
        return $this->isNumericInBounds($longitude, -180.0, 180.0);
    }


    /**
     * Checks if the given value is (1) numeric, and (2) between lower
     * and upper bounds (including the bounds values).
     * @param float $value
     * @param float $lower
     * @param float $upper
     * @return bool
     */
    protected function isNumericInBounds($value, $lower, $upper) {

        if ( ! is_numeric($value)) {
            return false;
        }

        if ($value < $lower || $value > $upper) {
            return false;
        }

        return true;
    }
}