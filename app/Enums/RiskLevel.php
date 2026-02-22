<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RiskLevel: int implements HasLabel
{
    case VeryLow = 1;
    case Low = 2;
    case Moderate = 3;
    case High = 4;
    case VeryHigh = 5;

    public function getLabel(): string
    {
        return match ($this) {
            self::VeryLow => 'Very Low',
            self::Low => 'Low',
            self::Moderate => 'Moderate',
            self::High => 'High',
            self::VeryHigh => 'Very High',
        };
    }

    /**
     * Get options array for form fields and filters (string keys for compatibility).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [(string) $case->value => $case->getLabel()])
            ->toArray();
    }

    /**
     * Get the risk level enum case from a score (1-25).
     *
     * Score ranges:
     * - 1-4:   Very Low
     * - 5-8:   Low
     * - 9-12:  Moderate
     * - 13-17: High
     * - 18-25: Very High
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 18 => self::VeryHigh,
            $score >= 13 => self::High,
            $score >= 9 => self::Moderate,
            $score >= 5 => self::Low,
            default => self::VeryLow,
        };
    }

    /**
     * Get the risk level enum case from likelihood and impact values.
     */
    public static function fromLikelihoodAndImpact(int $likelihood, int $impact): self
    {
        return self::fromScore($likelihood * $impact);
    }

    /**
     * Format risk as "Label (score)" for display.
     */
    public static function formatRisk(int $likelihood, int $impact): string
    {
        $score = $likelihood * $impact;
        $label = self::fromScore($score)->getLabel();

        return "{$label} ({$score})";
    }

    /**
     * Get the Filament color name for a risk based on likelihood and impact.
     * Used by Filament badge columns for proper light/dark mode support.
     */
    public static function getFilamentColor(int $likelihood, int $impact): string
    {
        $score = $likelihood * $impact;

        return match (true) {
            $score >= 18 => 'danger',    // Very High risk
            $score >= 13 => 'warning',   // High risk
            $score >= 9 => 'info',       // Moderate risk
            $score >= 5 => 'primary',    // Low risk
            default => 'success',        // Very Low risk
        };
    }

    /**
     * Get the Tailwind background color class for a risk based on likelihood and impact.
     *
     * Note: These classes are referenced to prevent Tailwind from purging them:
     * bg-grcblue-200 bg-red-200 bg-orange-200 bg-yellow-200 bg-green-200
     * bg-grcblue-500 bg-red-500 bg-orange-500 bg-yellow-500 bg-green-500
     */
    public static function getColor(int $likelihood, int $impact, int $weight = 200): string
    {
        $score = $likelihood * $impact;

        return match (true) {
            $score >= 18 => "bg-red-{$weight}",      // Very High risk
            $score >= 13 => "bg-orange-{$weight}",   // High risk
            $score >= 9 => "bg-yellow-{$weight}",    // Moderate risk
            $score >= 5 => "bg-grcblue-{$weight}",   // Low risk
            default => "bg-green-{$weight}",         // Very Low risk
        };
    }
}
