<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    StorePropertyRequest,
    UpdatePropertyRequest,
};
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\{
    JsonResponse,
    Request,
};
use Illuminate\Support\Facades\{
    DB,
    Log,
};
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON API controller for properties (Part A).
 */
class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    /**
     * List properties, optionally filtered by `project_id` query string.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $projectId = $request->filled('project_id')
                ? $request->integer('project_id')
                : null;

            $data = $this->propertyService->listProperties($projectId);

            return response()->json([
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            Log::error(__('messages.log.properties.list_error'), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'message' => __('messages.api.properties.list_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified property with its parent project.
     *
     * @param Property $property Route-model-bound property.
     *
     * @return JsonResponse
     */
    public function show(Property $property): JsonResponse
    {
        try {
            return response()->json([
                'data' => $this->propertyService->getProperty($property),
            ]);
        } catch (\Throwable $exception) {
            Log::error(__('messages.log.properties.show_error'), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'property_id' => $property->id,
            ]);

            return response()->json([
                'message' => __('messages.api.properties.show_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created property.
     *
     * @param StorePropertyRequest $request
     *
     * @return JsonResponse
     */
    public function store(StorePropertyRequest $request): JsonResponse
    {
        try {
            $data = DB::transaction(function () use ($request) {
                return $this->propertyService->createProperty($request->validated());
            });

            return response()->json([
                'data' => $data,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $exception) {
            Log::error(__('messages.log.properties.create_error'), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'message' => __('messages.api.properties.create_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified property.
     *
     * @param UpdatePropertyRequest $request
     * @param Property $property Route-model-bound property.
     *
     * @return JsonResponse
     */
    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        try {
            $data = DB::transaction(function () use ($request, $property) {
                return $this->propertyService->updateProperty(
                    $property->id,
                    $request->validated(),
                );
            });

            return response()->json([
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            Log::error(__('messages.log.properties.update_error'), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'property_id' => $property->id,
            ]);

            return response()->json([
                'message' => __('messages.api.properties.update_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified property.
     *
     * @param Property $property Route-model-bound property.
     *
     * @return JsonResponse
     */
    public function destroy(Property $property): JsonResponse
    {
        try {
            $data = DB::transaction(function () use ($property) {
                return $this->propertyService->deleteProperty($property->id);
            });

            return response()->json([
                'message' => __('messages.api.properties.delete_success'),
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            Log::error(__('messages.log.properties.delete_error'), [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'property_id' => $property->id,
            ]);

            return response()->json([
                'message' => __('messages.api.properties.delete_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
