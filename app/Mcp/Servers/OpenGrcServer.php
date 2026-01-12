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
use App\Mcp\Tools\ManageControlTool;
use App\Mcp\Tools\ManageImplementationTool;
use App\Mcp\Tools\ManagePolicyTool;
use App\Mcp\Tools\ManageProgramTool;
use App\Mcp\Tools\ManageRiskTool;
use App\Mcp\Tools\ManageStandardTool;
use App\Mcp\Tools\ManageVendorTool;
use Laravel\Mcp\Server;

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

        This MCP server provides tools and resources to interact with OpenGRC, a Governance, Risk, and Compliance (GRC) platform.

        ## Tools (Entity Management)

        Each entity type has a management tool with list, get, create, update, and delete actions:

        - `ManageApplication` - Manage applications/systems
        - `ManageAsset` - Manage IT assets
        - `ManageAudit` - Manage assessment/audit records
        - `ManageAuditItem` - Manage individual audit questions/items
        - `ManageControl` - Manage security controls within standards
        - `ManageImplementation` - Manage how controls are implemented
        - `ManagePolicy` - Manage security and compliance policies
        - `ManageProgram` - Manage organizational security programs
        - `ManageRisk` - Manage risk register entries
        - `ManageStandard` - Manage compliance frameworks (NIST, ISO, SOC2, etc.)
        - `ManageVendor` - Manage third-party vendors

        ### Tool Actions

        All Manage* tools support these actions:

        **List (paginated):**
        ```json
        {"action": "list"}
        {"action": "list", "page": 2}
        ```

        **Get (by ID):**
        ```json
        {"action": "get", "id": 1}
        ```

        **Create:**
        ```json
        {"action": "create", "data": {"name": "Security Policy", "purpose": "<p>Objective...</p>"}}
        ```

        **Update:**
        ```json
        {"action": "update", "id": 1, "data": {"name": "Updated Name"}}
        ```

        **Delete:**
        ```json
        {"action": "delete", "id": 1, "confirm": true}
        ```

        ## Common Workflows

        ### Creating a Policy
        1. Use ManageControl with action="list" to find controls to reference
        2. Use ManagePolicy with action="create" and data

        ### Reviewing Compliance
        1. Use ManageStandard with action="list" to see frameworks
        2. Use ManageControl with action="list" to list controls
        3. Use ManageControl with action="get" and id for control details

        ### Managing Audits
        1. Use ManageAudit with action="list" to see audits
        2. Use ManageAudit with action="get" and id for audit details
        3. Use ManageAuditItem with action="update" to update items

        ## HTML Formatting

        Text content fields (purpose, body, description, details) support HTML:
        - `<p>` for paragraphs
        - `<ul>` and `<li>` for lists
        - `<h2>`, `<h3>` for section headers
        - `<strong>` for emphasis

        ## Auto-Generated Codes

        Policies auto-generate codes (POL-001, POL-002) if not provided.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ManageApplicationTool::class,
        ManageAssetTool::class,
        ManageAuditTool::class,
        ManageAuditItemTool::class,
        ManageControlTool::class,
        ManageImplementationTool::class,
        ManagePolicyTool::class,
        ManageProgramTool::class,
        ManageRiskTool::class,
        ManageStandardTool::class,
        ManageVendorTool::class,
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        GapAnalysisPrompt::class,
        RiskAssessmentPrompt::class,
        PolicyDraftPrompt::class,
        AuditPreparationPrompt::class,
        ComplianceSummaryPrompt::class,
    ];
}
