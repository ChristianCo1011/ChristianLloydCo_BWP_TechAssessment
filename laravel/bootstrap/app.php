<?php

/**
 * Application bootstrap: registers web, API, console routes, and middleware stack.
 *
 * Part A registers `routes/api.php` (JSON under `/api`).
 */

use App\Http\Middleware\ForceJsonResponse;
use App\Models\Project;
use App\Models\Property;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $apiNotFound = function (Request $request, ModelNotFoundException $e): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = match ($e->getModel()) {
                Property::class => __('messages.api.properties.not_found'),
                Project::class => __('messages.api.projects.not_found'),
                default => __('messages.api.not_found'),
            };

            return response()->json(['message' => $message], 404);
        };

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($apiNotFound) {
            return $apiNotFound($request, $e);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($apiNotFound) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                return $apiNotFound($request, $previous);
            }

            return response()->json([
                'message' => __('messages.api.not_found'),
            ], 404);
        });
    })->create();
