<?php

namespace App\Services;

use App\Enums\PropertyStatus;
use App\Interfaces\PropertyInterface;
use App\Models\Property;

/**
 * Class PropertyService
 *
 * Application service for property use cases (Part A).
 */
class PropertyService
{
    public function __construct(
        private readonly PropertyInterface $properties,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function listProperties(?int $projectId): array
    {
        return $this->properties->allWithProject($projectId)
            ->map(fn (Property $property) => $this->formatProperty($property))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperty(Property $property): array
    {
        $property->loadMissing('project');

        return $this->formatProperty($property);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createProperty(array $data): array
    {
        $property = $this->properties->create($data);

        return $this->formatProperty($property);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateProperty(int $id, array $data): array
    {
        $property = $this->properties->update($id, $data);

        return $this->formatProperty($property);
    }

    /**
     * @return array{id: int, deleted_at: string}
     */
    public function deleteProperty(int $id): array
    {
        return $this->properties->softDelete($id);
    }

    /**
     * Shape a single property (and nested project) for JSON `data` payloads.
     *
     * @return array<string, mixed>
     */
    private function formatProperty(Property $property): array
    {
        $project = $property->relationLoaded('project')
            ? $property->project
            : $property->project()->first();

        return [
            'id' => $property->id,
            'project_id' => $property->project_id,
            'label' => $property->label,
            'status' => $property->status instanceof PropertyStatus
                ? $property->status->value
                : (string) $property->status,
            'price' => $this->priceToNumber($property->price),
            'created_at' => $property->created_at?->toIso8601String(),
            'updated_at' => $property->updated_at?->toIso8601String(),
            'project' => $project ? [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'created_at' => $project->created_at?->toIso8601String(),
                'updated_at' => $project->updated_at?->toIso8601String(),
            ] : null,
        ];
    }

    /**
     * Normalize a stored price for JSON (`null` when absent or empty string).
     *
     * @param  mixed  $price  Raw attribute from the model (e.g. decimal string, numeric, null).
     * @return float|null Cast number, or `null` when `$price` is `null` or `''`.
     */
    private function priceToNumber(mixed $price): ?float
    {
        if ($price === null || $price === '') {
            return null;
        }

        return (float) $price;
    }
}
