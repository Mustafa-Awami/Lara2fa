<?php

namespace Mustafa\Lara2fa\Services;

use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

class WebauthnJsonSerializer
{

    public static function serialize($data): string
    {
        return (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->serialize($data ,'json');
    }

    /**
     * @template TReturn
     * @param class-string<TReturn> $type
     * @return TReturn
     */
    public static function deserialize(string $json, string $type)
    {
        return (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->deserialize($json, $type, 'json');
    }
}