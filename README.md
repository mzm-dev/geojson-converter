# mzm/geojson-converter

Tukar array PHP/Laravel ke format **GeoJSON** (RFC 7946) dengan mudah — menyokong Point, LineString, Polygon dan jenis geometry yang lain.

---

## Pemasangan

```bash
composer require mzm/geojson-converter
```

Package ini menyokong Laravel auto-discovery. `GeoJsonServiceProvider` akan didaftarkan secara automatik.

---

## Penggunaan

### 1. Point — `fromPoints()`

Untuk data yang mengandungi koordinat lat/lon secara berasingan.

```php
use Mzm\GeoJsonConverter\GeoJsonConverter;

$data = [
    ['nama' => 'KL Tower', 'lat' => 3.1528,  'lon' => 101.7038],
    ['nama' => 'KLCC',     'lat' => 3.1579,  'lon' => 101.7119],
];

$geojson = GeoJsonConverter::fromPoints($data);
```

**Parameter:**

| Parameter | Default | Keterangan |
|---|---|---|
| `$data` | — | Array data |
| `$latKey` | `'lat'` | Nama kunci latitud |
| `$lonKey` | `'lon'` | Nama kunci longitud |

---

### 2. LineString — `fromLineStrings()`

Untuk data laluan atau garisan. Koordinat dalam format `[lon, lat]` mengikut standard GeoJSON.

```php
$data = [
    [
        'nama'        => 'Laluan A',
        'coordinates' => [[101.70, 3.15], [101.71, 3.16], [101.72, 3.17]],
    ],
];

$geojson = GeoJsonConverter::fromLineStrings($data);
```

**Parameter:**

| Parameter | Default | Keterangan |
|---|---|---|
| `$data` | — | Array data |
| `$coordinatesKey` | `'coordinates'` | Nama kunci koordinat |

---

### 3. Polygon — `fromPolygons()`

Untuk data kawasan atau sempadan. Ring pertama mestilah **ditutup** (koordinat pertama == koordinat terakhir).

```php
$data = [
    [
        'nama'        => 'Kawasan B',
        'coordinates' => [
            [
                [101.70, 3.15],
                [101.71, 3.15],
                [101.71, 3.16],
                [101.70, 3.16],
                [101.70, 3.15], // tutup semula
            ]
        ],
    ],
];

$geojson = GeoJsonConverter::fromPolygons($data);
```

**Parameter:**

| Parameter | Default | Keterangan |
|---|---|---|
| `$data` | — | Array data |
| `$coordinatesKey` | `'coordinates'` | Nama kunci koordinat |

---

### 4. Data dari Kolum `geom` (PostGIS / MySQL Spatial) — `fromWkt()`

#### Pra-Syarat

Kolum `geom` dalam pangkalan data menyimpan geometri dalam format **WKB (Well-Known Binary)** — format binari yang tidak boleh terus ditukar. Perlu tukar kepada **WKT (Well-Known Text)** dalam query SQL terlebih dahulu menggunakan fungsi spatial:

| Pangkalan Data | Fungsi SQL |
|---|---|
| PostgreSQL/PostGIS | `ST_AsText(geom)` |
| MySQL 5.7+ | `ST_AsText(geom)` |
| MariaDB | `ST_AsText(geom)` |

**Contoh Query (Laravel Eloquent):**

```php
// PostGIS
$data = DB::table('lokasi')
    ->selectRaw('ST_AsText(geom) AS geom, nama, kategori')
    ->get()
    ->toArray();

// MySQL Spatial
$data = DB::table('lokasi')
    ->selectRaw('ST_AsText(geom) AS geom, nama, kategori')
    ->get()
    ->toArray();
```

> **Alternatif lebih mudah:** Guna `ST_AsGeoJSON(geom)` dalam SQL untuk dapatkan GeoJSON terus dari pangkalan data tanpa package ini.

#### Penggunaan `fromWkt()`

Menyokong semua jenis geometry: `Point`, `LineString`, `Polygon`, `MultiPoint`, `MultiLineString`, `MultiPolygon`.

```php
use Mzm\GeoJsonConverter\GeoJsonConverter;

// Data selepas ST_AsText(geom)
$data = [
    ['geom' => 'POINT(101.7038 3.1528)',                        'nama' => 'KL Tower'],
    ['geom' => 'LINESTRING(101.70 3.15, 101.71 3.16)',          'nama' => 'Jalan A'],
    ['geom' => 'POLYGON((101.70 3.15, 101.71 3.15, 101.70 3.15))', 'nama' => 'Kawasan B'],
];

$geojson = GeoJsonConverter::fromWkt($data);
```

**Parameter:**

| Parameter | Default | Keterangan |
|---|---|---|
| `$data` | — | Array data dengan WKT string |
| `$geomKey` | `'geom'` | Nama kunci kolum WKT |

**Format WKT yang disokong:**

