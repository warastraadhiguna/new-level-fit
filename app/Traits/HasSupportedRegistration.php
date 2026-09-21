<?php

namespace App\Traits;

trait HasSupportedRegistration
{
    public static function bootHasSupportedRegistration(): void
    {
        static::addGlobalScope('supported_registration', function ($query) {
            $query->whereHas($query->getModel()->supportedRegistrationRelation);
        });
    }
}
