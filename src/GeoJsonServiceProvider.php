<?php

namespace Mzm\GeoJsonConverter;

use Illuminate\Support\ServiceProvider;

class GeoJsonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Anda boleh bind kelas ke container jika perlu
    }

    public function boot()
    {
        // Bootstrapping jika ada fail config atau migrations
    }
}