<?php

namespace jeremykenedy\laravelexceptionnotifier\Test\Feature;

use App\Mail\ExceptionOccurred;
use App\Traits\ExceptionNotificationHandlerTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelexceptionnotifier\LaravelExceptionNotifier;
use jeremykenedy\laravelexceptionnotifier\Test\TestCase;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class CompatibilityTest extends TestCase
{
    public function test_legacy_publish_paths_and_defaults_remain_available(): void
    {
        $paths = ServiceProvider::pathsToPublish(LaravelExceptionNotifier::class, 'laravelexceptionnotifier');
        $this->assertSame([
            app_path('Mail/ExceptionOccurred.php'),
            resource_path('views/emails/exception.blade.php'),
            config_path('exceptions.php'),
        ], array_values($paths));
        $this->assertSame('emails.exception', config('laravelexceptionnotifier.emailExceptionView'));
        $this->assertTrue(config('laravelexceptionnotifier.emailExceptionEnabled'));
        $this->artisan('vendor:publish', ['--tag' => 'laravelexceptionnotifier'])->assertExitCode(0);
        foreach ($paths as $source => $destination) {
            $this->assertSame(file_get_contents($source), file_get_contents($destination));
        }
    }

    public function test_vendor_publish_preserves_existing_application_files(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'laravelexceptionnotifier'])->assertExitCode(0);
        file_put_contents(config_path('exceptions.php'), '<?php return ["custom" => true];');
        file_put_contents(resource_path('views/emails/exception.blade.php'), 'Custom email');
        $this->artisan('vendor:publish', ['--tag' => 'laravelexceptionnotifier'])->assertExitCode(0);
        $this->assertSame('Custom email', file_get_contents(resource_path('views/emails/exception.blade.php')));
        $this->assertSame('<?php return ["custom" => true];', file_get_contents(config_path('exceptions.php')));
    }

    public function test_published_mail_delivers_recipients_subject_and_exception_content(): void
    {
        $this->configureMail();
        $this->artisan('vendor:publish', ['--tag' => 'laravelexceptionnotifier'])->assertExitCode(0);
        $mail = new ExceptionOccurred($this->content());
        Mail::send($mail);
        $message = Mail::mailer()->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $this->assertSame('Production exception', $message->getSubject());
        $this->assertSame(['first@example.com', 'second@example.com'], array_map(function ($address) {
            return $address->getAddress();
        }, $message->getTo()));
        $this->assertSame('cc@example.com', $message->getCc()[0]->getAddress());
        $this->assertSame('bcc@example.com', $message->getBcc()[0]->getAddress());
        $this->assertSame('errors@example.com', $message->getFrom()[0]->getAddress());
        $this->assertStringContainsString('Unable to complete the request', $message->getHtmlBody());
        $this->assertStringNotContainsString('must-not-appear', $message->getHtmlBody());
    }

    public function test_custom_email_view_is_respected(): void
    {
        $this->configureMail();
        file_put_contents(resource_path('views/custom.blade.php'), 'Custom: {{ $content["message"] }}');
        config()->set('exceptions.emailExceptionView', 'custom');
        $this->assertSame('Custom: Unable to complete the request', (new ExceptionOccurred($this->content()))->render());
    }

    public function test_handler_honors_enabled_setting_and_handles_throwables(): void
    {
        $this->configureMail();
        Mail::fake();
        $handler = $this->handler();
        $handler->register();
        config()->set('exceptions.emailExceptionEnabled', false);
        ($handler->callback)(new RuntimeException('Disabled'));
        Mail::assertNothingSent();
        config()->set('exceptions.emailExceptionEnabled', true);
        ($handler->callback)(new \Error('Enabled'));
        Mail::assertSent(ExceptionOccurred::class, 1);
    }

    public function test_delivery_failure_is_logged_without_replacing_original_exception(): void
    {
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Transport unavailable'));
        Log::shouldReceive('error')->once()->withArgs(function (Throwable $exception) {
            return $exception->getMessage() === 'Transport unavailable';
        });
        $this->handler()->sendEmail(new RuntimeException('Original exception'));
        $this->addToAssertionCount(1);
    }

    public function test_provider_boot_does_not_create_application_files(): void
    {
        $this->assertFileDoesNotExist(app_path('Mail/ExceptionOccurred.php'));
        $this->assertFileDoesNotExist(config_path('exceptions.php'));
        $this->assertFileDoesNotExist(resource_path('views/emails/exception.blade.php'));
    }

    public function test_optional_recipients_and_sender_can_be_empty(): void
    {
        $this->configureMail();
        config()->set('exceptions.emailExceptionCCto', '');
        config()->set('exceptions.emailExceptionBCCto', null);
        config()->set('exceptions.emailExceptionFrom', null);
        $mail = new ExceptionOccurred([]);
        if (class_exists(Envelope::class)) {
            $envelope = $mail->envelope();
            $this->assertSame([], $envelope->cc);
            $this->assertSame([], $envelope->bcc);
            $this->assertNull($envelope->from);
            config()->set('exceptions.emailExceptionsTo', null);
            $this->assertSame([], (new ExceptionOccurred([]))->envelope()->to);
        } else {
            $mail->build();
            $this->assertSame([], $mail->cc);
            $this->assertSame([], $mail->bcc);
            $this->assertSame([], $mail->from);
            config()->set('exceptions.emailExceptionsTo', null);
            $this->assertSame([], (new ExceptionOccurred([]))->build()->to);
        }
    }

    public function test_quoted_csv_and_serialized_mail_keep_existing_behavior(): void
    {
        $this->configureMail();
        config()->set('exceptions.emailExceptionsTo', '"first@example.com","second@example.com"');
        $mail = unserialize(serialize(new ExceptionOccurred($this->content())));
        if (class_exists(Envelope::class)) {
            $this->assertSame('first@example.com', $mail->envelope()->to[0]->address);
            $this->assertSame('second@example.com', $mail->envelope()->to[1]->address);
        } else {
            $mail->build();
            $this->assertSame('first@example.com', $mail->to[0]['address']);
            $this->assertSame('second@example.com', $mail->to[1]['address']);
        }
        $this->assertSame($this->content(), $this->mailContent($mail));
    }

    public function test_handler_passes_original_exception_and_request_data_to_the_mailer(): void
    {
        Mail::fake();
        $request = Request::create('https://example.com/checkout?query=ignored', 'POST', ['item' => 'sample'], [], [], ['REMOTE_ADDR' => '192.0.2.8']);
        $this->app->instance('request', $request);
        $exception = new RuntimeException('Checkout failed');
        $this->handler()->sendEmail($exception);
        Mail::assertSent(ExceptionOccurred::class, function ($mail) use ($exception) {
            $content = $this->mailContent($mail);
            $this->assertSame($exception->getMessage(), $content['message']);
            $this->assertSame($exception->getFile(), $content['file']);
            $this->assertSame($exception->getLine(), $content['line']);
            $this->assertSame($exception->getTrace(), $content['trace']);
            $this->assertSame('https://example.com/checkout', $content['url']);
            $this->assertSame('sample', $content['body']['item']);
            $this->assertSame('192.0.2.8', $content['ip']);

            return true;
        });
    }

    public function test_real_handler_preserves_ignored_exception_rules(): void
    {
        $this->configureMail();
        Mail::fake();
        $handler = new class($this->app) extends Handler
        {
            use ExceptionNotificationHandlerTrait;

            protected $dontReport = [\LogicException::class];
        };
        foreach ([
            new \LogicException('Application-specific exclusion'),
            new AuthenticationException,
            new AuthorizationException,
            new HttpException(404),
            new ModelNotFoundException,
            new TokenMismatchException,
            ValidationException::withMessages(['email' => 'Invalid email']),
        ] as $exception) {
            $handler->report($exception);
        }
        Mail::assertNothingSent();
        $handler->report(new RuntimeException('Report this exception'));
        Mail::assertSent(ExceptionOccurred::class, 1);
    }

    private function handler()
    {
        return new class
        {
            use ExceptionNotificationHandlerTrait;

            public $callback;

            public function ignore(string $class): void
            {
            }

            public function reportable($callback): void
            {
                $this->callback = $callback;
            }
        };
    }
}
