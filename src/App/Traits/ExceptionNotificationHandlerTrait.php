<?php

namespace App\Traits;

use App\Mail\ExceptionOccurred;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

trait ExceptionNotificationHandlerTrait
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        foreach ([
            AuthenticationException::class,
            AuthorizationException::class,
            HttpException::class,
            ModelNotFoundException::class,
            TokenMismatchException::class,
            ValidationException::class,
        ] as $exception) {
            $this->ignore($exception);
        }

        $this->reportable(function (Throwable $e) {
            $enableEmailExceptions = config('exceptions.emailExceptionEnabled');

            if ($enableEmailExceptions) {
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
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTrace(),
                'url'     => request()->url(),
                'body'    => request()->all(),
                'ip'      => request()->ip(),
            ];

            Mail::send(new ExceptionOccurred($content));
        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }
}
