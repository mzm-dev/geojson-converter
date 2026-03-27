<?php

namespace Mzm\GeoJsonConverter;

use Illuminate\Http\Response;

class GeoJsonConverter
{
    /** Content-Type standard untuk GeoJSON (RFC 7946). */
    private const CONTENT_TYPE = 'application/geo+json';

    // =========================================================================
    // Convert — pulangkan string GeoJSON
    // =========================================================================

    /**
     * Convert array of points using lat/lon keys.
     *
     * Input example:
     *   ['name' => 'KL Tower', 'lat' => 3.1528, 'lon' => 101.7038]
     */
    public static function fromPoints(array $data, string $latKey = 'lat', string $lonKey = 'lon'): string
    {
        $features = [];

        foreach ($data as $item) {
            $geometry = null;
            if (isset($item[$latKey]) && isset($item[$lonKey])) {
                $geometry = [
                    'type' => 'Point',
                    'coordinates' => [(float) $item[$lonKey], (float) $item[$latKey]],
                ];
            }

            $properties = $item;
            unset($properties[$latKey], $properties[$lonKey]);

            $features[] = self::makeFeature($geometry, $properties);
        }

        return self::encode($features);
    }

    /**
     * Convert array of LineString data.
     *
     * Input example:
     *   ['name' => 'Jalan A', 'coordinates' => [[101.70, 3.15], [101.71, 3.16]]]
     */
    public static function fromLineStrings(array $data, string $coordinatesKey = 'coordinates'): string
    {
        return self::fromGeometry($data, 'LineString', $coordinatesKey);
    }

    /**
     * Convert array of Polygon data.
     *
     * Input example:
     *   ['name' => 'Kawasan B', 'coordinates' => [[[101.70, 3.15], [101.71, 3.16], [101.70, 3.15]]]]
     * Note: polygon ring must be closed (first == last coordinate).
     */
    public static function fromPolygons(array $data, string $coordinatesKey = 'coordinates'): string
    {
        return self::fromGeometry($data, 'Polygon', $coordinatesKey);
    }

