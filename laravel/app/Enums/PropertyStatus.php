<?php

namespace App\Enums;

/**
 * Allowed property listing states (Part A / Part D sample data).
 */
enum PropertyStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
}
