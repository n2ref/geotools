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
     * Calculates the polygon perimeter.
     * @return float
     */
    public function getPerimeter() {

        $perimeter = 0.0;

        foreach ($this->getSegments() as $segment) {
            if ($segment instanceof Line) {
                $perimeter += $segment->getLength();
            }
        }

        return $perimeter;
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

        return $segments;
    }


    /**
     * @return array
     */
    public function getSegmentsInternal() {

        $segments = [];

        if (empty($this->polygons_internal)) {
            return $segments;
        }

        foreach ($this->polygons_internal as $polygon_internal) {
            if ($polygon_internal instanceof Polygon) {
                $coordinates   = $polygon_internal->getCoordinates()[0];
                $previousPoint = reset($coordinates);

                while ($point = next($coordinates)) {
                    $segments[]    = new Line($previousPoint, $point);
                    $previousPoint = $point;
                }
            }
        }

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
     * @return array
     */
    public function getBounds() {

        $top    = null;
        $bottom = null;
        $left   = null;
        $right  = null;

        foreach ($this->coordinates as $coordinate) {

            $top = $top !== null
                ? ($top < $coordinate->getLat() ? $coordinate->getLat() : $top)
                : $coordinate->getLat();

            $bottom = $bottom !== null
                ? ($bottom > $coordinate->getLat() ? $coordinate->getLat() : $bottom)
                : $coordinate->getLat();

            $left = $left !== null
                ? ($left > $coordinate->getLng() ? $coordinate->getLng() : $left)
                : $coordinate->getLng();

            $right = $right !== null
                ? ($right < $coordinate->getLng() ? $coordinate->getLng() : $right)
                : $coordinate->getLng();
        }

        return [[$bottom, $left], [$top, $right]];
    }


    /**
     * @return Coordinate
     */
    public function getCenter() {

        $coordinates = $this->coordinates;
        foreach ($coordinates as $k => $coordinate) {
            $coordinates[$k] = $coordinate->getCoordinates();
        }

        $sumY = 0;
        $sumX = 0;
        $sum  = 0;

        $count_coordinates = count($this->coordinates);

        for ($i = 0; $i < $count_coordinates - 1; $i++) {
            $partialSum = $coordinates[$i][1] * $coordinates[$i+1][0] - $coordinates[$i+1][1] * $coordinates[$i][0];
            $sum        = $sum + $partialSum;

            $sumX += ($coordinates[$i][1] + $coordinates[$i + 1][1]) * $partialSum;
            $sumY += ($coordinates[$i][0] + $coordinates[$i + 1][0]) * $partialSum;
        }

        $area = 0.5 * $sum;

        return new Coordinate($sumY / 6 / $area, $sumX / 6 / $area);
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getTriangles() {

        // TODO разделение самопересекающегося многоугольника
        $segments = $this->getSegments();
        foreach ($segments as $segment) {
            foreach ($segments as $segment2) {
                if ($segment != $segment2 &&
                    $segment instanceof \GeoTools\Line &&
                    $segment2 instanceof \GeoTools\Line
                ) {
                    if ($segment->isCross($segment2)) {
                        throw new \Exception('Self-intersecting polygon');
                    }
                }
            }
        }

        $segments_internal = $this->getSegmentsInternal();
        foreach ($segments_internal as $segment) {
            foreach ($segments_internal as $segment2) {
                if ($segment != $segment2 &&
                    $segment instanceof \GeoTools\Line &&
                    $segment2 instanceof \GeoTools\Line
                ) {
                    if ($segment->isCross($segment2)) {
                        throw new \Exception('Self-intersecting polygon');
                    }
                }
            }
        }

        // разделение многоугольников на треугольники
        $coordinates       = $this->coordinates;
        $segments          = $this->getSegments();
        $lines             = [];
        $lines_length      = [];
        $lines_contains    = [];
        $triangles         = [];

        if ( ! empty($this->polygons_internal)) {
            foreach ($this->polygons_internal as $polygon_internal) {
                if ($polygon_internal instanceof Polygon) {
                    $coordinates_internal = $polygon_internal->getCoordinates();
                    foreach ($coordinates_internal[0] as $coordinate_internal) {
                        $coordinates[] = $this->toCoordinate($coordinate_internal);
                    }
                    foreach ($polygon_internal->getSegments() as $segment_internal) {
                        $segments[] = $segment_internal;
                    }
                }
            }
        }

        if (count($coordinates) == 4) {
            return [$this];
        }


        foreach ($coordinates as $coordinate) {
            if ($coordinate instanceof Coordinate) {

                foreach ($coordinates as $coordinate2) {
                    if ($coordinate2 instanceof Coordinate) {

                        if ($coordinate->getCoordinates() == $coordinate2->getCoordinates()) {
                            continue;
                        }

                        $line = new Line($coordinate, $coordinate2);


                        foreach ($segments as $segment) {
                            $segment_coords = $segment->getCoordinates();

                            if (($coordinate->getCoordinates()  == $segment_coords[0] &&
                                 $coordinate2->getCoordinates() == $segment_coords[1]) ||
                                ($coordinate2->getCoordinates() == $segment_coords[0] &&
                                 $coordinate->getCoordinates()  == $segment_coords[1])
                            ) {
                                continue 2;
                            }
                        }

                        if ( ! $this->isContainsLine($line)) {
                            continue;
                        }

                        $length = $line->getLength();

                        if ($length > 0) {
                            $lines_length[] = $length;
                            $lines[]        = $line;
                        }

                    }
                }
            }
        }

        asort($lines_length);

        foreach ($lines_length as $line_key => $line_length) {
            $line = $lines[$line_key];
            if ($line instanceof Line) {

                if ( ! empty($lines_contains)) {
                    foreach ($lines_contains as $line_contains) {
                        if (($line_contains->getPoint1() == $line->getPoint1() && $line_contains->getPoint2() == $line->getPoint2()) ||
                            ($line_contains->getPoint1() == $line->getPoint2() && $line_contains->getPoint2() == $line->getPoint1()) ||
                            $line_contains->isCross($line)
                        ) {
                            continue 2;
                        }
                    }
                }

                $lines_contains[] = $line;
                $segments[]       = $line;
            }
        }


        foreach ($lines_contains as $line) {
            if ($line instanceof Line) {
                $points1 = [];
                $points2 = [];

                foreach ($segments as $segment) {
                    if ($segment instanceof Line) {

                        if ($line != $segment) {
                            if ($line->getPoint1()->getCoordinates() == $segment->getPoint1()->getCoordinates() ||
                                $line->getPoint1()->getCoordinates() == $segment->getPoint2()->getCoordinates()
                            ) {
                                $points1[] = $line->getPoint1()->getCoordinates() == $segment->getPoint1()->getCoordinates()
                                    ? $segment->getPoint2()->getCoordinates()
                                    : $segment->getPoint1()->getCoordinates();
                            }

                            if ($line->getPoint2()->getCoordinates() == $segment->getPoint1()->getCoordinates() ||
                                $line->getPoint2()->getCoordinates() == $segment->getPoint2()->getCoordinates()
                            ) {
                                $points2[] = $line->getPoint2()->getCoordinates() == $segment->getPoint1()->getCoordinates()
                                    ? $segment->getPoint2()->getCoordinates()
                                    : $segment->getPoint1()->getCoordinates();
                            }
                        }
                    }
                }


                foreach ($points1 as $point1) {
                    foreach ($points2 as $point2) {
                        if ($point1 == $point2) {
                            $triangle = new Polygon([
                                $line->getPoint1(),
                                $line->getPoint2(),
                                $point2,
                                $line->getPoint1(),
                            ]);

                            $isset_triangle       = false;
                            $triangle_coordinates = $triangle->getCoordinates();

                            foreach ($triangles as $triangle_contains) {
                                if ($triangle_contains instanceof Polygon) {
                                    $triangle_contains_coords = $triangle_contains->getCoordinates();

                                    if (($triangle_contains_coords[0][0] == $triangle_coordinates[0][0] ||
                                         $triangle_contains_coords[0][0] == $triangle_coordinates[0][1] ||
                                         $triangle_contains_coords[0][0] == $triangle_coordinates[0][2]) &&
                                        ($triangle_contains_coords[0][1] == $triangle_coordinates[0][0] ||
                                         $triangle_contains_coords[0][1] == $triangle_coordinates[0][1] ||
                                         $triangle_contains_coords[0][1] == $triangle_coordinates[0][2]) &&
                                        ($triangle_contains_coords[0][2] == $triangle_coordinates[0][0] ||
                                         $triangle_contains_coords[0][2] == $triangle_coordinates[0][1] ||
                                         $triangle_contains_coords[0][2] == $triangle_coordinates[0][2])
                                    ) {
                                        $isset_triangle = true;
                                        break;
                                    }
                                }
                            }

                            if ( ! $isset_triangle) {
                                $triangles[] = $triangle;
                            }
                        }
                    }
                }
            }
        }

        return $triangles;
    }


    /**
     * @param int   $number
     * @param array $excluding_points
     * @return array
     */
    public function getFillPoints($number, $excluding_points = []) {

        $points = [];

        if ($number <= 0) {
            return $points;
        }

        $triangles = $this->getTriangles();

        if ( ! empty($triangles)) {
            foreach ($triangles as $k => $triangle) {
                if ($triangle instanceof Polygon) {
                    $triangles[$k] = [
                        'polygon' => $triangle,
                        'area'    => $triangle->getArea(),
                    ];
                }
            }
        }
        array_multisort(array_column($triangles, 'area'), SORT_DESC, $triangles);

        do {
            reset($triangles);
            $triangle_key = key($triangles);
            $triangle     = $triangles[$triangle_key]['polygon'];

            if ($triangle instanceof Polygon) {
                $center = $triangle->getCenter();

                if ( ! in_array($center->getCoordinates(), $excluding_points)) {
                    if ($this->isContainsPoint($center)) {
                        $points[] = $center->getCoordinates();
                    }
                }

                unset($triangles[$triangle_key]);

                $split_triangles = $triangle->splitTriangle();

                foreach ($split_triangles as $split_triangle) {
                    if ($split_triangle instanceof Polygon) {
                        $triangles[] = [
                            'polygon' => $split_triangle,
                            'area'    => $split_triangle->getArea(),
                        ];
                    }
                }

                array_multisort(array_column($triangles, 'area'), SORT_DESC, $triangles);
            }

        } while (count($points) < $number);

        return $points;
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
                    $contains_internal = $this->isContainsPointInPolygon($coordinates_internal[0], $point, true);

                    if ($contains_internal == 'inside') {
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
     * @param Line|array $line
     * @return bool|string
     */
    public function isContainsLine($line) {

        $line = $this->toLine($line);


        if ( ! $this->isContainsPoint($line->getPoint1())) {
            return false;
        }

        if ( ! $this->isContainsPoint($line->getPoint2())) {
            return false;
        }

        if ( ! $this->isContainsPoint($line->getMiddle())) {
            return false;
        }


        $segments          = $this->getSegments();
        $segments_internal = [];

        if ( ! empty($this->polygons_internal)) {
            foreach ($this->polygons_internal as $polygon_internal) {
                if ($polygon_internal instanceof Polygon) {
                    foreach ($polygon_internal->getSegments() as $segment) {
                        $segments_internal[] = $segment;
                    }
                }
            }
        }

        foreach ($segments as $segment) {
            if ($segment instanceof Line) {
                if ($segment->isCross($line)) {
                    return false;
                }
            }
        }

        if ( ! empty($segments_internal)) {
            foreach ($segments_internal as $segment_internal) {
                if ($segment_internal instanceof Line) {
                    if ($segment_internal->isCross($line)) {
                        return false;
                    }
                }
            }
        }

        return true;
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
     * @return array
     */
    public function splitTriangle() {

        $coordinates = $this->coordinates;

        if ( ! empty($this->polygons_internal)) {
            foreach ($this->polygons_internal as $polygon_internal) {
                if ($polygon_internal instanceof Polygon) {
                    $coordinates_internal = $polygon_internal->getCoordinates();
                    foreach ($coordinates_internal[0] as $coordinate_internal) {
                        $coordinates[] = $this->toCoordinate($coordinate_internal);
                    }
                }
            }
        }

        if (count($coordinates) != 4) {
            throw new \InvalidArgumentException('Separation is possible only for triangles', self::ERROR_COORDINATES);
        }

        $segments        = $this->getSegments();
        $segments_length = [];

        foreach ($segments as $k => $segment) {
            if ($segment instanceof Line) {
                $segments_length[$k] = $segment->getLength();
            }
        }

        asort($segments_length);
        end($segments_length);

        $triangles   = [];
        $segment_max = $segments[key($segments_length)];

        if ($segment_max instanceof Line) {
            foreach ($coordinates as $coordinate) {
                if ($coordinate instanceof Coordinate) {
                    if ($coordinate->getCoordinates() != $segment_max->getPoint1()->getCoordinates() &&
                        $coordinate->getCoordinates() != $segment_max->getPoint2()->getCoordinates()
                    ) {

                        $middle_point = $segment_max->getMiddle();

                        $triangles[] = new Polygon([$middle_point, $segment_max->getPoint1(), $coordinate, $middle_point]);
                        $triangles[] = new Polygon([$middle_point, $segment_max->getPoint2(), $coordinate, $middle_point]);
                        break;
                    }
                }
            }
        }

        return $triangles;
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
     * @param bool       $exact_location
     * @return bool|string
     */
    private function isContainsPointInPolygon($coordinates, $point, $exact_location = false) {

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

        return $exact_location
            ? $result
            : $result !== 'outside';
    }


    /**
     * @param       $segments
     * @param       $segments_internal
     * @param       $segments_contains
     * @param array $points
     */
    private function getFillPointsDeep($segments, $segments_internal, $segments_contains, $points = []) {

        $this->getFillPointsDeep($segments, $segments_internal, $segments_contains, $points);
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