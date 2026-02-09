<?php

namespace App\Http\Controllers\API;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends BaseApiController
{
    protected string $modelClass = Asset::class;

    protected string $resourceName = 'Assets';

    protected array $indexRelations = ['assignedToUser'];

    protected array $showRelations = ['assignedToUser', 'implementations'];

    protected array $searchableFields = ['name', 'description', 'asset_tag'];

    protected array $sortableFields = ['id', 'name', 'asset_type', 'created_at', 'updated_at'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'asset_tag' => 'nullable|string|max:255',
            'asset_type' => 'nullable|string|max:255',
            'asset_owner_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string',
        ]);
    }

    protected function validateUpdate(Request $request, $resource): array
    {
        return $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'asset_tag' => 'nullable|string|max:255',
            'asset_type' => 'nullable|string|max:255',
            'asset_owner_id' => 'nullable|exists:users,id',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string',
        ]);
    }
}
