<?php

namespace App\Mcp\Tools;

use App\Enums\SurveyStatus;
use App\Enums\SurveyType;
use App\Models\Survey;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Tool for managing Checklist entities.
 *
 * Checklists are internal self-assessments using the Survey model
 * with type = INTERNAL_CHECKLIST.
 */
class ManageChecklistTool extends Tool
{
    protected string $name = 'ManageChecklist';

    protected string $description = 'Manage checklists: list, get, create, update, delete, approve.';

    /**
     * Map of MCP actions to permission action names.
     */
    protected const ACTION_PERMISSION_MAP = [
        'list' => 'List',
        'get' => 'Read',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'approve' => 'Update',
    ];

    protected function getPermissionName(string $action): string
    {
        return "{$action} Checklists";
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
                'error' => "You do not have permission to {$action} checklists.",
                'required_permission' => $permission,
            ], JSON_PRETTY_PRINT));
        }

        return null;
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => 'required|string|in:list,get,create,update,delete,approve',
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
            'approve' => $this->handleApprove($validated, $request),
        };
    }

    protected function handleList(array $validated): Response
    {
        $query = Survey::checklists()
            ->with(['template', 'assignedTo', 'approvedBy'])
            ->orderBy('created_at', 'desc');

        $page = $validated['page'] ?? 1;
        $results = $query->paginate(50, ['*'], 'page', $page);

        $items = $results->map(fn ($item) => $this->formatListItem($item))->toArray();

        return Response::text(json_encode([
            'success' => true,
            'action' => 'list',
            'type' => 'checklist',
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'checklists' => $items,
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

        $checklist = Survey::checklists()
            ->with(['template', 'template.questions', 'answers', 'assignedTo', 'createdBy', 'approvedBy'])
            ->find($validated['id']);

        if (! $checklist) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        return Response::text(json_encode([
            'success' => true,
            'action' => 'get',
            'type' => 'checklist',
            'checklist' => $this->formatDetailItem($checklist),
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
                'data.survey_template_id' => 'required|exists:survey_templates,id',
                'data.title' => 'nullable|string|max:255',
                'data.description' => 'nullable|string',
                'data.assigned_to_id' => 'nullable|exists:users,id',
                'data.due_date' => 'nullable|date',
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
                $checklist = Survey::create([
                    'survey_template_id' => $data['survey_template_id'],
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'type' => SurveyType::INTERNAL_CHECKLIST,
                    'status' => SurveyStatus::DRAFT,
                    'assigned_to_id' => $data['assigned_to_id'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                    'created_by_id' => $request->user()->id,
                ]);

                $checklist->load(['template', 'assignedTo']);

                return Response::text(json_encode([
                    'success' => true,
                    'action' => 'created',
                    'message' => "Checklist '{$checklist->display_title}' created successfully.",
                    'checklist' => $this->formatListItem($checklist),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            });
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to create checklist: '.$e->getMessage(),
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

        $checklist = Survey::checklists()->find($validated['id']);

        if (! $checklist) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];
        $allowedFields = ['title', 'description', 'assigned_to_id', 'due_date', 'status'];
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($filteredData)) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'No valid fields to update. Allowed fields: '.implode(', ', $allowedFields),
            ], JSON_PRETTY_PRINT));
        }

        try {
            $checklist->update($filteredData);
            $checklist->load(['template', 'assignedTo']);

            return Response::text(json_encode([
                'success' => true,
                'action' => 'updated',
                'message' => "Checklist '{$checklist->display_title}' updated successfully.",
                'updated_fields' => array_keys($filteredData),
                'checklist' => $this->formatListItem($checklist),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to update checklist: '.$e->getMessage(),
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

        $checklist = Survey::checklists()->find($validated['id']);

        if (! $checklist) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $title = $checklist->display_title;
        $checklistId = $checklist->id;

        try {
            $checklist->delete();

            return Response::text(json_encode([
                'success' => true,
                'action' => 'deleted',
                'message' => "Checklist '{$title}' (ID: {$checklistId}) has been deleted.",
                'soft_deleted' => true,
                'restorable' => true,
            ], JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to delete checklist: '.$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    protected function handleApprove(array $validated, Request $request): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for approve action.',
            ], JSON_PRETTY_PRINT));
        }

        $checklist = Survey::checklists()->find($validated['id']);

        if (! $checklist) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Checklist with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        if ($checklist->status !== SurveyStatus::COMPLETED) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Checklist must be completed before approval.',
            ], JSON_PRETTY_PRINT));
        }

        if ($checklist->isApproved()) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Checklist is already approved.',
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'] ?? [];

        if (empty($data['approval_signature'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Approval signature is required. Provide data.approval_signature.',
            ], JSON_PRETTY_PRINT));
        }

        try {
            $checklist->update([
                'approved_by_id' => $request->user()->id,
                'approved_at' => now(),
                'approval_signature' => $data['approval_signature'],
                'approval_notes' => $data['approval_notes'] ?? null,
            ]);

            $checklist->load(['template', 'assignedTo', 'approvedBy']);

            return Response::text(json_encode([
                'success' => true,
                'action' => 'approved',
                'message' => "Checklist '{$checklist->display_title}' approved successfully.",
                'checklist' => $this->formatListItem($checklist),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Failed to approve checklist: '.$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    protected function formatListItem(Survey $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->display_title,
            'template' => $item->template ? [
                'id' => $item->template->id,
                'title' => $item->template->title,
            ] : null,
            'status' => $item->status?->value,
            'progress' => $item->progress,
            'assigned_to' => $item->assignedTo ? [
                'id' => $item->assignedTo->id,
                'name' => $item->assignedTo->name,
            ] : null,
            'due_date' => $item->due_date?->format('Y-m-d'),
            'completed_at' => $item->completed_at?->toIso8601String(),
            'is_approved' => $item->isApproved(),
            'approved_by' => $item->approvedBy ? [
                'id' => $item->approvedBy->id,
                'name' => $item->approvedBy->name,
            ] : null,
            'approved_at' => $item->approved_at?->toIso8601String(),
            'created_at' => $item->created_at->toIso8601String(),
            'url' => url('/app/checklists/'.$item->id),
        ];
    }

    protected function formatDetailItem(Survey $item): array
    {
        $output = $this->formatListItem($item);
        $output['description'] = $item->description;
        $output['approval_signature'] = $item->approval_signature;
        $output['approval_notes'] = $item->approval_notes;
        $output['created_by'] = $item->createdBy ? [
            'id' => $item->createdBy->id,
            'name' => $item->createdBy->name,
        ] : null;

        if ($item->template && $item->template->questions) {
            $output['questions'] = $item->template->questions->map(function ($q) use ($item) {
                $answer = $item->answers->firstWhere('survey_question_id', $q->id);

                return [
                    'id' => $q->id,
                    'question' => $q->question,
                    'type' => $q->response_type,
                    'is_required' => $q->is_required,
                    'answer' => $answer?->answer_value,
                ];
            })->toArray();
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
                ->enum(['list', 'get', 'create', 'update', 'delete', 'approve'])
                ->description('Action to perform.')
                ->required(),

            'id' => $schema->integer()
                ->description('Checklist ID (required for get/update/delete/approve).'),

            'page' => $schema->integer()
                ->description('Page number for list (default: 1, 50/page).'),

            'data' => $schema->object()
                ->description('Create: survey_template_id (req), title, assigned_to_id, due_date. Update: title, status. Approve: approval_signature (req).'),

            'confirm' => $schema->boolean()
                ->description('Set true for delete.'),
        ];
    }
}
