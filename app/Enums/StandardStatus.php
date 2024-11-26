<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum StandardStatus: string implements hasColor, hasDescription, hasLabel
{
    case DRAFT = 'Draft';
    case NOT_IN_SCOPE = 'Not in Scope';
    case IN_SCOPE = 'In Scope';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::NOT_IN_SCOPE => 'Not In Scope',
            self::IN_SCOPE => 'In Scope',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'primary',
            self::IN_SCOPE => 'success',
            self::NOT_IN_SCOPE => 'danger',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Not yet reviewed to determine scope',
            self::IN_SCOPE => 'In Scope for Assessment',
            self::NOT_IN_SCOPE => 'Not In Scope for Assessment',
        };
    }
}
