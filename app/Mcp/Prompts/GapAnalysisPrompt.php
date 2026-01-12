<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class GapAnalysisPrompt extends Prompt
{
    /**
     * The prompt's name.
     */
    protected string $name = 'gap-analysis';

    /**
     * The prompt's title.
     */
    protected string $title = 'Control Gap Analysis';

    /**
     * The prompt's description.
     */
    protected string $description = 'Analyze gaps between compliance framework controls and current implementations. Identifies missing or incomplete controls and provides remediation recommendations.';

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'standard',
                description: 'The compliance standard/framework to analyze (e.g., "NIST CSF", "SOC 2", "ISO 27001", "CMMC")',
                required: true,
            ),
            new Argument(
                name: 'focus_area',
                description: 'Optional focus area to narrow the analysis (e.g., "access control", "encryption", "incident response")',
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
            'standard' => 'required|string|max:100',
            'focus_area' => 'nullable|string|max:100',
        ]);

        $standard = $validated['standard'];
        $focusArea = $validated['focus_area'] ?? null;

        $focusContext = $focusArea
            ? "Focus specifically on controls related to: {$focusArea}"
            : 'Analyze all control domains comprehensively';

        $systemMessage = <<<PROMPT
You are a GRC (Governance, Risk, and Compliance) expert conducting a control gap analysis for the {$standard} framework.

Your task is to:
1. First, use the OpenGRC tools to gather data:
   - Use ManageStandard with action="list" to find the {$standard} framework
   - Use ManageControl with action="list" to get controls for that standard
   - Use ManageImplementation with action="list" to see current implementations

2. For each control, evaluate:
   - Implementation Status: Is it fully implemented, partially implemented, or not implemented?
   - Evidence Quality: Is there sufficient documentation and evidence?
   - Control Effectiveness: Based on implementation details, how effective is the control?

3. {$focusContext}

4. Produce a gap analysis report with:
   - Executive Summary: Overall compliance posture percentage
   - Critical Gaps: High-priority controls that are missing or inadequate
   - Partial Implementations: Controls that need enhancement
   - Remediation Roadmap: Prioritized recommendations with effort estimates
   - Quick Wins: Easy improvements that can be made immediately

Format your findings clearly with headers and bullet points. Be specific about which controls have gaps and what actions are needed.
PROMPT;

        $userMessage = "Please conduct a gap analysis for our {$standard} compliance program".
            ($focusArea ? ", focusing on {$focusArea}" : '').'.';

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }
}
