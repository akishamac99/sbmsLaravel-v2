<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

// Laravel's normal reset-password email, except the link also says which kind of
// account it is for ("customers" or "staff"), so the right token table is checked.
class SbmsResetPassword extends ResetPassword
{
    public function __construct($token, protected string $broker)
    {
        parent::__construct($token);
    }

    protected function resetUrl($notifiable)
    {
        return url(route('password.reset', [
            'broker' => $this->broker,
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
