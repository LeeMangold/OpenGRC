<?php

namespace Modules\DataManager\Services;

use Filament\Support\Contracts\HasLabel;

class EnumResolver
{
    /**
     * Resolve a string value to an enum case.
     *
     * Resolution order:
     * 1. Exact value match
     * 2. Case-insensitive value match
     * 3. Label match (if enum implements HasLabel)
     * 4. Enum case name match
     *
     * @param  class-string  $enumClass
     */
    public function resolve(string $enumClass, ?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! enum_exists($enumClass)) {
            return null;
        }

        $cases = $enumClass::cases();
        $isBackedEnum = is_subclass_of($enumClass, \BackedEnum::class);

        // 1. Try exact value match (only for BackedEnum)
        if ($isBackedEnum) {
            foreach ($cases as $case) {
                /** @var \BackedEnum $case */
                if ($case->value === $value) {
                    return $case;
                }
            }
        }

        // 2. Try case-insensitive value match (only for BackedEnum)
        $lowerValue = strtolower(trim($value));
        if ($isBackedEnum) {
            foreach ($cases as $case) {
                /** @var \BackedEnum $case */
                if (strtolower((string) $case->value) === $lowerValue) {
                    return $case;
                }
            }
        }

        // 3. Try label match (if HasLabel interface is implemented)
        if (is_subclass_of($enumClass, HasLabel::class)) {
            foreach ($cases as $case) {
                /** @var HasLabel $case */
                $label = $case->getLabel();
                if ($label && strtolower($label) === $lowerValue) {
                    return $case;
                }
            }
        }

        // 4. Try case name match
        foreach ($cases as $case) {
            if (strtolower($case->name) === $lowerValue) {
                return $case;
            }
        }

        // 5. Try partial match on case name (e.g., "In Progress" matches "INPROGRESS")
        $normalizedValue = strtolower(str_replace([' ', '-', '_'], '', $value));
        foreach ($cases as $case) {
            $normalizedName = strtolower(str_replace(['_'], '', $case->name));
            if ($normalizedName === $normalizedValue) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get all possible values for an enum (for display in UI).
     *
     * @param  class-string  $enumClass
     * @return array<string, string> Key is the value, value is the display label
     */
    public function getOptions(string $enumClass): array
    {
        if (! enum_exists($enumClass)) {
            return [];
        }

        $options = [];
        $isBackedEnum = is_subclass_of($enumClass, \BackedEnum::class);

        foreach ($enumClass::cases() as $case) {
            $label = $case instanceof HasLabel ? $case->getLabel() : $case->name;
            /** @var \BackedEnum|\UnitEnum $case */
            $key = $isBackedEnum && $case instanceof \BackedEnum ? (string) $case->value : $case->name;
            $options[$key] = $label;
        }

        return $options;
    }

    /**
     * Get the display value for an enum case.
     */
    public function getDisplayValue(mixed $enumCase): string
    {
        if ($enumCase === null) {
            return '';
        }

        if ($enumCase instanceof HasLabel) {
            $label = $enumCase->getLabel();
            if ($label !== null) {
                return $label;
            }
        }

        if ($enumCase instanceof \BackedEnum) {
            return (string) $enumCase->value;
        }

        if ($enumCase instanceof \UnitEnum) {
            return $enumCase->name;
        }

        return '';
    }

    /**
     * Get the raw value from an enum case for export.
     */
    public function getExportValue(mixed $enumCase): string
    {
        if ($enumCase === null) {
            return '';
        }

        if ($enumCase instanceof \BackedEnum) {
            return (string) $enumCase->value;
        }

        if ($enumCase instanceof \UnitEnum) {
            return $enumCase->name;
        }

        return '';
    }

    /**
     * Validate that a value can be resolved to an enum case.
     *
     * @param  class-string  $enumClass
     */
    public function isValid(string $enumClass, ?string $value): bool
    {
        if ($value === null || $value === '') {
            return true; // Null is valid (field might be nullable)
        }

        return $this->resolve($enumClass, $value) !== null;
    }

    /**
     * Get validation error message for an invalid enum value.
     *
     * @param  class-string  $enumClass
     */
    public function getValidationError(string $enumClass, string $value): string
    {
        $validOptions = implode(', ', array_keys($this->getOptions($enumClass)));

        return "'{$value}' is not a valid option. Valid options are: {$validOptions}";
    }
}
