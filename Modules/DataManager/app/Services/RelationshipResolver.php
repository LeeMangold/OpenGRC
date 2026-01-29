<?php

namespace Modules\DataManager\Services;

use Illuminate\Database\Eloquent\Model;

class RelationshipResolver
{
    /**
     * Resolve a BelongsTo relationship by ID.
     *
     * @param  class-string<Model>  $modelClass
     * @return int|null The resolved ID or null if not found
     */
    public function resolveBelongsToById(string $modelClass, mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = (int) $id;

        // Verify the record exists
        if ($modelClass::query()->where('id', $id)->exists()) {
            return $id;
        }

        return null;
    }

    /**
     * Resolve a BelongsTo relationship by a lookup field.
     *
     * @param  class-string<Model>  $modelClass
     * @return int|null The resolved ID or null if not found
     */
    public function resolveBelongsTo(string $modelClass, string $lookupField, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $record = $modelClass::query()
            ->where($lookupField, $value)
            ->first();

        return $record ? (int) $record->getKey() : null;
    }

    /**
     * Resolve ManyToMany relationships from comma-separated IDs.
     *
     * @param  class-string<Model>  $modelClass
     * @return array<int> Array of valid IDs
     */
    public function resolveManyToManyByIds(string $modelClass, mixed $values, string $separator = ','): array
    {
        if ($values === null || $values === '') {
            return [];
        }

        // Handle if already an array
        if (is_array($values)) {
            $ids = array_map('intval', $values);
        } else {
            $ids = array_map('intval', array_map('trim', explode($separator, (string) $values)));
        }

        // Filter out zeros and invalid values
        $ids = array_filter($ids, fn ($id) => $id > 0);

        if (empty($ids)) {
            return [];
        }

        // Verify records exist and return only valid IDs
        return $modelClass::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Resolve ManyToMany relationships by a lookup field.
     *
     * @param  class-string<Model>  $modelClass
     * @return array<int> Array of valid IDs
     */
    public function resolveManyToMany(
        string $modelClass,
        string $lookupField,
        mixed $values,
        string $separator = ','
    ): array {
        if ($values === null || $values === '') {
            return [];
        }

        // Handle if already an array
        if (is_array($values)) {
            $lookupValues = array_map('trim', $values);
        } else {
            $lookupValues = array_map('trim', explode($separator, (string) $values));
        }

        // Filter out empty values
        $lookupValues = array_filter($lookupValues, fn ($v) => $v !== '');

        if (empty($lookupValues)) {
            return [];
        }

        return $modelClass::query()
            ->whereIn($lookupField, $lookupValues)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get display options for a relationship (for column mapping UI).
     *
     * @param  class-string<Model>  $modelClass
     * @return array<int, string> ID => Display value
     */
    public function getRelationshipOptions(
        string $modelClass,
        string $displayField = 'name',
        ?int $limit = 100
    ): array {
        $query = $modelClass::query()
            ->select(['id', $displayField])
            ->orderBy($displayField);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->mapWithKeys(fn ($record) => [
                $record->getKey() => "{$record->{$displayField}} (ID: {$record->getKey()})",
            ])
            ->toArray();
    }

    /**
     * Format relationship IDs for export as comma-separated string.
     *
     * @param  \Illuminate\Support\Collection|array  $relatedRecords
     */
    public function formatForExport($relatedRecords): string
    {
        if (empty($relatedRecords)) {
            return '';
        }

        $ids = collect($relatedRecords)->pluck('id')->toArray();

        return implode(',', $ids);
    }

    /**
     * Validate that all IDs exist in the target model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function validateIds(string $modelClass, array $ids): array
    {
        $validIds = $modelClass::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        $invalidIds = array_diff($ids, $validIds);

        return [
            'valid' => $validIds,
            'invalid' => $invalidIds,
            'is_valid' => empty($invalidIds),
        ];
    }

    /**
     * Get the foreign key column name for a relationship.
     */
    public function getForeignKeyColumn(string $relationshipName): string
    {
        return str($relationshipName)->snake()->singular()->append('_id')->toString();
    }
}
