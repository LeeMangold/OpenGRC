<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;

/**
 * A two-sided multiselect component with customizable action buttons.
 *
 * Displays available options on the left and selected options on the right,
 * with support for search, bulk actions, and custom filtering selectors.
 */
class ActionableMultiselectTwoSides extends Select
{
    protected string $view = 'filament.forms.components.actionable-multiselect-two-sides';

    /**
     * Label for the selectable (left) panel
     */
    public ?string $selectableLabel = null;

    /**
     * Label for the selected (right) panel
     */
    public ?string $selectedLabel = null;

    /**
     * Whether search is enabled
     */
    public bool $searchEnabled = false;

    /**
     * Stores full model data for filtering
     *
     * @var array<string|int, array<string, mixed>>|Closure
     */
    protected array|Closure $optionsMetadata = [];

    /**
     * Registered simple action buttons
     *
     * @var array<string, array{label: string, type: string, icon: string|null, color: string, count: int}>
     */
    protected array $actions = [];

    /**
     * Registered dropdown actions
     *
     * @var array<string, array{label: string, type: string, icon: string|null, color: string, options: array}>
     */
    protected array $dropdownActions = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Enable multiple selection
        $this->multiple();

        // Set default labels
        $this->selectableLabel = 'Available';
        $this->selectedLabel = 'Selected';
    }

    /**
     * Get options formatted for JavaScript consumption
     *
     * @return array<string, string>
     */
    public function getOptionsForJs(): array
    {
        return collect($this->getOptions())
            ->mapWithKeys(fn ($label, $value) => [(string) $value => $label])
            ->toArray();
    }

    // =========================================================================
    // LABEL CONFIGURATION
    // =========================================================================

    /**
     * Set the label for the selectable (left) panel
     */
    public function selectableLabel(string $label): static
    {
        $this->selectableLabel = $label;

        return $this;
    }

    /**
     * Get the label for the selectable panel
     */
    public function getSelectableLabel(): string
    {
        return $this->selectableLabel ?? 'Available';
    }

    /**
     * Set the label for the selected (right) panel
     */
    public function selectedLabel(string $label): static
    {
        $this->selectedLabel = $label;

        return $this;
    }

    /**
     * Get the label for the selected panel
     */
    public function getSelectedLabel(): string
    {
        return $this->selectedLabel ?? 'Selected';
    }

    // =========================================================================
    // SEARCH CONFIGURATION
    // =========================================================================

    /**
     * Enable search functionality
     */
    public function enableSearch(): static
    {
        $this->searchEnabled = true;

        return $this;
    }

    /**
     * Check if search is enabled
     */
    public function isSearchable(): bool
    {
        return $this->searchEnabled;
    }

    // =========================================================================
    // OPTIONS MANAGEMENT
    // =========================================================================

    /**
     * Get options that are available for selection (not yet selected)
     *
     * @return array<string|int, string>
     */
    public function getSelectableOptions(): array
    {
        return collect($this->getOptions())
            ->diff($this->getSelectedOptions())
            ->toArray();
    }

    /**
     * Get currently selected options
     *
     * @return array<string|int, string>
     */
    public function getSelectedOptions(): array
    {
        $state = $this->getState() ?? [];

        return collect($this->getOptions())
            ->filter(fn (string $label, string|int $value) => in_array($value, $state))
            ->toArray();
    }

    /**
     * Select a single option by value
     */
    public function selectOption(string $value): void
    {
        $state = $this->getState() ?? [];
        $state = array_unique(array_merge($state, [$value]));
        $this->state($state);
    }

    /**
     * Unselect a single option by value
     */
    public function unselectOption(string $value): void
    {
        $state = $this->getState() ?? [];
        $key = array_search($value, $state);
        if ($key !== false) {
            unset($state[$key]);
        }
        $this->state(array_values($state));
    }

    /**
     * Select all available options
     */
    public function selectAll(): void
    {
        $this->state(array_keys($this->getOptions()));
    }

    /**
     * Unselect all options
     */
    public function unselectAll(): void
    {
        $this->state([]);
    }

    // =========================================================================
    // METADATA FOR FILTERING
    // =========================================================================

    /**
     * Set metadata for options (full model data for filtering)
     *
     * @param  array<string|int, array<string, mixed>>|Closure  $metadata
     */
    public function optionsMetadata(array|Closure $metadata): static
    {
        $this->optionsMetadata = $metadata;

        return $this;
    }

    /**
     * Get metadata for all options
     *
     * @return array<string|int, array<string, mixed>>
     */
    public function getOptionsMetadata(): array
    {
        return $this->evaluate($this->optionsMetadata);
    }

    // =========================================================================
    // ACTION BUTTONS
    // =========================================================================

    /**
     * Add a simple action button
     *
     * Available types: 'random', 'randomUnassessed', 'oldest', 'oldestPreviouslyAssessed'
     *
     * @param  string  $name  Unique identifier for the action
     * @param  string  $label  Display label for the button
     * @param  string  $type  The type of selection algorithm (random, randomUnassessed, oldest, oldestPreviouslyAssessed)
     * @param  string|null  $icon  Heroicon name (e.g., 'heroicon-o-sparkles')
     * @param  string  $color  Tailwind color (e.g., 'primary', 'gray', 'danger')
     * @param  int  $count  Default count parameter
     */
    public function addAction(
        string $name,
        string $label,
        string $type = 'random',
        ?string $icon = null,
        string $color = 'gray',
        int $count = 10
    ): static {
        $this->actions[$name] = [
            'label' => $label,
            'type' => $type,
            'icon' => $icon,
            'color' => $color,
            'count' => $count,
        ];

        return $this;
    }

    /**
     * Add a dropdown action with sub-options
     *
     * Available types: 'random', 'randomUnassessed', 'oldest', 'oldestPreviouslyAssessed'
     *
     * @param  string  $name  Unique identifier for the action
     * @param  string  $label  Display label for the dropdown button
     * @param  string  $type  The type of selection algorithm (random, randomUnassessed, oldest, oldestPreviouslyAssessed)
     * @param  array  $options  Array of dropdown options, each with 'label' and 'count' keys
     * @param  string|null  $icon  Heroicon name
     * @param  string  $color  Tailwind color
     */
    public function addDropdownAction(
        string $name,
        string $label,
        string $type,
        array $options,
        ?string $icon = null,
        string $color = 'gray'
    ): static {
        $this->dropdownActions[$name] = [
            'label' => $label,
            'type' => $type,
            'icon' => $icon,
            'color' => $color,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Get all registered simple actions
     *
     * @return array<string, array{label: string, type: string, icon: string|null, color: string, count: int}>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get all registered dropdown actions
     *
     * @return array<string, array{label: string, type: string, icon: string|null, color: string, options: array}>
     */
    public function getDropdownActions(): array
    {
        return $this->dropdownActions;
    }
}
