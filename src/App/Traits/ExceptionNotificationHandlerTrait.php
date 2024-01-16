<?php

namespace App\Traits;

use App\Mail\ExceptionOccurred;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
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
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     */
    public function report(Throwable $e): void
    {
        $enableEmailExceptions = config('exceptions.emailExceptionEnabled');

        if ($enableEmailExceptions === '') {
            $enableEmailExceptions = config('exceptions.emailExceptionEnabledDefault');
        }

        if ($enableEmailExceptions) {
            if ($this->shouldReport($e)) {
                $this->sendEmail($e);
            }
        }

        parent::report($e);
    }

    /**
     * Sends an email upon exception.
     */
    public function sendEmail(Throwable $exception): void
    {
        try {
            $hasDebugModeEnabled = app()->hasDebugModeEnabled();
            $renderer = new HtmlErrorRenderer($hasDebugModeEnabled);
            $html = $renderer->render($exception);

            Mail::send(new ExceptionOccurred($html));
        } catch (Throwable $exception) {
            Log::error($exception);
        }
    }
}