    /**
     * Convert a mixed dataset where each item declares its own geometry type.
     * Supported types: Point, LineString, Polygon, MultiPoint, MultiLineString, MultiPolygon.
     *
     * Input example:
     *   ['geometry_type' => 'Point',      'lat' => 3.15, 'lon' => 101.70, 'name' => 'A']
     *   ['geometry_type' => 'LineString', 'coordinates' => [[101.70, 3.15], [101.71, 3.16]], 'name' => 'B']
     *   ['geometry_type' => 'Polygon',    'coordinates' => [[[101.70, 3.15], ...]], 'name' => 'C']
     */
    public static function fromMixed(
        array $data,
        string $typeKey = 'geometry_type',
        string $coordinatesKey = 'coordinates',
        string $latKey = 'lat',
        string $lonKey = 'lon'
    ): string {
        $features = [];

        foreach ($data as $item) {
            $type = $item[$typeKey] ?? 'Point';
            $geometry = null;
            $properties = $item;

            if ($type === 'Point') {
                if (isset($item[$latKey]) && isset($item[$lonKey])) {
                    $geometry = [
                        'type' => 'Point',
                        'coordinates' => [(float) $item[$lonKey], (float) $item[$latKey]],
                    ];
                }
                unset($properties[$typeKey], $properties[$latKey], $properties[$lonKey]);
            } elseif (in_array($type, ['LineString', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon'], true)) {
                if (isset($item[$coordinatesKey]) && is_array($item[$coordinatesKey])) {
                    $geometry = [
                        'type' => $type,
                        'coordinates' => $item[$coordinatesKey],
                    ];
                }
                unset($properties[$typeKey], $properties[$coordinatesKey]);
            } else {
                unset($properties[$typeKey]);
            }

            $features[] = self::makeFeature($geometry, $properties);
        }

        return self::encode($features);
    }

    /**
     * Convert array containing WKT geometry strings (from ST_AsText(geom)).
     * Supports: Point, LineString, Polygon, MultiPoint, MultiLineString, MultiPolygon.
     *
     * Prerequisite SQL:
     *   PostGIS : SELECT ST_AsText(geom) AS geom, nama FROM locations
     *   MySQL   : SELECT ST_AsText(geom) AS geom, nama FROM locations
     *
     * Input example:
     *   ['nama' => 'KL Tower', 'geom' => 'POINT(101.7038 3.1528)']
     */
    public static function fromWkt(array $data, string $geomKey = 'geom'): string
    {
        $features = [];

        foreach ($data as $item) {
            $geometry = null;
            if (isset($item[$geomKey]) && is_string($item[$geomKey])) {
                $geometry = self::parseWkt($item[$geomKey]);
            }

            $properties = $item;
            unset($properties[$geomKey]);

            $features[] = self::makeFeature($geometry, $properties);
        }

        return self::encode($features);
    }

    /**
     * Backward-compatible alias for fromPoints().
     */
    public static function fromArray(array $data, string $latKey = 'lat', string $lonKey = 'lon'): string
    {
        return self::fromPoints($data, $latKey, $lonKey);
    }

    // =========================================================================
    // Response — convert dan terus pulangkan Laravel HTTP Response
    // =========================================================================

    /**
     * Convert points dan pulangkan sebagai HTTP GeoJSON Response.
     */
    public static function pointsResponse(array $data, string $latKey = 'lat', string $lonKey = 'lon', int $status = 200): Response
    {
        return self::toResponse(self::fromPoints($data, $latKey, $lonKey), $status);
    }

    /**
     * Convert LineStrings dan pulangkan sebagai HTTP GeoJSON Response.
     */
    public static function lineStringsResponse(array $data, string $coordinatesKey = 'coordinates', int $status = 200): Response
    {
        return self::toResponse(self::fromLineStrings($data, $coordinatesKey), $status);
    }

    /**
     * Convert Polygons dan pulangkan sebagai HTTP GeoJSON Response.
     */
    public static function polygonsResponse(array $data, string $coordinatesKey = 'coordinates', int $status = 200): Response
    {
        return self::toResponse(self::fromPolygons($data, $coordinatesKey), $status);
    }

    /**
     * Convert WKT data dan pulangkan sebagai HTTP GeoJSON Response.
     */
    public static function wktResponse(array $data, string $geomKey = 'geom', int $status = 200): Response
    {
        return self::toResponse(self::fromWkt($data, $geomKey), $status);
    }

    /**
     * Convert mixed geometry data dan pulangkan sebagai HTTP GeoJSON Response.
     */
    public static function mixedResponse(
        array $data,
        string $typeKey = 'geometry_type',
        string $coordinatesKey = 'coordinates',
        string $latKey = 'lat',
        string $lonKey = 'lon',
        int $status = 200
    ): Response {
        return self::toResponse(self::fromMixed($data, $typeKey, $coordinatesKey, $latKey, $lonKey), $status);
    }

    /**
     * Wrap mana-mana GeoJSON string ke dalam HTTP Response dengan header yang betul.
     * Boleh digunakan secara bebas untuk GeoJSON yang sudah sedia ada.
     *
     * Header yang ditetapkan:
     *   Content-Type  : application/geo+json
     *   Cache-Control : no-store (elak cache data geospatial yang mungkin berubah)
     */
    public static function toResponse(string $geojson, int $status = 200, array $headers = []): Response
    {
        $defaultHeaders = [
            'Content-Type'  => self::CONTENT_TYPE,
            'Cache-Control' => 'no-store',
        ];

        return new Response($geojson, $status, array_merge($defaultHeaders, $headers));
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private static function fromGeometry(array $data, string $type, string $coordinatesKey): string
    {
        $features = [];

        foreach ($data as $item) {
            $geometry = null;
            if (isset($item[$coordinatesKey]) && is_array($item[$coordinatesKey])) {
                $geometry = [
                    'type' => $type,
                    'coordinates' => $item[$coordinatesKey],
                ];
            }

            $properties = $item;
            unset($properties[$coordinatesKey]);

            $features[] = self::makeFeature($geometry, $properties);
        }

        return self::encode($features);
    }

    private static function parseWkt(string $wkt): ?array
    {
        $wkt = trim($wkt);
        $upper = strtoupper($wkt);

        if (preg_match('/^POINT\s*\(\s*([\d.\-]+)\s+([\d.\-]+)\s*\)$/i', $wkt, $m)) {
            return ['type' => 'Point', 'coordinates' => [(float) $m[1], (float) $m[2]]];
        }

        if (preg_match('/^LINESTRING\s*\((.+)\)$/is', $wkt, $m)) {
            return ['type' => 'LineString', 'coordinates' => self::wktCoordPairs($m[1])];
        }

        if (preg_match('/^POLYGON\s*\((.+)\)$/is', $wkt, $m)) {
            return ['type' => 'Polygon', 'coordinates' => self::wktRingList($m[1])];
        }

        if (preg_match('/^MULTIPOINT\s*\((.+)\)$/is', $wkt, $m)) {
            // Support both: MULTIPOINT(1 2, 3 4) and MULTIPOINT((1 2),(3 4))
            $inner = preg_replace('/[()]/', '', $m[1]);
            return ['type' => 'MultiPoint', 'coordinates' => self::wktCoordPairs($inner)];
        }

        if (preg_match('/^MULTILINESTRING\s*\((.+)\)$/is', $wkt, $m)) {
            return ['type' => 'MultiLineString', 'coordinates' => self::wktRingList($m[1])];
        }

        if (preg_match('/^MULTIPOLYGON\s*\((.+)\)$/is', $wkt, $m)) {
            return ['type' => 'MultiPolygon', 'coordinates' => self::wktMultiPolygon($m[1])];
        }

        return null;
    }

    /** Parse "lon lat, lon lat, ..." into [[lon,lat], ...] */
    private static function wktCoordPairs(string $str): array
    {
        $coords = [];
        foreach (explode(',', trim($str)) as $pair) {
            $parts = preg_split('/\s+/', trim($pair));
            if (count($parts) >= 2) {
                $coords[] = [(float) $parts[0], (float) $parts[1]];
            }
        }
        return $coords;
    }

    /** Parse "(ring1), (ring2)" into [[[lon,lat],...], ...] */
    private static function wktRingList(string $str): array
    {
        preg_match_all('/\(([^()]+)\)/', $str, $matches);
        return array_map([self::class, 'wktCoordPairs'], $matches[1]);
    }

    /** Parse MULTIPOLYGON inner "((ring)), ((ring))" */
    private static function wktMultiPolygon(string $str): array
    {
        preg_match_all('/\((\([^)]+\)(?:\s*,\s*\([^)]+\))*)\)/', $str, $matches);
        return array_map([self::class, 'wktRingList'], $matches[1]);
    }

    private static function makeFeature(?array $geometry, array $properties): array
    {
        return [
            'type' => 'Feature',
            'geometry' => $geometry,
            'properties' => $properties,
        ];
    }

    private static function encode(array $features): string
    {
        return json_encode([
            'type' => 'FeatureCollection',
            'features' => $features,
        ], JSON_PRETTY_PRINT);
    }
}