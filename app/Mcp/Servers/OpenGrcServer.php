<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\AuditPreparationPrompt;
use App\Mcp\Prompts\ComplianceSummaryPrompt;
use App\Mcp\Prompts\GapAnalysisPrompt;
use App\Mcp\Prompts\PolicyDraftPrompt;
use App\Mcp\Prompts\RiskAssessmentPrompt;
use App\Mcp\Tools\ManageApplicationTool;
use App\Mcp\Tools\ManageAssetTool;
use App\Mcp\Tools\ManageAuditItemTool;
use App\Mcp\Tools\ManageAuditTool;
use App\Mcp\Tools\ManageChecklistTemplateTool;
use App\Mcp\Tools\ManageChecklistTool;
use App\Mcp\Tools\ManageControlTool;
use App\Mcp\Tools\ManageImplementationTool;
use App\Mcp\Tools\ManagePolicyTool;
use App\Mcp\Tools\ManageProgramTool;
use App\Mcp\Tools\ManageRiskTool;
use App\Mcp\Tools\ManageStandardTool;
use App\Mcp\Tools\ManageTaxonomyTool;
use App\Mcp\Tools\ManageVendorTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

class OpenGrcServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'OpenGRC MCP Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '3.0.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # OpenGRC MCP Server

        GRC platform tools. All Manage* tools support: list, get, create, update, delete actions.

        ## Tools
        - ManageApplication, ManageAsset, ManageAudit, ManageAuditItem
        - ManageChecklist (with approve action), ManageChecklistTemplate
        - ManageControl, ManageImplementation, ManagePolicy, ManageProgram
        - ManageRisk, ManageStandard, ManageTaxonomy, ManageVendor

        ## Taxonomies
        Entities use taxonomy IDs (department_id, scope_id, status_id, etc.). Look up IDs first:
        - `{"action": "list_types"}` - see taxonomy types
        - `{"action": "list_terms", "type": "department"}` - see terms
        - `{"action": "get", "type": "department", "name": "IT"}` - get ID

        ## Actions
        - List: `{"action": "list"}` or `{"action": "list", "page": 2}`
        - Get: `{"action": "get", "id": 1}`
        - Create: `{"action": "create", "data": {...}}`
        - Update: `{"action": "update", "id": 1, "data": {...}}`
        - Delete: `{"action": "delete", "id": 1, "confirm": true}`

        Text fields support HTML (`<p>`, `<ul>`, `<strong>`). Policy codes auto-generate.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        ManageApplicationTool::class,
        ManageAssetTool::class,
        ManageAuditTool::class,
        ManageAuditItemTool::class,
        ManageChecklistTool::class,
        ManageChecklistTemplateTool::class,
        ManageControlTool::class,
        ManageImplementationTool::class,
        ManagePolicyTool::class,
        ManageProgramTool::class,
        ManageRiskTool::class,
        ManageStandardTool::class,
        ManageTaxonomyTool::class,
        ManageVendorTool::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        GapAnalysisPrompt::class,
        RiskAssessmentPrompt::class,
        PolicyDraftPrompt::class,
        AuditPreparationPrompt::class,
        ComplianceSummaryPrompt::class,
    ];
}
