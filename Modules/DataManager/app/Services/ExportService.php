<?php

namespace Modules\DataManager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use League\Csv\Writer;

class ExportService
{
    public function __construct(
        protected EntityRegistry $entityRegistry,
        protected SchemaInspector $schemaInspector,
        protected EnumResolver $enumResolver,
        protected RelationshipResolver $relationshipResolver
    ) {}

    /**
     * Export records to CSV string.
     *
     * @param  array<string>  $selectedFields
     * @param  array<string>  $selectedRelationships  Many-to-many relationship names
     */
    public function export(
        string $entityKey,
        array $selectedFields,
        array $selectedRelationships = [],
        bool $includeHeaders = true
    ): string {
        $modelClass = $this->entityRegistry->getModel($entityKey);

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entityKey}");
        }

        $fields = $this->schemaInspector->getFields($modelClass);
        $query = $modelClass::query();

        // Eager load selected many-to-many relationships
        if (! empty($selectedRelationships)) {
            $query->with($selectedRelationships);
        }

        $records = $query->get();

        $csv = Writer::createFromString();

        // Add headers
        if ($includeHeaders) {
            $headers = $this->buildHeaders($fields, $selectedFields, $selectedRelationships);
            $csv->insertOne($headers);
        }

        // Add data rows
        foreach ($records as $record) {
            $row = $this->buildRow($record, $fields, $selectedFields, $selectedRelationships);
            $csv->insertOne($row);
        }

        return $csv->toString();
    }

    /**
     * Export with chunking for large datasets.
     *
     * @param  array<string>  $selectedFields
     * @param  array<string>  $selectedRelationships
     */
    public function exportChunked(
        string $entityKey,
        array $selectedFields,
        array $selectedRelationships = [],
        ?string $filePath = null,
        int $chunkSize = 1000
    ): string {
        $modelClass = $this->entityRegistry->getModel($entityKey);

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entityKey}");
        }

        $fields = $this->schemaInspector->getFields($modelClass);

        // Create file or memory stream
        $filePath = $filePath ?? sys_get_temp_dir().'/export_'.uniqid().'.csv';
        $csv = Writer::createFromPath($filePath, 'w+');

        // Add headers
        $headers = $this->buildHeaders($fields, $selectedFields, $selectedRelationships);
        $csv->insertOne($headers);

        // Process in chunks
        $modelClass::query()
            ->with($selectedRelationships)
            ->chunk($chunkSize, function (Collection $records) use ($csv, $fields, $selectedFields, $selectedRelationships) {
                foreach ($records as $record) {
                    $row = $this->buildRow($record, $fields, $selectedFields, $selectedRelationships);
                    $csv->insertOne($row);
                }
            });

        return $filePath;
    }

    /**
     * Get record count for an entity.
     */
    public function getRecordCount(string $entityKey): int
    {
        $modelClass = $this->entityRegistry->getModel($entityKey);

        if (! $modelClass) {
            return 0;
        }

        return $modelClass::query()->count();
    }

    /**
     * Build CSV headers from selected fields.
     *
     * @param  array<string, array>  $fields
     * @param  array<string>  $selectedFields
     * @param  array<string>  $selectedRelationships
     * @return array<string>
     */
    protected function buildHeaders(array $fields, array $selectedFields, array $selectedRelationships): array
    {
        $headers = [];

        foreach ($selectedFields as $fieldName) {
            if (isset($fields[$fieldName])) {
                $headers[] = $fieldName;
            }
        }

        // Add relationship columns
        foreach ($selectedRelationships as $relationName) {
            $headers[] = $relationName.'_ids';
        }

        return $headers;
    }

    /**
     * Build a CSV row from a record.
     *
     * @param  array<string, array>  $fields
     * @param  array<string>  $selectedFields
     * @param  array<string>  $selectedRelationships
     * @return array<string>
     */
    protected function buildRow(Model $record, array $fields, array $selectedFields, array $selectedRelationships): array
    {
        $row = [];

        foreach ($selectedFields as $fieldName) {
            if (! isset($fields[$fieldName])) {
                $row[] = '';

                continue;
            }

            $field = $fields[$fieldName];
            $value = $record->{$fieldName};

            $row[] = $this->formatValue($value, $field);
        }

        // Add relationship data
        foreach ($selectedRelationships as $relationName) {
            $related = $record->{$relationName} ?? collect();
            $row[] = $this->relationshipResolver->formatForExport($related);
        }

        return $row;
    }

    /**
     * Format a value for CSV export.
     *
     * @param  array<string, mixed>  $field
     */
    protected function formatValue(mixed $value, array $field): string
    {
        if ($value === null) {
            return '';
        }

        // Handle enums
        if ($field['field_type'] === 'enum' && is_object($value)) {
            return $this->enumResolver->getExportValue($value);
        }

        // Handle booleans
        if ($field['field_type'] === 'boolean') {
            return $value ? 'true' : 'false';
        }

        // Handle dates
        if ($field['field_type'] === 'date' && $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Handle datetime
        if ($field['field_type'] === 'datetime' && $value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        // Handle arrays/JSON
        if ($field['field_type'] === 'json' && is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
