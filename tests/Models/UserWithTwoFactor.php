<?php

namespace MustafaAwami\Lara2fa\Tests\Models;

use MustafaAwami\Lara2fa\Traits\TwoFactorAuthenticatable;

class UserWithTwoFactor extends \Illuminate\Foundation\Auth\User
{
    use TwoFactorAuthenticatable;

    protected $table = 'users';
}