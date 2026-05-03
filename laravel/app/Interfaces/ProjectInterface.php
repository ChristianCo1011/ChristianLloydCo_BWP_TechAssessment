<?php

namespace App\Interfaces;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface ProjectInterface
 *
 * Defines the contract for project data access (Part A).
 */
interface ProjectInterface
{
    /**
     * Get all projects with related property counts.
     *
     * @return Collection<int, Project>
     */
    public function allWithPropertiesCount(): Collection;
}
