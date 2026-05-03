<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part A — development / estate projects (BWP technical assessment).
 *
 * A project groups sellable properties (e.g. SUNSET, RIDGE) with a display name and optional unique code.
 */
return new class extends Migration
{
    /**
     * Create the `projects` table.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Drop the `projects` table.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
