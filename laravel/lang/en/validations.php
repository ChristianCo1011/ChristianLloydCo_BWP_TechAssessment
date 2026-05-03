<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attribute names (Part A property API)
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'project_id' => 'project',
        'label' => 'label',
        'status' => 'status',
        'price' => 'price',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule limits (strings so `__()` resolves; cast where needed)
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'property_label_max_length' => '255',
    ],

];
