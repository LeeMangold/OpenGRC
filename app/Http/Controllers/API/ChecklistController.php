<?php

namespace App\Http\Controllers\API;

use App\Enums\SurveyStatus;
use App\Enums\SurveyType;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends BaseApiController
{
    protected string $modelClass = Survey::class;

    protected string $resourceName = 'Checklists';

    protected array $indexRelations = ['template', 'assignedTo', 'approvedBy'];

    protected array $showRelations = ['template', 'template.questions', 'answers', 'assignedTo', 'createdBy', 'approvedBy'];

    protected array $searchableFields = ['title', 'description', 'template.title'];

    protected array $sortableFields = ['id', 'title', 'status', 'due_date', 'completed_at', 'approved_at', 'created_at', 'updated_at'];

    /**
     * Filter to only show checklists (INTERNAL_CHECKLIST type).
     */
    protected function applyIndexFilters($query, Request $request)
    {
        return $query->where('type', SurveyType::INTERNAL_CHECKLIST);
    }

    protected function validateStore(Request $request): array
    {
        $validated = $request->validate([
            'survey_template_id' => 'required|exists:survey_templates,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'assigned_to_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        // Set required fields
        $validated['type'] = SurveyType::INTERNAL_CHECKLIST;
        $validated['status'] = SurveyStatus::PENDING;
        $validated['created_by_id'] = auth()->id();

        return $validated;
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assigned_to_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|string',
        ]);
    }

    /**
     * Approve a checklist.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $checklist = Survey::checklists()->findOrFail($id);

        $this->authorize('update', $checklist);

        if ($checklist->status !== SurveyStatus::COMPLETED) {
            return response()->json([
                'message' => 'Checklist must be completed before approval',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($checklist->isApproved()) {
            return response()->json([
                'message' => 'Checklist is already approved',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'approval_signature' => 'required|string|max:255',
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        $checklist->update([
            'approved_by_id' => auth()->id(),
            'approved_at' => now(),
            'approval_signature' => $validated['approval_signature'],
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        $checklist->load($this->showRelations);

        return response()->json([
            'message' => 'Checklist approved successfully',
            'data' => $checklist,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Override show to filter by type.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize('view', Survey::class);

        $checklist = Survey::checklists()
            ->with($this->showRelations)
            ->findOrFail($id);

        return response()->json([
            'data' => $checklist,
        ], JsonResponse::HTTP_OK);
    }
}
