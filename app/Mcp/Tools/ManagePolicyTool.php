<?php

namespace App\Mcp\Tools;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;

/**
 * Tool for managing Policy entities.
 */
class ManagePolicyTool extends BaseManageEntityTool
{
    protected string $name = 'ManagePolicy';

    protected string $description = 'Manages Policy entities (security and compliance policies). Use action="create" with data, action="update" with id and data, or action="delete" with id and confirm=true. Codes are auto-generated (POL-001) if not provided.';

    protected function entityType(): string
    {
        return 'policy';
    }

    protected function shouldAutoGenerateCode(array $data): bool
    {
        return empty($data['code']);
    }

    protected function getCodePrefix(): string
    {
        return 'POL';
    }

    protected function prepareCreateData(array $data): array
    {
        // Set default status to Draft if not provided
        if (! isset($data['status_id'])) {
            $draftStatus = Taxonomy::where('type', 'policy-status')
                ->where('name', 'Draft')
                ->first();
            if ($draftStatus) {
                $data['status_id'] = $draftStatus->id;
            }
        }

        return $data;
    }
}
