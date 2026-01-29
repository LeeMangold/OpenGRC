<?php

namespace Modules\DataManager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ImportService
{
    public function __construct(
        protected EntityRegistry $entityRegistry,
        protected SchemaInspector $schemaInspector,
        protected EnumResolver $enumResolver,
        protected RelationshipResolver $relationshipResolver
    ) {}

    /**
     * Parse a CSV file and return headers and preview data.
     *
     * @return array{headers: array<string>, preview: array<array>, total_rows: int}
     */
    public function parseFile(string $filePath): array
    {
        $reader = Reader::createFromPath($filePath);
        $reader->setHeaderOffset(0);

        return [
            'headers' => $reader->getHeader(),
            'preview' => array_slice(iterator_to_array($reader->getRecords()), 0, 10),
            'total_rows' => $reader->count(),
        ];
    }

    /**
     * Auto-map CSV headers to database fields.
     *
     * @param  array<string>  $csvHeaders
     * @param  array<string, array>  $dbFields
     * @return array<int, string|null> CSV index => database field name
     */
    public function autoMapColumns(array $csvHeaders, array $dbFields): array
    {
        $mapping = [];

        foreach ($csvHeaders as $index => $header) {
            $mapping[$index] = $this->findMatchingField($header, $dbFields);
        }

        return $mapping;
    }

    /**
     * Find a matching database field for a CSV header.
     *
     * @param  array<string, array>  $dbFields
     */
    protected function findMatchingField(string $header, array $dbFields): ?string
    {
        $normalizedHeader = $this->normalizeFieldName($header);

        foreach ($dbFields as $fieldName => $fieldConfig) {
            // Exact match on field name
            if ($fieldName === $header) {
                return $fieldName;
            }

            // Normalized match
            if ($this->normalizeFieldName($fieldName) === $normalizedHeader) {
                return $fieldName;
            }

            // Match on label
            $normalizedLabel = $this->normalizeFieldName($fieldConfig['label'] ?? '');
            if ($normalizedLabel === $normalizedHeader) {
                return $fieldName;
            }
        }

        return null;
    }

    /**
     * Normalize a field name for comparison.
     */
    protected function normalizeFieldName(string $name): string
    {
        return strtolower(str_replace([' ', '-', '_'], '', $name));
    }

    /**
     * Validate column mapping has all required fields.
     *
     * @param  array<int, string|null>  $mapping
     * @param  array<string, array>  $requiredFields
     * @return array<string> Array of error messages
     */
    public function validateMapping(array $mapping, array $requiredFields): array
    {
        $errors = [];
        $mappedFields = array_values(array_filter($mapping));

        foreach ($requiredFields as $fieldName => $fieldConfig) {
            if (! in_array($fieldName, $mappedFields)) {
                $errors[] = "Required field '{$fieldConfig['label']}' ({$fieldName}) is not mapped.";
            }
        }

        return $errors;
    }

