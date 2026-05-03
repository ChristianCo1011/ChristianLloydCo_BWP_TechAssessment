<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON API controller for projects (Part A).
 */
class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    /**
     * List all projects with each project's property count.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $data = $this->projectService->listProjects();

            return response()->json([
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            Log::error(__('messages.log.projects.list_error'), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'message' => __('messages.api.projects.list_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
