<?php
namespace GeoTools;

require_once 'Line.php';
require_once 'Coordinate.php';


/**
 * Class Polygon
 */
class Polygon {

    const ERROR_COORDINATES      = 30;
    const ERROR_ARGUMENT_TYPE    = 31;
    const ERROR_INTERNAL_POLYGON = 32;

    private $coordinates       = [];
    private $polygons_internal = [];


    /**
     * Polygon constructor.
     * @param array $coordinates
     * @param array $polygons_internal
     * @throws \InvalidArgumentException
     */
    public function __construct($coordinates, $polygons_internal = []) {

        if ( ! empty($coordinates)) {
            if (count($coordinates) < 4) {
                throw new \InvalidArgumentException('Error coordinates', self::ERROR_COORDINATES);
            }
            if (reset($coordinates) != end($coordinates)) {
                throw new \InvalidArgumentException('Error coordinates', self::ERROR_COORDINATES);
            }

            foreach ($coordinates as $coordinate) {
                $this->coordinates[] = $this->toCoordinate($coordinate);
            }
        } else {
            throw new \InvalidArgumentException('Error coordinates', self::ERROR_COORDINATES);
        }


        if (is_array($polygons_internal) && ! empty($polygons_internal)) {
            foreach ($polygons_internal as $polygon_internal) {
                $polygon_internal = $this->toPolygon($polygon_internal);
                if ($this->isContainsPolygon($polygon_internal)) {
                    $this->polygons_internal[] = $polygon_internal;
                } else {
                    throw new \InvalidArgumentException('Error internal polygon', self::ERROR_INTERNAL_POLYGON);
                }
            }
        }
    }


    /**
     * @param int $round_precision
     * @return float|int
     */
    public function getArea($round_precision = 0) {

        $t           = 0;
        $coordinates = $this->getCoordinates();


        if ($coordinates && count($coordinates) > 0) {
            $t += abs($this->n($coordinates[0]));

            for ($r = 1; $r < count($coordinates); $r++) {
                $t -= abs($this->n($coordinates[$r]));
            }
        }

        if ($round_precision > 0) {
            $t = round($t, $round_precision);
        }

        return $t;
    }


    /**
     * @return array
     */
    public function getSegments() {

        $segments = [];

        if (count($this->coordinates) <= 1) {
            return $segments;
        }

        $previousPoint = reset($this->coordinates);

        while ($point = next($this->coordinates)) {
            $segments[]    = new Line($previousPoint, $point);
            $previousPoint = $point;
        }

        // to close the polygon we have to add the final segment between
        // the last point and the first point
        $segments[] = new Line(end($this->coordinates), reset($this->coordinates));

        return $segments;
    }


    /**
     * @return array
     */
    public function getCoordinates() {

        $coordinates         = [];
        $coordinates_polygon = [];

        foreach ($this->coordinates as $coordinate) {
            if ($coordinate instanceof Coordinate) {
                $coordinates_polygon[] = $coordinate->getCoordinates();
            }
        }
        $coordinates[] = $coordinates_polygon;


        foreach ($this->polygons_internal as $polygons_internal) {
            if ($polygons_internal instanceof Polygon) {
                $coordinates_internal = $polygons_internal->getCoordinates();
                $coordinates[] = $coordinates_internal[0];
            }
        }

        return $coordinates;
    }


    /**
     * @param Coordinate|array $point
     * @return bool|string
     */
    public function isContainsPoint($point) {

        $point       = $this->toCoordinate($point);
        $coordinates = $this->getCoordinates();
        $is_contains = $this->isContainsPointInPolygon($coordinates[0], $point);

        if ($is_contains) {
            if ( ! empty($this->polygons_internal)) {
                foreach ($this->polygons_internal as $polygon_internal) {
                    $coordinates_internal = $polygon_internal->getCoordinates();
                    $is_contains_internal = $this->isContainsPointInPolygon($coordinates_internal[0], $point);

                    if ($is_contains_internal) {
                        return false;
                    }
                }

                return true;

            } else {
                return true;
            }

        } else {
            return false;
        }
    }


