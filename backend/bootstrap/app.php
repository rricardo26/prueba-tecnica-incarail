<?php

use App\Exceptions\ExternalRateLimitExceededException;
use App\Exceptions\ExternalResourceRestrictedException;
use App\Exceptions\ExternalServiceUnavailableException;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $renderJson = function (string $code, string $message, int $status, mixed $details = null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                ],
            ], $status);
        };

        $exceptions->render(fn (AuthenticationException $e, Request $r) => $renderJson('UNAUTHENTICATED', 'No autenticado o token inválido.', 401));

        $exceptions->render(fn (AuthorizationException|AccessDeniedHttpException $e, Request $r) => $renderJson('FORBIDDEN', 'No tienes permisos para realizar esta acción.', 403));

        $exceptions->render(fn (ExternalResourceRestrictedException $e, Request $r) => $renderJson('EXTERNAL_RESOURCE_RESTRICTED', $e->getMessage(), 403));

        $exceptions->render(fn (ModelNotFoundException|NotFoundHttpException $e, Request $r) => $renderJson('RESOURCE_NOT_FOUND', 'El recurso solicitado no existe.', 404));

        $exceptions->render(fn (ValidationException $e, Request $r) => $renderJson('VALIDATION_ERROR', 'Los datos enviados no son válidos.', 422, $e->errors()));

        $exceptions->render(fn (ExternalRateLimitExceededException|TooManyRequestsHttpException $e, Request $r) => $renderJson('RATE_LIMIT_EXCEEDED', $e->getMessage() ?: 'Límite de peticiones excedido.', 429));

        $exceptions->render(fn (ExternalServiceUnavailableException $e, Request $r) => $renderJson('EXTERNAL_SERVICE_UNAVAILABLE', $e->getMessage(), $e->getCode() ?: 503));
    })->create();
