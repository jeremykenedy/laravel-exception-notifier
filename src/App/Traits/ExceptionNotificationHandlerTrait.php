<?php

namespace App\Traits;

use App\Mail\ExceptionOccurred;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait ExceptionNotificationHandlerTrait
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $enableEmailExceptions = config('emailExceptionEnabled');

            if ($enableEmailExceptions && $this->shouldReport($e)) {
                $this->sendEmail($e);
            }
        });
    }

    /**
     * Sends an email upon exception.
     */
    public function sendEmail(Throwable $exception): void
    {
        try {
            $content = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
                'url' => request()->url(),
                'body' => request()->all(),
                'ip' => request()->ip(),
            ];

            Mail::send(new ExceptionOccurred($content));
        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }
}
