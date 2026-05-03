<?php

namespace App\Services;

use App\Interfaces\ProjectInterface;
use App\Models\Project;

/**
 * Class ProjectService
 *
 * Application service for project use cases (Part A).
 */
class ProjectService
{
    public function __construct(
        private readonly ProjectInterface $projects,
    ) {}

    /**
     * Build the serialized list payload for `GET /api/projects` (`data` array).
     *
     * @return list<array<string, mixed>>
     */
    public function listProjects(): array
    {
        return $this->projects->allWithPropertiesCount()
            ->map(fn (Project $project) => $this->formatProject($project))
            ->values()
            ->all();
    }

    /**
     * Shape a single project for JSON `data` payloads.
     *
     * @return array<string, mixed>
     */
    private function formatProject(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'properties_count' => (int) $project->properties_count,
            'created_at' => $project->created_at?->toIso8601String(),
            'updated_at' => $project->updated_at?->toIso8601String(),
        ];
    }
}
