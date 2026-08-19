<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Exceptions volontairement non journalisées : ce sont des erreurs
     * client normales (404, validation) et non des bugs serveur.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Toutes les réponses d'erreur de cette API sont renvoyées en JSON,
     * jamais en page HTML.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return new JsonResponse(['message' => 'Ressource introuvable.'], 404);
        }

        if ($exception instanceof ValidationException) {
            return new JsonResponse([
                'message' => 'Paramètres invalides.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof AuthorizationException) {
            return new JsonResponse(
                ['message' => $exception->getMessage() ?: 'Accès refusé.'],
                $exception->status() ?? 403
            );
        }

        if ($exception instanceof HttpException) {
            return new JsonResponse(
                ['message' => $exception->getMessage() ?: 'Erreur.'],
                $exception->getStatusCode()
            );
        }

        // API exclusivement JSON : toute autre exception renvoie un JSON 500
        // plutôt que la page d'erreur HTML par défaut de Lumen.
        $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

        return new JsonResponse([
            'message' => env('APP_DEBUG', false) ? $exception->getMessage() : 'Erreur serveur.',
        ], $status ?: 500);
    }
}
