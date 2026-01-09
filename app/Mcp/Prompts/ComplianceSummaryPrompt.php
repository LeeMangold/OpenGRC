<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class ComplianceSummaryPrompt extends Prompt
{
    /**
     * The prompt's name.
     */
    protected string $name = 'compliance-summary';

    /**
     * The prompt's title.
     */
    protected string $title = 'Compliance Status Summary';

    /**
     * The prompt's description.
     */
    protected string $description = 'Generate an executive-level compliance status summary for one or more frameworks, suitable for leadership reporting.';

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'frameworks',
                description: 'Compliance frameworks to summarize (e.g., "SOC 2", "all", "NIST CSF, ISO 27001")',
                required: true,
            ),
            new Argument(
                name: 'audience',
                description: 'Target audience for the summary (e.g., "board", "executives", "security team", "auditors")',
                required: false,
            ),
            new Argument(
                name: 'time_period',
                description: 'Time period for the summary (e.g., "Q4 2024", "annual", "current state")',
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
            'frameworks' => 'required|string|max:200',
            'audience' => 'nullable|string|max:50',
            'time_period' => 'nullable|string|max:50',
        ]);

        $frameworks = $validated['frameworks'];
        $audience = $validated['audience'] ?? 'executives';
        $timePeriod = $validated['time_period'] ?? 'current state';

        $audienceGuidance = match (strtolower($audience)) {
            'board' => 'Focus on strategic risk, business impact, and high-level metrics. Avoid technical jargon.',
            'executives' => 'Balance strategic overview with operational highlights. Include key decisions needed.',
            'security team' => 'Include technical details and specific action items.',
            'auditors' => 'Focus on evidence, control effectiveness, and documentation completeness.',
            default => 'Provide a balanced summary suitable for multiple audiences.',
        };

        $systemMessage = <<<PROMPT
You are a GRC reporting expert creating a compliance status summary for {$audience}.

{$audienceGuidance}

First, gather comprehensive data from OpenGRC:

1. Use ManageStandard with action="list" to identify frameworks matching: {$frameworks}
2. Use ManageControl with action="list" to get all controls and their statuses
3. Use ManageImplementation with action="list" to assess implementation coverage
4. Use ManageAudit with action="list" to review recent audit results
5. Use ManageRisk with action="list" to identify open risks affecting compliance
6. Use ManagePolicy with action="list" to assess policy coverage

Generate a {$timePeriod} compliance summary with:

## Executive Summary
- 2-3 sentence overview of compliance posture
- Key metric: Overall compliance percentage
- Trend indicator: Improving, stable, or declining

## Compliance Scorecard

| Framework | Controls | Implemented | Compliant | Score |
|-----------|----------|-------------|-----------|-------|
| [Name]    | Total #  | # (%)       | # (%)     | Grade |

## Key Achievements
- Significant improvements since last period
- Completed initiatives
- Successful audit outcomes

## Areas of Concern
- Critical gaps requiring attention
- Overdue items
- Emerging risks

## Risk Indicators
- Number of open high/critical risks
- Controls with exceptions
- Overdue remediation items

## Upcoming Milestones
- Scheduled audits
- Certification renewals
- Key compliance deadlines

## Recommendations
1. Prioritized actions for leadership consideration
2. Resource requirements
3. Timeline for improvements

## Appendix: Detailed Metrics
- Control implementation by domain
- Trend data (if available)
- Comparison to industry benchmarks (if applicable)

Keep the main summary concise (1-2 pages equivalent) with details in appendix. Use clear visualizations descriptions (charts, gauges) that could be created from the data.
PROMPT;

        $userMessage = "Please generate a compliance status summary for {$frameworks}.";

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }
}
