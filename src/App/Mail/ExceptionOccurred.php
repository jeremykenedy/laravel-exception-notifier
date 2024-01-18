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
        $emailsTo = str_getcsv(config('exceptions.emailExceptionsTo', ''));
        $ccEmails = str_getcsv(config('exceptions.emailExceptionCCto', ''));
        $bccEmails = str_getcsv(config('exceptions.emailExceptionBCCto', ''));
        $fromSender = config('exceptions.emailExceptionFrom', '');
        $subject = config('exceptions.emailExceptionSubject', '');

        return $this->from($fromSender)
                    ->to($emailsTo)
                    ->cc($ccEmails)
                    ->bcc($bccEmails)
                    ->subject($subject)
                    ->view(config('exceptions.emailExceptionView'))
                    ->with('content', $this->content);
    }
}
