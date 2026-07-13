<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventario: Compresion de Imagenes
    |--------------------------------------------------------------------------
    |
    | Estos valores controlan la compresion del inventario antes de guardar
    | las fotos en storage/app/public.
    |
    */
    'image_max_bytes' => (int) env('INVENTORY_IMAGE_MAX_BYTES', 512000),
    'image_max_dimension' => (int) env('INVENTORY_IMAGE_MAX_DIMENSION', 2200),
];

