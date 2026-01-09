<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class RiskAssessmentPrompt extends Prompt
{
    /**
     * The prompt's name.
     */
    protected string $name = 'risk-assessment';

    /**
     * The prompt's title.
     */
    protected string $title = 'Risk Assessment';

    /**
     * The prompt's description.
     */
    protected string $description = 'Assess and document a security or compliance risk, including likelihood, impact, and recommended mitigations.';

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'risk_description',
                description: 'Description of the risk scenario to assess (e.g., "Unauthorized access to customer data through compromised credentials")',
                required: true,
            ),
            new Argument(
                name: 'asset_or_system',
                description: 'The asset, system, or process affected by this risk (e.g., "Customer database", "Payment processing system")',
                required: false,
            ),
        ];
    }

    /**
     * Handle the prompt request.
     *
     * @return array<int, \Laravel\Mcp\Response>
     */
    public function handle(Request $request): array
    {
        $validated = $request->validate([
            'risk_description' => 'required|string|max:500',
            'asset_or_system' => 'nullable|string|max:200',
        ]);

        $riskDescription = $validated['risk_description'];
        $asset = $validated['asset_or_system'] ?? 'the affected systems';

        $systemMessage = <<<PROMPT
You are a risk management expert helping to assess and document a security risk for a GRC program.

First, gather context from OpenGRC:
1. Read `opengrc://taxonomy/risk-likelihood` for valid likelihood values
2. Read `opengrc://taxonomy/risk-impact` for valid impact values
3. Read `opengrc://taxonomy/risk-status` for valid status values
4. Read `opengrc://schema/risk` to understand all available fields
5. Optionally use ManageControl with action="list" to find relevant mitigating controls

Then provide a comprehensive risk assessment with:

## Risk Identification
- **Risk Title**: A concise name for this risk
- **Risk Description**: Detailed explanation of the threat scenario
- **Affected Asset**: {$asset}
- **Risk Category**: (e.g., Operational, Compliance, Strategic, Financial, Reputational)

## Risk Analysis
- **Threat Sources**: Who or what could exploit this vulnerability
- **Vulnerabilities**: What weaknesses enable this risk
- **Likelihood**: Assessment with justification (use taxonomy values)
- **Impact**: Assessment with justification (use taxonomy values)
- **Inherent Risk Level**: Combined likelihood × impact rating

## Risk Evaluation
- **Existing Controls**: Current mitigations already in place
- **Control Effectiveness**: How well do existing controls reduce risk
- **Residual Risk Level**: Risk level after existing controls

## Risk Treatment
- **Recommended Response**: (Accept, Mitigate, Transfer, Avoid)
- **Proposed Mitigations**: Specific controls or actions to reduce risk
- **Implementation Priority**: Based on risk level and effort
- **Monitoring Requirements**: How to track this risk going forward

Finally, provide the data needed to create this risk in OpenGRC using ManageRisk with action="create".
PROMPT;

        $userMessage = "Please assess the following risk: {$riskDescription}";

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }
}
