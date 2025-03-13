<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontReport = [];

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $exception): Response|JsonResponse|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        if ($request->expectsJson() || request()->wantsJson()) {
            return $this->handleApiException($exception);
        }

        return parent::render($request, $exception);
    }

    private function handleApiException(Throwable $exception): JsonResponse
    {
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->unauthorizedResponse();
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationErrorResponse($exception->errors());
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->notFoundResponse();
        }

        return $this->serverErrorResponse($exception->getMessage());
    }


    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
