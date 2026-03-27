<?php

namespace Mzm\GeoJsonConverter\Tests;

use Mzm\GeoJsonConverter\GeoJsonConverter;
use PHPUnit\Framework\TestCase;

class GeoJsonConverterTest extends TestCase
{
    // =========================================================================
    // fromPoints
    // =========================================================================

    public function test_fromPoints_returns_valid_feature_collection(): void
    {
        $data = [
            ['nama' => 'KL Tower', 'lat' => 3.1528, 'lon' => 101.7038],
        ];

        $result = json_decode(GeoJsonConverter::fromPoints($data), true);

        $this->assertSame('FeatureCollection', $result['type']);
        $this->assertCount(1, $result['features']);
    }

    public function test_fromPoints_sets_correct_geometry(): void
    {
        $data = [['nama' => 'A', 'lat' => 3.15, 'lon' => 101.70]];

        $result = json_decode(GeoJsonConverter::fromPoints($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('Point', $geometry['type']);
        $this->assertSame([101.70, 3.15], $geometry['coordinates']);
    }

    public function test_fromPoints_removes_lat_lon_from_properties(): void
    {
        $data = [['nama' => 'A', 'lat' => 3.15, 'lon' => 101.70]];

        $result     = json_decode(GeoJsonConverter::fromPoints($data), true);
        $properties = $result['features'][0]['properties'];

        $this->assertArrayHasKey('nama', $properties);
        $this->assertArrayNotHasKey('lat', $properties);
        $this->assertArrayNotHasKey('lon', $properties);
    }

    public function test_fromPoints_geometry_null_if_no_coordinates(): void
    {
        $data = [['nama' => 'Tiada koordinat']];

        $result   = json_decode(GeoJsonConverter::fromPoints($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertNull($geometry);
    }

    public function test_fromPoints_supports_custom_key_names(): void
    {
        $data = [['nama' => 'A', 'latitude' => 3.15, 'longitude' => 101.70]];

        $result   = json_decode(GeoJsonConverter::fromPoints($data, 'latitude', 'longitude'), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame([101.70, 3.15], $geometry['coordinates']);
    }

    // =========================================================================
    // fromLineStrings
    // =========================================================================

    public function test_fromLineStrings_returns_correct_geometry_type(): void
    {
        $data = [
            ['nama' => 'Jalan A', 'coordinates' => [[101.70, 3.15], [101.71, 3.16]]],
        ];

        $result   = json_decode(GeoJsonConverter::fromLineStrings($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('LineString', $geometry['type']);
        $this->assertCount(2, $geometry['coordinates']);
    }

    public function test_fromLineStrings_removes_coordinates_from_properties(): void
    {
        $data = [['nama' => 'A', 'coordinates' => [[101.70, 3.15], [101.71, 3.16]]]];

        $result     = json_decode(GeoJsonConverter::fromLineStrings($data), true);
        $properties = $result['features'][0]['properties'];

        $this->assertArrayNotHasKey('coordinates', $properties);
        $this->assertArrayHasKey('nama', $properties);
    }

    // =========================================================================
    // fromPolygons
    // =========================================================================

    public function test_fromPolygons_returns_correct_geometry_type(): void
    {
        $data = [
            [
                'nama'        => 'Kawasan B',
                'coordinates' => [
                    [[101.70, 3.15], [101.71, 3.15], [101.71, 3.16], [101.70, 3.15]],
                ],
            ],
        ];

        $result   = json_decode(GeoJsonConverter::fromPolygons($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('Polygon', $geometry['type']);
        $this->assertCount(1, $geometry['coordinates']); // 1 ring
    }

    // =========================================================================
    // fromWkt
    // =========================================================================

    public function test_fromWkt_parses_point(): void
    {
        $data = [['nama' => 'KL Tower', 'geom' => 'POINT(101.7038 3.1528)']];

        $result   = json_decode(GeoJsonConverter::fromWkt($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('Point', $geometry['type']);
        $this->assertSame([101.7038, 3.1528], $geometry['coordinates']);
    }

    public function test_fromWkt_parses_linestring(): void
    {
        $data = [['nama' => 'A', 'geom' => 'LINESTRING(101.70 3.15, 101.71 3.16)']];

        $result   = json_decode(GeoJsonConverter::fromWkt($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('LineString', $geometry['type']);
        $this->assertCount(2, $geometry['coordinates']);
    }

    public function test_fromWkt_parses_polygon(): void
    {
        $data = [['nama' => 'A', 'geom' => 'POLYGON((101.70 3.15, 101.71 3.15, 101.71 3.16, 101.70 3.15))']];

        $result   = json_decode(GeoJsonConverter::fromWkt($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('Polygon', $geometry['type']);
    }

    public function test_fromWkt_parses_multipoint(): void
    {
        $data = [['nama' => 'A', 'geom' => 'MULTIPOINT(101.70 3.15, 101.71 3.16)']];

        $result   = json_decode(GeoJsonConverter::fromWkt($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('MultiPoint', $geometry['type']);
        $this->assertCount(2, $geometry['coordinates']);
    }

    public function test_fromWkt_returns_null_geometry_for_unknown_wkt(): void
    {
        $data = [['nama' => 'A', 'geom' => 'INVALID WKT']];

        $result   = json_decode(GeoJsonConverter::fromWkt($data), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertNull($geometry);
    }

    public function test_fromWkt_removes_geom_from_properties(): void
    {
        $data = [['nama' => 'A', 'geom' => 'POINT(101.70 3.15)']];

        $result     = json_decode(GeoJsonConverter::fromWkt($data), true);
        $properties = $result['features'][0]['properties'];

        $this->assertArrayNotHasKey('geom', $properties);
        $this->assertArrayHasKey('nama', $properties);
    }

    public function test_fromWkt_supports_custom_geom_key(): void
    {
        $data = [['nama' => 'A', 'geometry' => 'POINT(101.70 3.15)']];

        $result   = json_decode(GeoJsonConverter::fromWkt($data, 'geometry'), true);
        $geometry = $result['features'][0]['geometry'];

        $this->assertSame('Point', $geometry['type']);
    }

    // =========================================================================
    // fromMixed
    // =========================================================================

    public function test_fromMixed_handles_multiple_geometry_types(): void
    {
        $data = [
            ['geometry_type' => 'Point',      'lat' => 3.15, 'lon' => 101.70, 'nama' => 'A'],
            ['geometry_type' => 'LineString', 'coordinates' => [[101.70, 3.15], [101.71, 3.16]], 'nama' => 'B'],
            ['geometry_type' => 'Polygon',    'coordinates' => [[[101.70, 3.15], [101.71, 3.15], [101.70, 3.15]]], 'nama' => 'C'],
        ];

        $result   = json_decode(GeoJsonConverter::fromMixed($data), true);
        $features = $result['features'];

        $this->assertCount(3, $features);
        $this->assertSame('Point',      $features[0]['geometry']['type']);
        $this->assertSame('LineString', $features[1]['geometry']['type']);
        $this->assertSame('Polygon',    $features[2]['geometry']['type']);
    }

    // =========================================================================
    // fromArray (alias)
    // =========================================================================

    public function test_fromArray_is_alias_for_fromPoints(): void
    {
        $data = [['nama' => 'A', 'lat' => 3.15, 'lon' => 101.70]];

        $this->assertSame(
            GeoJsonConverter::fromPoints($data),
            GeoJsonConverter::fromArray($data)
        );
    }

    // =========================================================================
    // Output format
    // =========================================================================

    public function test_output_is_valid_json(): void
    {
        $data   = [['nama' => 'A', 'lat' => 3.15, 'lon' => 101.70]];
        $result = GeoJsonConverter::fromPoints($data);

        $this->assertNotNull(json_decode($result));
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function test_output_has_feature_collection_type(): void
    {
        $data   = [['lat' => 3.15, 'lon' => 101.70]];
        $result = json_decode(GeoJsonConverter::fromPoints($data), true);

        $this->assertSame('FeatureCollection', $result['type']);
        $this->assertArrayHasKey('features', $result);
    }

    public function test_each_feature_has_type_geometry_and_properties(): void
    {
        $data   = [['nama' => 'A', 'lat' => 3.15, 'lon' => 101.70]];
        $result = json_decode(GeoJsonConverter::fromPoints($data), true);
        $feature = $result['features'][0];

        $this->assertSame('Feature', $feature['type']);
        $this->assertArrayHasKey('geometry', $feature);
        $this->assertArrayHasKey('properties', $feature);
    }

    public function test_handles_empty_array(): void
    {
        $result = json_decode(GeoJsonConverter::fromPoints([]), true);

        $this->assertSame('FeatureCollection', $result['type']);
        $this->assertCount(0, $result['features']);
    }
}
