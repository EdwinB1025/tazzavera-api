<?php

namespace App\Enums;

enum RoastLevel: string
{
    case Light = 'light';
    case MediumLight = 'medium_light';
    case Medium = 'medium';
    case MediumDark = 'medium_dark';
    case Dark = 'dark';
}
