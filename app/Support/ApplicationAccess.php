<?php

namespace App\Support;

final class ApplicationAccess
{
    public const MANAGEMENT = 'management';
    public const GYM_LANDING = 'gym_landing';

    public const ADMIN_APPLICATIONS = [
        self::MANAGEMENT,
        self::GYM_LANDING,
    ];
}