    /**
     * Import records from a CSV file.
     *
     * @param  array<int, string|null>  $columnMapping
     * @param  callable|null  $progressCallback  function(int $processed, int $total)
     */
    public function import(
        string $entityKey,
        string $filePath,
        array $columnMapping,
        ?callable $progressCallback = null
    ): ImportResult {
        $modelClass = $this->entityRegistry->getModel($entityKey);

        if (! $modelClass) {
            throw new \InvalidArgumentException("Unknown entity: {$entityKey}");
        }

        $fields = $this->schemaInspector->getFields($modelClass);
        $relationships = $this->schemaInspector->getManyToManyRelationships($modelClass);

        $reader = Reader::createFromPath($filePath);
        $reader->setHeaderOffset(0);

        $result = new ImportResult;
        $total = $reader->count();
        $processed = 0;

        DB::beginTransaction();

        try {
            foreach ($reader->getRecords() as $rowIndex => $row) {
                try {
                    $data = $this->mapRowToData($row, $columnMapping, $fields);
                    $relationshipData = $this->extractRelationshipData($row, $columnMapping, $relationships);

                    $this->validateRow($data, $fields);

                    $record = $this->upsertRecord($modelClass, $data, $fields);
                    $this->syncRelationships($record, $relationshipData);

                    $result->addSuccess($rowIndex, (int) $record->getKey());
                } catch (\Exception $e) {
                    $result->addError($rowIndex, $e->getMessage());
                }

                $processed++;
                if ($progressCallback) {
                    $progressCallback($processed, $total);
                }
            }

            // Check error threshold (rollback if > 10% errors)
            $errorRate = $total > 0 ? ($result->errorCount() / $total) : 0;
            if ($errorRate > 0.1 && $result->errorCount() > 0) {
                DB::rollBack();
                $result->setRolledBack(true);
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result->setCriticalError($e->getMessage());
        }

        return $result;
    }

    /**
     * Map a CSV row to model data.
     *
     * @param  array<string, string>  $row
     * @param  array<int, string|null>  $mapping
     * @param  array<string, array>  $fields
     * @return array<string, mixed>
     */
    protected function mapRowToData(array $row, array $mapping, array $fields): array
    {
        $data = [];
        $csvHeaders = array_keys($row);

        foreach ($mapping as $csvIndex => $dbField) {
            if ($dbField === null || ! isset($fields[$dbField])) {
                continue;
            }

            $csvHeader = $csvHeaders[$csvIndex] ?? null;
            if ($csvHeader === null) {
                continue;
            }

            $rawValue = $row[$csvHeader] ?? null;
            $field = $fields[$dbField];

            $data[$dbField] = $this->convertValue($rawValue, $field);
        }

        return $data;
    }

    /**
     * Extract many-to-many relationship data from a row.
     *
     * @param  array<string, string>  $row
     * @param  array<int, string|null>  $mapping
     * @param  array<string, array>  $relationships
     * @return array<string, array<int>>
     */
    protected function extractRelationshipData(array $row, array $mapping, array $relationships): array
    {
        $data = [];

        foreach ($relationships as $relationName => $relationConfig) {
            $columnName = $relationName.'_ids';

            // Find if this relationship is in the mapping
            foreach ($row as $header => $value) {
                if ($this->normalizeFieldName($header) === $this->normalizeFieldName($columnName)) {
                    // Parse comma-separated IDs
                    $data[$relationName] = $this->relationshipResolver->resolveManyToManyByIds(
                        $relationConfig['model'] ?? '',
                        $value
                    );

                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Convert a raw value to the appropriate type.
     *
     * @param  array<string, mixed>  $field
     */
    protected function convertValue(mixed $rawValue, array $field): mixed
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        $rawValue = trim((string) $rawValue);

        return match ($field['field_type']) {
            'enum' => $this->convertEnum($rawValue, $field['enum_class']),
            'boolean' => $this->convertBoolean($rawValue),
            'integer' => (int) $rawValue,
            'decimal' => (float) $rawValue,
            'date' => $this->convertDate($rawValue),
            'datetime' => $this->convertDateTime($rawValue),
            'json' => $this->convertJson($rawValue),
            default => $rawValue,
        };
    }

    /**
     * Convert string to enum value.
     */
    protected function convertEnum(string $value, ?string $enumClass): mixed
    {
        if (! $enumClass) {
            return $value;
        }

        $resolved = $this->enumResolver->resolve($enumClass, $value);

        if ($resolved === null && $value !== '') {
            throw new \InvalidArgumentException(
                $this->enumResolver->getValidationError($enumClass, $value)
            );
        }

        return $resolved;
    }

    /**
     * Convert string to boolean.
     */
    protected function convertBoolean(string $value): bool
    {
        $trueValues = ['true', '1', 'yes', 'y', 'on'];
        $falseValues = ['false', '0', 'no', 'n', 'off'];

        $lower = strtolower($value);

        if (in_array($lower, $trueValues)) {
            return true;
        }

        if (in_array($lower, $falseValues)) {
            return false;
        }

        throw new \InvalidArgumentException("'{$value}' is not a valid boolean value.");
    }

    /**
     * Convert string to date.
     */
    protected function convertDate(string $value): ?\Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($value)->startOfDay();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("'{$value}' is not a valid date.");
        }
    }

    /**
     * Convert string to datetime.
     */
    protected function convertDateTime(string $value): ?\Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("'{$value}' is not a valid datetime.");
        }
    }