| Jenis | Contoh WKT |
|---|---|
| Point | `POINT(101.70 3.15)` |
| LineString | `LINESTRING(101.70 3.15, 101.71 3.16)` |
| Polygon | `POLYGON((101.70 3.15, 101.71 3.16, 101.70 3.15))` |
| MultiPoint | `MULTIPOINT(101.70 3.15, 101.71 3.16)` |
| MultiLineString | `MULTILINESTRING((101.70 3.15, 101.71 3.16),(101.72 3.17, 101.73 3.18))` |
| MultiPolygon | `MULTIPOLYGON(((101.70 3.15, 101.71 3.15, 101.70 3.15)))` |

---

### 5. Pelbagai Jenis Geometry — `fromMixed()`

Untuk dataset yang mengandungi pelbagai jenis geometry sekaligus. Setiap item perlu menyatakan jenis geometrynya.

Jenis yang disokong: `Point`, `LineString`, `Polygon`, `MultiPoint`, `MultiLineString`, `MultiPolygon`.

```php
$data = [
    [
        'geometry_type' => 'Point',
        'lat'           => 3.1528,
        'lon'           => 101.7038,
        'nama'          => 'KL Tower',
    ],
    [
        'geometry_type' => 'LineString',
        'coordinates'   => [[101.70, 3.15], [101.71, 3.16]],
        'nama'          => 'Jalan A',
    ],
    [
        'geometry_type' => 'Polygon',
        'coordinates'   => [
            [[101.70, 3.15], [101.71, 3.15], [101.71, 3.16], [101.70, 3.15]]
        ],
        'nama'          => 'Kawasan B',
    ],
];

$geojson = GeoJsonConverter::fromMixed($data);
```

**Parameter:**

| Parameter | Default | Keterangan |
|---|---|---|
| `$data` | — | Array data |
| `$typeKey` | `'geometry_type'` | Nama kunci jenis geometry |
| `$coordinatesKey` | `'coordinates'` | Nama kunci koordinat |
| `$latKey` | `'lat'` | Nama kunci latitud (untuk Point) |
| `$lonKey` | `'lon'` | Nama kunci longitud (untuk Point) |

---

### 6. `fromArray()` *(lama — backward-compatible)*

Alias kepada `fromPoints()`. Masih berfungsi untuk kod sedia ada.

```php
$geojson = GeoJsonConverter::fromArray($data);
```

---

## Response HTTP GeoJSON

Semua method convert di atas hanya memulangkan **string JSON**. Untuk API, gunakan kaedah `*Response()` yang terus memulangkan Laravel `Response` dengan header yang betul.

### Header yang ditetapkan secara automatik

| Header | Nilai | Tujuan |
|---|---|---|
| `Content-Type` | `application/geo+json` | Standard MIME type untuk GeoJSON (RFC 7946) |
| `Cache-Control` | `no-store` | Elak cache data geospatial yang mungkin berubah |

### Method Response

Setiap method convert mempunyai pasangan `*Response()`:

| Method Convert (string) | Method Response (HTTP) |
|---|---|
| `fromPoints(...)` | `pointsResponse(...)` |
| `fromLineStrings(...)` | `lineStringsResponse(...)` |
| `fromPolygons(...)` | `polygonsResponse(...)` |
| `fromWkt(...)` | `wktResponse(...)` |
| `fromMixed(...)` | `mixedResponse(...)` |

Semua method Response menerima parameter tambahan `$status` (HTTP status code, default `200`).

### Contoh Penggunaan dalam Controller

```php
use Mzm\GeoJsonConverter\GeoJsonConverter;

class LokasiController extends Controller
{
    // Data point — terus pulangkan response
    public function points()
    {
        $data = DB::table('lokasi')->select('nama', 'lat', 'lon')->get()->toArray();

        return GeoJsonConverter::pointsResponse($data);
    }

    // Data dari kolum geom (PostGIS/MySQL Spatial)
    public function spatial()
    {
        $data = DB::table('lokasi')
            ->selectRaw('ST_AsText(geom) AS geom, nama, kategori')
            ->get()
            ->toArray();

        return GeoJsonConverter::wktResponse($data);
    }

    // Dengan custom header tambahan
    public function withCustomHeader()
    {
        $data = DB::table('lokasi')->select('nama', 'lat', 'lon')->get()->toArray();

        return GeoJsonConverter::pointsResponse($data, status: 200);
    }
}
```

### Atau Gunakan `toResponse()` Secara Bebas

Jika sudah ada GeoJSON string (dari `ST_AsGeoJSON()` atau sumber lain), boleh wrap terus:

```php
// GeoJSON terus dari PostGIS
$geojson = DB::selectOne('SELECT ST_AsGeoJSON(ST_Collect(geom)) AS geojson FROM lokasi')->geojson;

return GeoJsonConverter::toResponse($geojson);
```

---

## Ringkasan Method

### Convert (pulangkan string)

| Method | Geometry | Input koordinat |
|---|---|---|
| `fromPoints($data, $latKey, $lonKey)` | Point | `'lat' => 3.15, 'lon' => 101.70` |
| `fromLineStrings($data, $coordinatesKey)` | LineString | `'coordinates' => [[lon,lat], ...]` |
| `fromPolygons($data, $coordinatesKey)` | Polygon | `'coordinates' => [[[lon,lat], ...]]` |
| `fromWkt($data, $geomKey)` | Semua jenis (dari DB `geom`) | `'geom' => 'POINT(101.70 3.15)'` |
| `fromMixed($data, $typeKey, ...)` | Semua jenis | `'geometry_type' => 'Point\|LineString\|...'` |
| `fromArray($data, $latKey, $lonKey)` | Point *(alias)* | `'lat' => 3.15, 'lon' => 101.70` |

