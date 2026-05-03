<?php

/**
 * BWP Software Engineer Technical Assessment — Part A required seed data.
 *
 * Candidates must persist exactly these projects and properties (adapt namespaces
 * and class names to your app). Copy into e.g. database/seeders/BwpAssessmentReferenceSeeder.php,
 * register from DatabaseSeeder, then `php artisan db:seed`.
 *
 * This file is not auto-wired; integrate it into your submission’s Laravel app.
 */

namespace Database\Seeders;

use App\Enums\PropertyStatus;
use App\Models\{
    Project,
    Property,
};
use Illuminate\Database\Seeder;

/**
 * Eloquent-backed reference seeder for Part A (invoked from {@see DatabaseSeeder}).
 */
class BwpAssessmentReferenceSeeder extends Seeder
{
    /**
     * Upsert two reference projects (SUNSET, RIDGE) and five properties matching Part D sample data.
     *
     * Uses `updateOrCreate` on `Project` by `code`, then `firstOrCreate` on `Property` by
     * `project_id` + `label` so re-seeding is idempotent without wiping unrelated rows.
     */
    public function run(): void
    {
        /** @var Project $sunset */
        $sunset = Project::query()->updateOrCreate(
            ['code' => 'SUNSET'],
            ['name' => 'Sunset Residences']
        );
        /** @var Project $ridge */
        $ridge = Project::query()->updateOrCreate(
            ['code' => 'RIDGE'],
            ['name' => 'Ridge Estate']
        );

        /** @var list<array{project_id: int, label: string, status: PropertyStatus, price: int}> */
        $rows = [
            ['project_id' => $sunset->id, 'label' => 'Apt 101', 'status' => PropertyStatus::Available, 'price' => 450000],
            ['project_id' => $sunset->id, 'label' => 'Apt 102', 'status' => PropertyStatus::Reserved, 'price' => 460000],
            ['project_id' => $sunset->id, 'label' => 'Penthouse A', 'status' => PropertyStatus::Available, 'price' => 1200000],
            ['project_id' => $ridge->id, 'label' => 'Lot 7', 'status' => PropertyStatus::Sold, 'price' => 890000],
            ['project_id' => $ridge->id, 'label' => 'Apt 205', 'status' => PropertyStatus::Available, 'price' => 520000],
        ];

        foreach ($rows as $row) {
            Property::query()->firstOrCreate(
                [
                    'project_id' => $row['project_id'],
                    'label' => $row['label'],
                ],
                [
                    'status' => $row['status'],
                    'price' => $row['price'],
                ]
            );
        }
    }
}
