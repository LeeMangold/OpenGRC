<?php

namespace App\Http\Controllers\API;

use App\Models\Risk;
use Illuminate\Http\Request;

class RiskController extends BaseApiController
{
    protected string $modelClass = Risk::class;

    protected string $resourceName = 'Risks';

    protected array $showRelations = ['implementations'];

    protected array $searchableFields = ['title', 'description', 'mitigation'];

    protected array $sortableFields = ['id', 'title', 'likelihood', 'impact', 'risk_level', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'mitigation' => 'nullable|string',
            'likelihood' => 'nullable|string',
            'impact' => 'nullable|string',
            'risk_level' => 'nullable|string',
            'status' => 'nullable|string',
            'risk_owner_id' => 'nullable|exists:users,id',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'mitigation' => 'nullable|string',
            'likelihood' => 'nullable|string',
            'impact' => 'nullable|string',
            'risk_level' => 'nullable|string',
            'status' => 'nullable|string',
            'risk_owner_id' => 'nullable|exists:users,id',
        ]);
    }
}
