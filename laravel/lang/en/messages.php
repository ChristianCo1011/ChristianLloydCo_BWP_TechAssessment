<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API error responses (Part A JSON)
    |--------------------------------------------------------------------------
    */

    'api' => [
        'not_found' => 'The requested resource was not found.',

        'projects' => [
            'list_failed' => 'Unable to list projects.',
            'not_found' => 'Project not found.',
        ],
        'properties' => [
            'list_failed' => 'Unable to list properties.',
            'not_found' => 'Property not found.',
            'show_failed' => 'Unable to load property.',
            'create_failed' => 'Unable to create property.',
            'update_failed' => 'Unable to update property.',
            'delete_failed' => 'Unable to delete property.',
            'delete_success' => 'Property deleted successfully.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log lines (Part A API)
    |--------------------------------------------------------------------------
    */

    'log' => [
        'projects' => [
            'list_error' => 'Error listing projects.',
        ],
        'properties' => [
            'list_error' => 'Error listing properties.',
            'show_error' => 'Error showing property.',
            'create_error' => 'Error creating property.',
            'update_error' => 'Error updating property.',
            'delete_error' => 'Error deleting property.',
        ],
    ],
];
