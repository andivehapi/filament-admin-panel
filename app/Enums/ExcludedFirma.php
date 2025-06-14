<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExcludedFirma: int implements HasLabel
{
    case ADAPTECH = 2;
    case DEMO_FAHRSCHULE = 8;

    public function getLabel(): ?string
    {
        return match($this) {
            self::ADAPTECH => 'Adaptech',
            self::DEMO_FAHRSCHULE => 'Demo Fahrschule',
        };
    }
} 