<?php

namespace App\Enums;

enum Roles: string
{
    case COFFEESHOP = 'coffeeshop';
    case SPECIALIST = 'specialist';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            static::COFFEESHOP => 'Coffeeshop',
            static::SPECIALIST => 'Specialist',
            static::USER => 'Generic User',
        };
    }
}
