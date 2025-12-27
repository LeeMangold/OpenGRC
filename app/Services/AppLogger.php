<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AppLogger
{
    /**
     * Log an application event with consistent SIEM-friendly format.
     *
     * @param  string  $category  Event category (e.g., 'auth', 'trust_center', 'audit')
     * @param  string  $event  Event name (e.g., 'Login', 'AccessRequest', 'MagicLinkAccess')
     * @param  string  $message  Human-readable message
     * @param  array  $context  Additional context data specific to the event
     * @param  Model|null  $subject  The model the action was performed on
     * @param  Model|null  $causer  The user/model that caused the action
     */
    public static function log(
        string $category,
        string $event,
        string $message,
        array $context = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        // Build activity log entry (Spatie Activity Log)
        $activityLogger = activity($category)
            ->event($event)
            ->withProperties(array_merge(
                self::getRequestContext(),
                $context
            ));

        if ($subject) {
            $activityLogger->performedOn($subject);
        }

        if ($causer) {
            $activityLogger->causedBy($causer);
        }

        $activityLogger->log($message);

        // Build SIEM-friendly log entry
        $logData = array_merge(
            [
                'category' => $category,
                'event' => $event,
            ],
            self::getRequestContext(),
            $context
        );

        Log::info("APPLOG - {$message}", $logData);
    }

    /**
     * Get standard request context for all log entries.
     */
    protected static function getRequestContext(): array
    {
        return [
            'ip' => request()->ip(),
            'host' => request()->header('host'),
            'forwarded_for' => request()->header('X-Forwarded-For'),
            'referer' => request()->header('referer'),
            'user_agent' => request()->userAgent(),
        ];
    }

    /**
     * Log an info-level event.
     */
    public static function info(
        string $category,
        string $event,
        string $message,
        array $context = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        self::log($category, $event, $message, $context, $subject, $causer);
    }

    /**
     * Log a warning-level event.
     */
    public static function warning(
        string $category,
        string $event,
        string $message,
        array $context = [],
        ?Model $subject = null,
        ?Model $causer = null
    ): void {
        // Build activity log entry
        $activityLogger = activity($category)
            ->event($event)
            ->withProperties(array_merge(
                self::getRequestContext(),
                $context
            ));

        if ($subject) {
            $activityLogger->performedOn($subject);
        }

        if ($causer) {
            $activityLogger->causedBy($causer);
        }

        $activityLogger->log($message);

        // Build SIEM-friendly log entry
        $logData = array_merge(
            [
                'category' => $category,
                'event' => $event,
                'level' => 'warning',
            ],
            self::getRequestContext(),
            $context
        );

        Log::warning("APPLOG - {$message}", $logData);
    }
}
