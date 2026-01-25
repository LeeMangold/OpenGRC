<?php

namespace App\Http\Controllers\API;

use App\Models\Policy;
use Illuminate\Http\Request;

class PolicyController extends BaseApiController
{
    protected string $modelClass = Policy::class;

    protected string $resourceName = 'Policies';

    protected array $indexRelations = ['status', 'scope', 'department', 'owner', 'creator', 'updater'];

    protected array $showRelations = ['status', 'scope', 'department', 'owner', 'creator', 'updater', 'controls', 'implementations', 'risks'];

    protected array $searchableFields = ['code', 'name', 'policy_scope', 'purpose', 'body'];

    protected array $sortableFields = ['id', 'code', 'name', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:255|unique:policies,code',
            'name' => 'required|string|max:255',
            'policy_scope' => 'nullable|string',
            'purpose' => 'nullable|string',
            'body' => 'nullable|string',
            'document_path' => 'nullable|string|max:255',
            'scope_id' => 'nullable|exists:taxonomies,id',
            'department_id' => 'nullable|exists:taxonomies,id',
            'status_id' => 'nullable|exists:taxonomies,id',
            'owner_id' => 'nullable|exists:users,id',
            'effective_date' => 'nullable|date',
            'retired_date' => 'nullable|date',
            'revision_history' => 'nullable|array',
            'revision_history.*.version' => 'required|string|max:255',
            'revision_history.*.date' => 'required|date',
            'revision_history.*.author' => 'required|string|max:255',
            'revision_history.*.changes' => 'required|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'code' => 'sometimes|string|max:255|unique:policies,code,'.$resource->id,
            'name' => 'sometimes|string|max:255',
            'policy_scope' => 'nullable|string',
            'purpose' => 'nullable|string',
            'body' => 'nullable|string',
            'document_path' => 'nullable|string|max:255',
            'scope_id' => 'nullable|exists:taxonomies,id',
            'department_id' => 'nullable|exists:taxonomies,id',
            'status_id' => 'nullable|exists:taxonomies,id',
            'owner_id' => 'nullable|exists:users,id',
            'effective_date' => 'nullable|date',
            'retired_date' => 'nullable|date',
            'revision_history' => 'nullable|array',
            'revision_history.*.version' => 'required|string|max:255',
            'revision_history.*.date' => 'required|date',
            'revision_history.*.author' => 'required|string|max:255',
            'revision_history.*.changes' => 'required|string',
        ]);
    }
}
