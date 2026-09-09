<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $password;
    public $code;

    public function __construct($password, $code)
    {
        $this->password = $password;
        $this->code = $code;
    }

    public function build()
    {
        $message = "مرحباً،\n\n"
            . "تمت الموافقة على حسابك كمسوق.\n\n"
            . "كلمة المرور الخاصة بك: {$this->password}\n"
            . "كود المسوق الخاص بك: {$this->code}\n\n"
            . "نتمنى لك التوفيق.";

        return $this->subject('تمت الموافقة على حسابك')
                    ->withSwiftMessage(function ($swift) use ($message) {
                        $swift->setBody($message, 'text/plain');
                    });
    }
}
