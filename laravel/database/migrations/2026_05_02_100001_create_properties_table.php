<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Part A — sellable units within a project (BWP technical assessment).
 *
 * Each property belongs to one `projects` row; deleting a project cascades to its properties.
 */
return new class extends Migration
{
    /**
     * Create the `properties` table.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('status');
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Drop the `properties` table.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
