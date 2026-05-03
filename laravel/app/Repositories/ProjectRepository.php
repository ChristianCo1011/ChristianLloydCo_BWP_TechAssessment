<?php

namespace App\Repositories;

use App\Interfaces\ProjectInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ProjectRepository
 *
 * Repository for project data access operations (Part A).
 */
class ProjectRepository implements ProjectInterface
{
    /**
     * {@inheritDoc}
     */
    public function allWithPropertiesCount(): Collection
    {
        return Project::query()
            ->withCount('properties')
            ->orderBy('id')
            ->get();
    }
}
