<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
     * Build the message.
     */
    public function build(): Mailable
    {
        $emailsTo = str_getcsv(config('emailExceptionsTo', ''));
        $ccEmails = str_getcsv(config('emailExceptionCCto', ''));
        $bccEmails = str_getcsv(config('emailExceptionBCCto', ''));
        $fromSender = config('emailExceptionFrom', '');
        $subject = config('emailExceptionSubject', '');

        return $this->from($fromSender)
                    ->to($emailsTo)
                    ->cc($ccEmails)
                    ->bcc($bccEmails)
                    ->subject($subject)
                    ->view(config('emailExceptionView'))
                    ->with('content', $this->content);
    }
}
