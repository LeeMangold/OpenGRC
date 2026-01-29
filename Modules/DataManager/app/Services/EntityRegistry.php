<?php

namespace Modules\DataManager\Services;

use App\Models\Application;
use App\Models\Asset;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\DataRequest;
use App\Models\FileAttachment;
use App\Models\Implementation;
use App\Models\Policy;
use App\Models\PolicyException;
use App\Models\Program;
use App\Models\Risk;
use App\Models\Standard;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Models\Vendor;
use App\Models\VendorDocument;

class EntityRegistry
{
    /**
     * All registered entities for import/export.
     *
     * @var array<string, array{model: class-string, group: string, label: string}>
     */
    protected array $entities = [];

    public function __construct()
    {
        $this->registerEntities();
    }

    /**
     * Register all importable/exportable entities.
     */
    protected function registerEntities(): void
    {
        // Core GRC
        $this->register('standards', Standard::class, 'Core GRC');
        $this->register('controls', Control::class, 'Core GRC');
        $this->register('implementations', Implementation::class, 'Core GRC');
        $this->register('policies', Policy::class, 'Core GRC');
        $this->register('audits', Audit::class, 'Core GRC');
        $this->register('audit_items', AuditItem::class, 'Core GRC');
        $this->register('risks', Risk::class, 'Core GRC');
        $this->register('programs', Program::class, 'Core GRC');

        // Third-Party Management
        $this->register('vendors', Vendor::class, 'Third-Party');
        $this->register('applications', Application::class, 'Third-Party');
        $this->register('assets', Asset::class, 'Third-Party');
        $this->register('vendor_documents', VendorDocument::class, 'Third-Party');

        // Survey & Assessment
        $this->register('surveys', Survey::class, 'Survey & Assessment');
        $this->register('survey_templates', SurveyTemplate::class, 'Survey & Assessment');
        $this->register('survey_questions', SurveyQuestion::class, 'Survey & Assessment');

        // Only register Checklist entities if they exist (may not be implemented yet)
        if (class_exists(\App\Models\Checklist::class)) {
            $this->register('checklists', \App\Models\Checklist::class, 'Survey & Assessment');
        }
        if (class_exists(\App\Models\ChecklistTemplate::class)) {
            $this->register('checklist_templates', \App\Models\ChecklistTemplate::class, 'Survey & Assessment');
        }

        // Data Management
        $this->register('data_requests', DataRequest::class, 'Data Management');
        $this->register('file_attachments', FileAttachment::class, 'Data Management');
        $this->register('policy_exceptions', PolicyException::class, 'Data Management');
    }

    /**
     * Register a single entity.
     *
     * @param  class-string  $modelClass
     */
    public function register(string $key, string $modelClass, string $group): void
    {
        $this->entities[$key] = [
            'key' => $key,
            'model' => $modelClass,
            'group' => $group,
            'label' => str($key)->replace('_', ' ')->title()->toString(),
        ];
    }

    /**
     * Get all registered entities.
     *
     * @return array<string, array{model: class-string, group: string, label: string}>
     */
    public function all(): array
    {
        return $this->entities;
    }

    /**
     * Get a single entity by key.
     */
    public function get(string $key): ?array
    {
        return $this->entities[$key] ?? null;
    }

    /**
     * Get the model class for an entity key.
     *
     * @return class-string|null
     */
    public function getModel(string $key): ?string
    {
        return $this->entities[$key]['model'] ?? null;
    }

    /**
     * Get entities grouped by their group name.
     *
     * @return array<string, array<string, array>>
     */
    public function grouped(): array
    {
        return collect($this->entities)
            ->groupBy('group')
            ->toArray();
    }

    /**
     * Get options formatted for a Filament Select component with option groups.
     *
     * @return array<string, array<string, string>>
     */
    public function getGroupedOptions(): array
    {
        return collect($this->entities)
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('label', 'key')->toArray())
            ->toArray();
    }

    /**
     * Get flat options for a simple select.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return collect($this->entities)
            ->pluck('label', 'key')
            ->toArray();
    }

    /**
     * Check if an entity key exists.
     */
    public function has(string $key): bool
    {
        return isset($this->entities[$key]);
    }

    /**
     * Get all entity keys.
     *
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->entities);
    }
}
