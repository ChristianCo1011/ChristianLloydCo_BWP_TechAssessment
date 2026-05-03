<?php

namespace App\Repositories;

use App\Interfaces\PropertyInterface;
use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class PropertyRepository
 *
 * Repository for property data access operations (Part A).
 */
class PropertyRepository implements PropertyInterface
{
    /**
     * {@inheritDoc}
     */
    public function allWithProject(?int $projectId): Collection
    {
        $query = Property::query()
            ->with('project')
            ->orderBy('id');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Property
    {
        $property = Property::query()->create($data);
        $property->load('project');

        return $property;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): Property
    {
        $property = Property::query()->findOrFail($id);
        $property->update($data);
        $property->load('project');

        return $property;
    }

    /**
     * {@inheritDoc}
     */
    public function softDelete(int $id): array
    {
        $property = Property::query()->findOrFail($id);
        $property->delete();

        return [
            'id' => $property->id,
            'deleted_at' => $property->deleted_at->toIso8601String(),
        ];
    }
}
