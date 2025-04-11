<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WorkflowStatus: string implements hasColor, hasLabel
{
    case NOTSTARTED = 'Not Started';
    case INPROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case UNKNOWN = 'Unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NOTSTARTED => __('enums.workflow_status.not_started'),
            self::INPROGRESS => __('enums.workflow_status.in_progress'),
            self::COMPLETED => __('enums.workflow_status.completed'),
            self::UNKNOWN => __('enums.workflow_status.unknown'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NOTSTARTED => 'danger',
            self::INPROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::UNKNOWN => 'primary',
        };
    }
}
