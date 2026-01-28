<?php

namespace App\Mcp\Tools;

use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\SurveyTemplate;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Tool for managing Checklist Template entities.
 *
 * Checklist Templates are templates for internal checklists using the SurveyTemplate model
 * with type = INTERNAL_CHECKLIST.
 */
class ManageChecklistTemplateTool extends Tool
{
    protected string $name = 'ManageChecklistTemplate';

    protected string $description = 'Manage checklist templates: list, get, create, update, delete.';

    /**
     * Map of MCP actions to permission action names.
     */
    protected const ACTION_PERMISSION_MAP = [
        'list' => 'List',
        'get' => 'Read',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
    ];

    protected function getPermissionName(string $action): string
    {
        return "{$action} ChecklistTemplates";
    }

    protected function authorize(Request $request, string $action): ?Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Authentication required.',
            ], JSON_PRETTY_PRINT));
        }

        $permission = $this->getPermissionName($action);

        if (! $user->can($permission)) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "You do not have permission to {$action} checklist templates.",
                'required_permission' => $permission,
            ], JSON_PRETTY_PRINT));
        }

        return null;
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => 'required|string|in:list,get,create,update,delete',
            'id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'data' => 'nullable|array',
            'confirm' => 'nullable|boolean',
        ]);

        $action = $validated['action'];
        $permissionAction = self::ACTION_PERMISSION_MAP[$action];

        if ($error = $this->authorize($request, $permissionAction)) {
            return $error;
        }

        return match ($action) {
            'list' => $this->handleList($validated),
            'get' => $this->handleGet($validated),
            'create' => $this->handleCreate($validated, $request),
            'update' => $this->handleUpdate($validated),
            'delete' => $this->handleDelete($validated),
        };
    }

    protected function handleList(array $validated): Response
    {
        $query = SurveyTemplate::checklists()
            ->with(['defaultAssignee', 'createdBy'])
            ->withCount('surveys')
            ->orderBy('title');

        $page = $validated['page'] ?? 1;
        $results = $query->paginate(50, ['*'], 'page', $page);

        $items = $results->map(fn ($item) => $this->formatListItem($item))->toArray();

        return Response::text(json_encode([
            'success' => true,
            'action' => 'list',
            'type' => 'checklist_template',
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'checklist_templates' => $items,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function handleGet(array $validated): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for get action.',
            ], JSON_PRETTY_PRINT));
        }

        $template = SurveyTemplate::checklists()
            ->with(['questions', 'defaultAssignee', 'createdBy'])
            ->withCount('surveys')
            ->find($validated['id']);

        if (! $template) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist Template with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        return Response::text(json_encode([
            'success' => true,
            'action' => 'get',
            'type' => 'checklist_template',
            'checklist_template' => $this->formatDetailItem($template),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function handleCreate(array $validated, Request $request): Response
    {
        if (empty($validated['data'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Data is required for create action.',
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];

        try {
            validator($validated, [
                'data.title' => 'required|string|max:255',
                'data.description' => 'nullable|string',
                'data.status' => 'nullable|string',
                'data.default_assignee_id' => 'nullable|exists:users,id',
                'data.recurrence_frequency' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly',
                'data.recurrence_interval' => 'nullable|integer|min:1',
                'data.recurrence_day_of_week' => 'nullable|integer|min:0|max:6',
                'data.recurrence_day_of_month' => 'nullable|integer|min:1|max:31',
            ])->validate();
        } catch (ValidationException $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Validation failed',
                'validation_errors' => $e->errors(),
            ], JSON_PRETTY_PRINT));
        }

        try {
            return DB::transaction(function () use ($data, $request) {
                $template = SurveyTemplate::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'type' => SurveyType::INTERNAL_CHECKLIST,
                    'status' => $data['status'] ?? SurveyTemplateStatus::DRAFT,
                    'default_assignee_id' => $data['default_assignee_id'] ?? null,
                    'recurrence_frequency' => $data['recurrence_frequency'] ?? null,
                    'recurrence_interval' => $data['recurrence_interval'] ?? null,
                    'recurrence_day_of_week' => $data['recurrence_day_of_week'] ?? null,
                    'recurrence_day_of_month' => $data['recurrence_day_of_month'] ?? null,
                    'created_by_id' => $request->user()->id,
                ]);

                $template->load(['defaultAssignee']);

                return Response::text(json_encode([
                    'success' => true,
                    'action' => 'created',
                    'message' => "Checklist Template '{$template->title}' created successfully.",
                    'checklist_template' => $this->formatListItem($template),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            });
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to create checklist template: '.$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    protected function handleUpdate(array $validated): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for update action.',
            ], JSON_PRETTY_PRINT));
        }

        if (empty($validated['data'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Data is required for update action.',
            ], JSON_PRETTY_PRINT));
        }

        $template = SurveyTemplate::checklists()->find($validated['id']);

        if (! $template) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist Template with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];
        $allowedFields = [
            'title', 'description', 'status', 'default_assignee_id',
            'recurrence_frequency', 'recurrence_interval',
            'recurrence_day_of_week', 'recurrence_day_of_month',
        ];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($filteredData)) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'No valid fields to update. Allowed fields: '.implode(', ', $allowedFields),
            ], JSON_PRETTY_PRINT));
        }

        try {
            $template->update($filteredData);
            $template->load(['defaultAssignee']);

            return Response::text(json_encode([
                'success' => true,
                'action' => 'updated',
                'message' => "Checklist Template '{$template->title}' updated successfully.",
                'updated_fields' => array_keys($filteredData),
                'checklist_template' => $this->formatListItem($template),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to update checklist template: '.$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    protected function handleDelete(array $validated): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for delete action.',
            ], JSON_PRETTY_PRINT));
        }

        if (empty($validated['confirm']) || $validated['confirm'] !== true) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Delete operation not confirmed. Set confirm: true to proceed.',
            ], JSON_PRETTY_PRINT));
        }

        $template = SurveyTemplate::checklists()->find($validated['id']);

        if (! $template) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist Template with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $title = $template->title;
        $templateId = $template->id;

        try {
            $template->delete();

            return Response::text(json_encode([
                'success' => true,
                'action' => 'deleted',
                'message' => "Checklist Template '{$title}' (ID: {$templateId}) has been deleted.",
                'soft_deleted' => true,
                'restorable' => true,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to delete checklist template: '.$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    protected function formatListItem(SurveyTemplate $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'status' => $item->status?->value,
            'default_assignee' => $item->defaultAssignee ? [
                'id' => $item->defaultAssignee->id,
                'name' => $item->defaultAssignee->name,
            ] : null,
            'recurrence_frequency' => $item->recurrence_frequency?->value,
            'recurrence_interval' => $item->recurrence_interval,
            'next_checklist_due_at' => $item->next_checklist_due_at?->toIso8601String(),
            'checklists_count' => $item->surveys_count ?? 0,
            'is_locked' => $item->isLocked(),
            'created_at' => $item->created_at->toIso8601String(),
            'url' => url('/app/checklist-templates/'.$item->id),
        ];
    }

    protected function formatDetailItem(SurveyTemplate $item): array
    {
        $output = $this->formatListItem($item);
        $output['description'] = $item->description;
        $output['recurrence_day_of_week'] = $item->recurrence_day_of_week;
        $output['recurrence_day_of_month'] = $item->recurrence_day_of_month;
        $output['last_checklist_generated_at'] = $item->last_checklist_generated_at?->toIso8601String();
        $output['created_by'] = $item->createdBy ? [
            'id' => $item->createdBy->id,
            'name' => $item->createdBy->name,
        ] : null;

        if ($item->questions) {
            $output['questions'] = $item->questions->map(fn ($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->response_type,
                'is_required' => $q->is_required,
                'sort_order' => $q->sort_order,
            ])->toArray();
        }

        return $output;
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->enum(['list', 'get', 'create', 'update', 'delete'])
                ->description('Action to perform.')
                ->required(),

            'id' => $schema->integer()
                ->description('Template ID (required for get/update/delete).'),

            'page' => $schema->integer()
                ->description('Page number for list (default: 1, 50/page).'),

            'data' => $schema->object()
                ->description('Create/Update: title (req), description, status, default_assignee_id, recurrence_frequency, recurrence_interval.'),

            'confirm' => $schema->boolean()
                ->description('Set true for delete.'),
        ];
    }
}