    /**
     * Convert string to JSON/array.
     */
    protected function convertJson(string $value): mixed
    {
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("'{$value}' is not valid JSON.");
        }

        return $decoded;
    }

    /**
     * Validate row data.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, array>  $fields
     */
    protected function validateRow(array $data, array $fields): void
    {
        foreach ($fields as $fieldName => $field) {
            if ($field['required'] && ! $field['is_primary'] && ! $field['is_timestamp']) {
                $value = $data[$fieldName] ?? null;
                if ($value === null || $value === '') {
                    throw new \InvalidArgumentException("Required field '{$field['label']}' is missing or empty.");
                }
            }
        }
    }

    /**
     * Upsert a record (update or create).
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $data
     * @param  array<string, array>  $fields
     */
    protected function upsertRecord(string $modelClass, array $data, array $fields): Model
    {
        // Find unique identifier fields for upsert
        $uniqueFields = $this->getUniqueFields($modelClass, $data);

        if (! empty($uniqueFields)) {
            return $modelClass::updateOrCreate($uniqueFields, $data);
        }

        return $modelClass::create($data);
    }

    /**
     * Get unique field values for upsert lookup.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getUniqueFields(string $modelClass, array $data): array
    {
        // Common unique identifiers in priority order
        $uniqueCandidates = ['code', 'email', 'name'];

        foreach ($uniqueCandidates as $field) {
            $value = $data[$field] ?? null;
            if ($value !== null && $value !== '') {
                return [$field => $value];
            }
        }

        // If 'id' is provided, use it for update
        $id = $data['id'] ?? null;
        if ($id !== null) {
            return ['id' => $id];
        }

        return [];
    }

    /**
     * Sync many-to-many relationships.
     *
     * @param  array<string, array<int>>  $relationshipData
     */
    protected function syncRelationships(Model $record, array $relationshipData): void
    {
        foreach ($relationshipData as $relationName => $ids) {
            if (method_exists($record, $relationName)) {
                $record->{$relationName}()->sync($ids);
            }
        }
    }
}

/**
 * Import result tracking class.
 */
class ImportResult
{
    protected array $successes = [];

    protected array $errors = [];

    protected bool $rolledBack = false;

    protected ?string $criticalError = null;

    public function addSuccess(int $rowIndex, int $recordId): void
    {
        $this->successes[$rowIndex] = $recordId;
    }

    public function addError(int $rowIndex, string $message): void
    {
        $this->errors[$rowIndex] = $message;
    }

    public function setRolledBack(bool $rolledBack): void
    {
        $this->rolledBack = $rolledBack;
    }

    public function setCriticalError(string $error): void
    {
        $this->criticalError = $error;
    }

    public function successCount(): int
    {
        return count($this->successes);
    }

    public function errorCount(): int
    {
        return count($this->errors);
    }

    public function totalRows(): int
    {
        return $this->successCount() + $this->errorCount();
    }

    public function hasErrors(): bool
    {
        return $this->errorCount() > 0 || $this->criticalError !== null;
    }

    public function isRolledBack(): bool
    {
        return $this->rolledBack;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function getCriticalError(): ?string
    {
        return $this->criticalError;
    }

    public function toArray(): array
    {
        return [
            'success_count' => $this->successCount(),
            'error_count' => $this->errorCount(),
            'total_rows' => $this->totalRows(),
            'rolled_back' => $this->rolledBack,
            'critical_error' => $this->criticalError,
            'errors' => $this->errors,
        ];
    }
}
