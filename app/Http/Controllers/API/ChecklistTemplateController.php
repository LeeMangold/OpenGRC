<?php

namespace App\Http\Controllers\API;

use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\SurveyTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistTemplateController extends BaseApiController
{
    protected string $modelClass = SurveyTemplate::class;

    protected string $resourceName = 'Checklist Templates';

    protected array $indexRelations = ['defaultAssignee', 'createdBy'];

    protected array $showRelations = ['questions', 'defaultAssignee', 'createdBy'];

    protected array $searchableFields = ['title', 'description'];

    protected array $sortableFields = ['id', 'title', 'status', 'recurrence_frequency', 'next_checklist_due_at', 'created_at', 'updated_at'];

    /**
     * Filter to only show checklist templates (INTERNAL_CHECKLIST type).
     */
    protected function applyIndexFilters($query, Request $request)
    {
        return $query->where('type', SurveyType::INTERNAL_CHECKLIST);
    }

    protected function validateStore(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'default_assignee_id' => 'nullable|exists:users,id',
            'recurrence_frequency' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_day_of_week' => 'nullable|integer|min:0|max:6',
            'recurrence_day_of_month' => 'nullable|integer|min:1|max:31',
        ]);

        // Set required fields
        $validated['type'] = SurveyType::INTERNAL_CHECKLIST;
        $validated['status'] = $validated['status'] ?? SurveyTemplateStatus::DRAFT;
        $validated['created_by_id'] = auth()->id();

        return $validated;
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|string',
            'default_assignee_id' => 'nullable|exists:users,id',
            'recurrence_frequency' => 'nullable|string',
            'recurrence_interval' => 'nullable|integer|min:1',
            'recurrence_day_of_week' => 'nullable|integer|min:0|max:6',
            'recurrence_day_of_month' => 'nullable|integer|min:1|max:31',
        ]);
    }

    /**
     * Override show to filter by type.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize('view', SurveyTemplate::class);

        $template = SurveyTemplate::checklists()
            ->with($this->showRelations)
            ->findOrFail($id);

        return response()->json([
            'data' => $template,
        ], JsonResponse::HTTP_OK);
    }
}