    /**
     * @param Polygon|array $polygon
     * @return bool
     */
    public function isContainsPolygon($polygon) {

        $polygon     = $this->toPolygon($polygon);
        $is_contains = $this->isContainsPolygonInPolygon($this, $polygon);

        if ($is_contains) {
            if ( ! empty($this->polygons_internal)) {
                foreach ($this->polygons_internal as $polygon_internal) {

                    $coordinates = $polygon->getCoordinates();
                    foreach ($coordinates[0] as $coordinate) {
                        $is_contains_internal = $polygon_internal->isContainsPoint($coordinate);

                        if ($is_contains_internal) {
                            return false;
                        }
                    }

                    $coordinates_internal = $polygon_internal->getCoordinates();
                    foreach ($coordinates_internal[0] as $coordinate) {
                        $is_contains_internal = $polygon->isContainsPoint($coordinate);

                        if ($is_contains_internal) {
                            return false;
                        }
                    }


                    $segments_initial = $polygon_internal->getSegments();
                    $segments         = $polygon->getSegments();

                    foreach ($segments_initial as $segment_initial) {
                        if ($segment_initial instanceof Line) {
                            foreach ($segments as $segment) {
                                if ($segment instanceof Line) {
                                    if ($segment_initial->isCross($segment)) {
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }

                return true;

            } else {
                return true;
            }

        } else {
            return false;
        }
    }


    /**
     * @param Polygon $polygon_initial
     * @param Polygon $polygon
     * @return bool
     */
    private function isContainsPolygonInPolygon($polygon_initial, $polygon) {

        $coordinates = $polygon->getCoordinates();
        foreach ($coordinates[0] as $coordinate) {
            $is_contains = $polygon_initial->isContainsPoint($coordinate);

            if ( ! $is_contains) {
                return false;
            }
        }


        $segments_initial = $polygon_initial->getSegments();
        $segments         = $polygon->getSegments();

        foreach ($segments_initial as $segment_initial) {
            if ($segment_initial instanceof Line) {
                foreach ($segments as $segment) {
                    if ($segment instanceof Line) {
                        if ($segment_initial->isCross($segment)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }


    /**
     * @param array      $coordinates
     * @param Coordinate $point
     * @return bool
     */
    private function isContainsPointInPolygon($coordinates, $point) {

        $result = '';

        // Check if the point sits exactly on a vertex
        if ( ! empty($coordinates)) {
            foreach ($coordinates as $coordinate) {
                if ($point->getCoordinates() == $coordinate) {
                    $result = "vertex";
                    break;
                }
            }
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $point_lat     = $point->getLat();
        $point_lng     = $point->getLng();

        for ($i = 1; $i < count($coordinates); $i++) {
            $vertex1 = $coordinates[$i - 1];
            $vertex2 = $coordinates[$i];

            // Check if point is on an horizontal polygon boundary
            if ($vertex1[1] == $vertex2[1] &&
                $vertex1[1] == $point_lng &&
                $point_lat > min($vertex1[0], $vertex2[0]) &&
                $point_lat < max($vertex1[0], $vertex2[0])
            ) {
                $result = "boundary";
                break;
            }

            if ($point_lng > min($vertex1[1], $vertex2[1]) &&
                $point_lng <= max($vertex1[1], $vertex2[1]) &&
                $point_lat <= max($vertex1[0], $vertex2[0]) &&
                $vertex1[1] != $vertex2[1]
            ) {
                $xinters = ($point_lng - $vertex1[1]) * ($vertex2[0] - $vertex1[0]) / ($vertex2[1] - $vertex1[1]) + $vertex1[0];

                // Check if point is on the polygon boundary (other than horizontal)
                if ($xinters == $point_lat) {
                    $result = "boundary";
                    break;
                }
                if ($vertex1[0] == $vertex2[0] || $point_lat <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if (empty($result)) {
            if ($intersections % 2 != 0) {
                $result = "inside";
            } else {
                $result = "outside";
            }
        }

        return $result !== 'outside';
    }


    /**
     * @param $e
     * @return float|int
     */
    private function n($e) {

        $i = 6378137;
        $u = 0;
        $g = count($e);
        $l = 1;
        $d = 0;

        if ($g > 2) {
            for ($s = 0; $s < $g; $s++) {
                if ($s === $g - 2) {
                    $o = $g - 2;
                    $n = $g - 1;
                    $c = 0;

                } else {
                    if ($s === $g - 1) {
                        $o = $g - 1;
                        $n = 0;
                        $c = 1;

                    } else {
                        $o = $s;
                        $n = $s + 1;
                        $c = $s + 2;
                    }
                }

                $t = $e[$o];
                $r = $e[$n];
                $u += ($this->toRadian($e[$c][$l]) - $this->toRadian($t[$l])) * sin($this->toRadian($r[$d]));
            }

            $u = $u * $i * $i / 2;
        }

        return $u;
    }


    /**
     * @param $number
     * @return float|int
     */
    private function toRadian($number) {
        return $number * M_PI / 180;
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
            is_numeric($point[0]) &&
            isset($point[1]) &&
            is_numeric($point[1])
        ) {
            return new Coordinate($point[0], $point[1]);

        } else {
            throw new \InvalidArgumentException('Error argument type', self::ERROR_ARGUMENT_TYPE);
        }
    }


    /**
     * @param array|Polygon $polygon
     * @return Polygon
     * @throws \InvalidArgumentException
     */
    private function toPolygon($polygon) {

        if ($polygon instanceof Polygon) {
            return $polygon;

        } elseif (is_array($polygon))  {
            $coordinates = [];
            foreach ($polygon as $coordinate) {
                $coordinates[] = $this->toCoordinate($coordinate);
            }
            return new Polygon($coordinates);

        } else {
            throw new \InvalidArgumentException('Error argument type', self::ERROR_ARGUMENT_TYPE);
        }
    }
}