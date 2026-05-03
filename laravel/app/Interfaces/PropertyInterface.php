<?php

namespace App\Interfaces;

use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface PropertyInterface
 *
 * Defines the contract for property data access (Part A).
 */
interface PropertyInterface
{
    /**
     * Get properties with `project` eager-loaded, optionally filtered by project id.
     *
     * @return Collection<int, Property>
     */
    public function allWithProject(?int $projectId): Collection;

    /**
     * Create a new property.
     *
     * @param  array<string, mixed>  $data  The attributes for the new record.
     * @return Property The created model with `project` loaded when applicable.
     */
    public function create(array $data): Property;

    /**
     * Update an existing property.
     *
     * @param  int  $id  The id of the property to update.
     * @param  array<string, mixed>  $data  The updated attributes.
     * @return Property The updated model with `project` loaded.
     */
    public function update(int $id, array $data): Property;

    /**
     * Soft-delete a property by primary key.
     *
     * @return array{id: int, deleted_at: string}
     */
    public function softDelete(int $id): array;
}
