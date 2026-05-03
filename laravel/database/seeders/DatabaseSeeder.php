<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Application entry point for `php artisan db:seed` (Part A loads reference data only).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run seeders. Part A: {@see BwpAssessmentReferenceSeeder} only.
     */
    public function run(): void
    {
        $this->call(BwpAssessmentReferenceSeeder::class);
    }
}
