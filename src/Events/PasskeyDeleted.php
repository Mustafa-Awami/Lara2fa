<?php

namespace MustafaAwami\Lara2fa\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PasskeyDeleted
{
    use Dispatchable;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
}