### Response (pulangkan Laravel HTTP Response)

| Method | Keterangan |
|---|---|
| `pointsResponse($data, $latKey, $lonKey, $status)` | Response untuk data point |
| `lineStringsResponse($data, $coordinatesKey, $status)` | Response untuk LineString |
| `polygonsResponse($data, $coordinatesKey, $status)` | Response untuk Polygon |
| `wktResponse($data, $geomKey, $status)` | Response untuk data WKT dari DB |
| `mixedResponse($data, $typeKey, ..., $status)` | Response untuk data pelbagai geometry |
| `toResponse($geojson, $status, $headers)` | Wrap GeoJSON string sedia ada ke Response |

---

## Format Output

Semua method mengembalikan string JSON dalam format **GeoJSON FeatureCollection**:

```json
{
    "type": "FeatureCollection",
    "features": [
        {
            "type": "Feature",
            "geometry": {
                "type": "Point",
                "coordinates": [101.7038, 3.1528]
            },
            "properties": {
                "nama": "KL Tower"
            }
        }
    ]
}
```

> **Nota:** Koordinat GeoJSON menggunakan susunan `[longitud, latitud]` mengikut standard RFC 7946.

---

## Maklumat Tambahan

### Format Data Spatial dalam Pangkalan Data

Kolum geometry dalam pangkalan data wujud dalam beberapa format:

| Format | Nama Penuh | Penerangan |
|---|---|---|
| **WKB** | Well-Known Binary | Format **binari** lalai yang disimpan dalam DB — tidak boleh dibaca terus |
| **WKT** | Well-Known Text | Format **teks** yang boleh dibaca — hasil `ST_AsText()` |
| **GeoJSON** | — | Format JSON standard — hasil `ST_AsGeoJSON()` |
| **EWKT** | Extended WKT | WKT dengan maklumat SRID, contoh: `SRID=4326;POINT(...)` — khusus PostGIS |

### Pilih Pendekatan yang Sesuai

```
Data di DB (kolum geom/geometry)
│
├── Jika nak tukar kepada GeoJSON menggunakan package ini:
│   └── Guna ST_AsText(geom) dalam SQL  →  GeoJsonConverter::fromWkt($data)
│
└── Jika nak GeoJSON terus tanpa package ini:
    └── Guna ST_AsGeoJSON(geom) dalam SQL  →  terus encode ke JSON
```

### Contoh Lengkap: PostGIS dengan `fromWkt()`

```php
// 1. Query — tukar WKB kepada WKT dalam SQL
$rows = DB::table('lokasi')
    ->selectRaw('ST_AsText(geom) AS geom, nama, kategori, kawasan')
    ->where('aktif', true)
    ->get()
    ->toArray();

// 2. Tukar dan pulangkan sebagai HTTP Response (dengan header yang betul)
return GeoJsonConverter::wktResponse($rows);

// -- ATAU -- jika perlu string sahaja (contoh: simpan ke fail)
$geojson = GeoJsonConverter::fromWkt($rows);
```

### Contoh Lengkap: Alternatif Tanpa Package (`ST_AsGeoJSON`)

Jika data hanya satu jenis geometry, boleh bypass package sepenuhnya:

```php
// PostGIS — dapatkan geometry sebagai GeoJSON string terus dari DB
$rows = DB::table('lokasi')
    ->selectRaw('ST_AsGeoJSON(geom)::json AS geometry, nama, kategori')
    ->get();

// Bina FeatureCollection secara manual
$features = $rows->map(fn($row) => [
    'type'       => 'Feature',
    'geometry'   => json_decode($row->geometry),
    'properties' => ['nama' => $row->nama, 'kategori' => $row->kategori],
]);

$geojson = json_encode([
    'type'     => 'FeatureCollection',
    'features' => $features,
], JSON_PRETTY_PRINT);
```

> **Bil sebab guna package ini berbanding `ST_AsGeoJSON` terus:**
> - Data bercampur darielbagai sumber (bukan hanya DB)
> - Perlu kawalan penuh ke atas `properties`
> - Data dari CSV/Excel/API yang tiada fungsi spatial

### SRID dan Proyeksi

Package ini mengandaikan koordinat dalam **WGS 84 (SRID 4326)** — sistem koordinat standard untuk GeoJSON. Jika data dalam sistem koordinat lain (contoh: Malaysia GDM2000, MRSO), perlu tukar dahulu dalam SQL:

```sql
-- Tukar dari SRID 3168 (Kertau RSO) kepada WGS 84 (4326) dalam PostGIS
SELECT ST_AsText(ST_Transform(geom, 4326)) AS geom, nama FROM lokasi
```

---

## Lesen

This package is open-sourced software licensed under the MIT license. See the [LICENSE](LICENSE) file for more details.
