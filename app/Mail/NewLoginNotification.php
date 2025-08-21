<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewLoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public object $user;
    public string $ip;
    public string $location;
    public $deviceInfo;

    /**
     * Create a new message instance.
     *
     * @param object $user
     * @param string $ip
     * @param string $location
     * @param mixed $deviceInfo
     */
    public function __construct($user, $ip, $location, $deviceInfo = null)
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->location = $location;
        $this->deviceInfo = $deviceInfo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Nouvelle connexion détectée')
                    ->view('emails.new-login-notification');
    }
}
