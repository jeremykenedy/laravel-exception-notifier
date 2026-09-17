<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExceptionOccurred extends Mailable
{
    use Queueable, SerializesModels;

    private array $content;

    /**
     * Create a new message instance.
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $emailsTo = $this->recipients('emailExceptionsTo');
        $emailsCc = $this->recipients('emailExceptionCCto');
        $emailsBcc = $this->recipients('emailExceptionBCCto');
        $fromSender = config('exceptions.emailExceptionFrom');
        $subject = config('exceptions.emailExceptionSubject');

        return new Envelope(
            from: $fromSender,
            to: $emailsTo,
            cc: $emailsCc,
            bcc: $emailsBcc,
            subject: $subject
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = config('exceptions.emailExceptionView');

        return new Content(
            view: $view,
            with: [
                'content' => $this->content,
            ]
        );
    }

    public function build()
    {
        if (class_exists(Envelope::class)) {
            return $this;
        }

        // Early Laravel 9 releases use build() instead of envelope()/content().
        $from = config('exceptions.emailExceptionFrom');
        $subject = config('exceptions.emailExceptionSubject');
        if ($from) {
            $this->from($from);
        }
        if ($subject !== null) {
            $this->subject($subject);
        }

        return $this->to($this->recipients('emailExceptionsTo'))
            ->cc($this->recipients('emailExceptionCCto'))
            ->bcc($this->recipients('emailExceptionBCCto'))
            ->view(config('exceptions.emailExceptionView'))
            ->with(['content' => $this->content]);
    }

    private function recipients(string $key): array
    {
        $value = config('exceptions.'.$key);

        return $value ? str_getcsv($value, ',', '"', '\\') : [];
    }
}
