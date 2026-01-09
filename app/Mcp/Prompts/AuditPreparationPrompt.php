<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class AuditPreparationPrompt extends Prompt
{
    /**
     * The prompt's name.
     */
    protected string $name = 'audit-preparation';

    /**
     * The prompt's title.
     */
    protected string $title = 'Audit Preparation';

    /**
     * The prompt's description.
     */
    protected string $description = 'Prepare for an upcoming compliance audit by reviewing controls, gathering evidence, and identifying gaps that need attention.';

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'audit_type',
                description: 'The type of audit to prepare for (e.g., "SOC 2 Type II", "ISO 27001", "PCI-DSS", "HIPAA")',
                required: true,
            ),
            new Argument(
                name: 'audit_id',
                description: 'Optional: The ID of an existing audit in OpenGRC to review',
                required: false,
            ),
            new Argument(
                name: 'focus_areas',
                description: 'Specific areas of concern or focus (e.g., "access management, change management")',
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
            'audit_type' => 'required|string|max:100',
            'audit_id' => 'nullable|integer',
            'focus_areas' => 'nullable|string|max:200',
        ]);

        $auditType = $validated['audit_type'];
        $auditId = $validated['audit_id'] ?? null;
        $focusAreas = $validated['focus_areas'] ?? null;

        $auditContext = $auditId
            ? "Use ManageAudit with action=\"get\" and id={$auditId} to review the existing audit's status and items."
            : 'Use ManageAudit with action="list" to see if there are existing audits for this framework.';

        $focusContext = $focusAreas
            ? "Pay special attention to these areas: {$focusAreas}"
            : 'Review all control domains comprehensively';

        $systemMessage = <<<PROMPT
You are a compliance audit expert helping prepare for a {$auditType} audit.

First, gather comprehensive data from OpenGRC:

1. **Framework Review**:
   - Use ManageStandard with action="list" to find the relevant standard/framework
   - Use ManageControl with action="list" to get the controls for that standard

2. **Current State Assessment**:
   - Use ManageImplementation with action="list" to see control implementations
   - {$auditContext}

3. **Supporting Documentation**:
   - Use ManagePolicy with action="list" to review relevant policies
   - Use ManageRisk with action="list" to understand documented risks

4. **Taxonomy Reference**:
   - Read `opengrc://taxonomy/audit-status` for valid audit statuses
   - Read `opengrc://taxonomy/implementation-status` for implementation statuses

{$focusContext}

Produce an audit preparation report with:

## Audit Readiness Summary
- Overall readiness score (percentage)
- Key strengths
- Critical gaps requiring immediate attention

## Control-by-Control Readiness

For each control domain:
### [Domain Name]
| Control | Status | Evidence | Gaps | Priority |
|---------|--------|----------|------|----------|
| Control ID | Ready/Partial/Not Ready | Evidence types available | What's missing | High/Medium/Low |

## Evidence Checklist

List required evidence for each control category:
- [ ] Access control logs (last 12 months)
- [ ] Security awareness training records
- [ ] Incident response procedures
- [ ] Change management tickets
- etc.

## Gap Remediation Plan

For each identified gap:
1. **Gap Description**: What's missing or inadequate
2. **Risk Level**: Impact on audit outcome
3. **Remediation Steps**: Specific actions to address
4. **Owner**: Who should be responsible
5. **Timeline**: Suggested completion date before audit

## Interview Preparation

- Key personnel who may be interviewed
- Topics to review with each person
- Sample questions auditors may ask

## Pre-Audit Checklist

- [ ] All policies reviewed and current
- [ ] Evidence organized and accessible
- [ ] Control owners briefed
- [ ] Known gaps documented with remediation plans
- [ ] Audit logistics arranged

Highlight any critical items that could result in audit findings if not addressed.
PROMPT;

        $userMessage = "Please help me prepare for our upcoming {$auditType} audit.";

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }
}
