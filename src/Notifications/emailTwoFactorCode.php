<?php

namespace MustafaAwami\Lara2fa\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MustafaAwami\Lara2fa\Lara2fa;

class emailTwoFactorCode extends Notification
{
    use Queueable;

    private $code;

    private $actionUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($code, $actionUrl = null)
    {
        $this->code = $code;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $expireTimeWindow = Lara2fa::emailTwoFactorWindow();
        
        /**
         * @var MailMessage
         */
        $emailMessage = (new MailMessage);

        $emailMessage->line('Your two factor code is '.$this->code);
        if($this->actionUrl){
            $emailMessage->action('Verify Here', $this->actionUrl);
        }
        $emailMessage->line('The code will expire in '.$expireTimeWindow.' minutes')
                    ->line("If you didn't make this request, ignore this email.");


        return $emailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
