<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Image Processor Driver
    |--------------------------------------------------------------------------
    |
    | The image processor driver to use for image manipulation.
    |
    | Supported: "gd", "imagick"
    |
    */
    'driver' => env('IMAGE_DRIVER', 'gd'),

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk to use for storing uploaded images.
    |
    */
    'disk' => env('IMAGE_DISK', 'public'),
];
