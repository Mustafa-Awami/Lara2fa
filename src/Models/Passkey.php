<?php

namespace MustafaAwami\Lara2fa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use MustafaAwami\Lara2fa\Services\WebauthnJsonSerializer;
use Webauthn\PublicKeyCredentialSource;

class Passkey extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'data',
    ];

    public function data(): Attribute
    {
        return new Attribute(
            get: fn (string $value) => WebauthnJsonSerializer::deserialize($value, PublicKeyCredentialSource::class),
            set: fn (PublicKeyCredentialSource $value) => WebauthnJsonSerializer::serialize($value),
        );
    }
}
