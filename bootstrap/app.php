<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle ValidationException (422)
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // Handle AuthenticationException (401)
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // Handle Spatie's UnauthorizedException and general AuthorizationExceptions (403)
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                $message = 'This action is unauthorized.';
                if ($e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
                    $message = 'You do not have the required permissions for this action.';
                }
                return response()->json(['message' => $message], 403);
            }
        });

        // Handle ModelNotFoundException and other 404s
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                $message = $e->getPrevious() instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                    ? 'The requested resource was not found.'
                    : 'Endpoint not found.';
                return response()->json(['message' => $message], 404);
            }
        });

        // Generic fallback for all other exceptions (500)
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                // For production, hide detailed errors. For debug, show them.
                $message = config('app.debug') ? $e->getMessage() : 'Server Error';
                
                // Get status code from exception if available, otherwise default to 500
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                if ($statusCode < 400 || $statusCode > 599) {
                    $statusCode = 500;
                }

                return response()->json(['message' => $message], $statusCode);
            }
        });
    })->create();
