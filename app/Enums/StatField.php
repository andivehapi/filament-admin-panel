<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatField: string implements HasLabel, HasIcon, HasColor
{
    case ERWEITERT = 'erweitert';
    case STARTER = 'starter';
    case KURS = 'kurs';
    case DIREKTPAY = 'direktpay';
    case PAYLINK = 'paylink';
    case FINSUIT = 'finsuit';

    public function getLabel(): ?string
    {
        return match($this) {
            self::ERWEITERT => 'Erweitert',
            self::STARTER => 'Basis',
            self::KURS => 'Kurs',
            self::DIREKTPAY => 'DirektPay',
            self::PAYLINK => 'PayLink',
            self::FINSUIT => 'FinSuit',
        };
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::ERWEITERT => 'heroicon-o-academic-cap',
            self::STARTER => 'heroicon-o-user-group',
            self::KURS => 'heroicon-o-book-open',
            self::DIREKTPAY => 'heroicon-o-currency-euro',
            self::PAYLINK => 'heroicon-o-link',
            self::FINSUIT => 'heroicon-o-briefcase',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::ERWEITERT => 'info',
            self::STARTER => 'primary',
            self::KURS => 'warning',
            self::DIREKTPAY => 'success',
            self::PAYLINK => 'danger',
            self::FINSUIT => 'secondary',
        };
    }

    public function isCurrency(): bool
    {
        return in_array($this, [self::DIREKTPAY, self::PAYLINK, self::FINSUIT]);
    }
} 