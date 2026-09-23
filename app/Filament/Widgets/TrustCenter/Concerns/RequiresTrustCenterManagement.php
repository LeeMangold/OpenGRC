<?php

namespace App\Filament\Widgets\TrustCenter\Concerns;

use Filament\Actions\Action;
use Illuminate\Auth\Access\Response;

/**
 * Restricts a Trust Center widget, and every action on it without its own
 * authorization, to users with the "Manage Trust Center" permission.
 *
 * The Trust Center page is also reachable with only "Manage Trust Access",
 * which must not be able to change documents, certifications or content.
 */
trait RequiresTrustCenterManagement
{
    public static function canView(): bool
    {
        return auth()->user()?->can('Manage Trust Center') ?? false;
    }

    /**
     * Filament re-checks canView() on every subsequent request; also refuse the initial render.
     */
    public function mountRequiresTrustCenterManagement(): void
    {
        abort_unless(static::canView(), 403);
    }

    public function getDefaultActionAuthorizationResponse(Action $action): ?Response
    {
        return static::canView() ? null : Response::deny();
    }
}
