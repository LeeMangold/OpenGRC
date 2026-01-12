<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class PolicyDraftPrompt extends Prompt
{
    /**
     * The prompt's name.
     */
    protected string $name = 'policy-draft';

    /**
     * The prompt's title.
     */
    protected string $title = 'Draft Security Policy';

    /**
     * The prompt's description.
     */
    protected string $description = 'Draft a security or compliance policy document based on industry best practices and organizational requirements.';

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'policy_topic',
                description: 'The topic or type of policy to draft (e.g., "Acceptable Use", "Access Control", "Incident Response", "Data Classification")',
                required: true,
            ),
            new Argument(
                name: 'organization_context',
                description: 'Brief context about the organization (e.g., "healthcare SaaS startup", "financial services firm", "e-commerce company")',
                required: false,
            ),
            new Argument(
                name: 'compliance_frameworks',
                description: 'Compliance frameworks to align with (e.g., "SOC 2, HIPAA", "PCI-DSS", "GDPR")',
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
            'policy_topic' => 'required|string|max:100',
            'organization_context' => 'nullable|string|max:200',
            'compliance_frameworks' => 'nullable|string|max:200',
        ]);

        $topic = $validated['policy_topic'];
        $orgContext = $validated['organization_context'] ?? 'a technology company';
        $frameworks = $validated['compliance_frameworks'] ?? null;

        $frameworkContext = $frameworks
            ? "Ensure the policy aligns with these compliance frameworks: {$frameworks}"
            : 'Follow industry best practices and common compliance framework requirements';

        $systemMessage = <<<PROMPT
You are a GRC policy expert helping to draft a {$topic} policy for {$orgContext}.

First, gather context from OpenGRC:
1. Read `opengrc://schema/policy` to understand available policy fields
2. Read `opengrc://taxonomy/policy-status` for valid status values
3. Use ManagePolicy with action="list" to review existing policies for style consistency
4. Use ManageControl with action="list" to find relevant controls this policy should reference

{$frameworkContext}

Draft a comprehensive policy document with the following structure:

## Policy Metadata
- **Policy Name**: Clear, descriptive title
- **Policy Code**: Will be auto-generated (POL-XXX format)
- **Purpose**: One paragraph explaining why this policy exists

## Policy Body (HTML formatted)
Structure the policy content with these sections:

### 1. Purpose and Scope
- Why this policy exists
- Who and what it applies to
- Any exclusions

### 2. Policy Statements
- Clear, actionable requirements
- Use "shall", "must", "will" for mandatory items
- Use "should" for recommendations
- Number each statement for easy reference

### 3. Roles and Responsibilities
- Who is responsible for what
- Accountability matrix if applicable

### 4. Definitions
- Key terms used in the policy

### 5. Related Controls
- Reference relevant security controls by ID
- Explain how this policy supports control objectives

### 6. Compliance
- How compliance will be measured
- Consequences of non-compliance

### 7. Review and Maintenance
- Review frequency
- Change management process

Use proper HTML formatting:
- `<h2>` for main sections
- `<h3>` for subsections
- `<p>` for paragraphs
- `<ul>` and `<li>` for lists
- `<strong>` for emphasis

Finally, provide the data needed to create this policy in OpenGRC using ManagePolicy with action="create".
PROMPT;

        $userMessage = "Please draft a {$topic} policy for our organization.";

        return [
            Response::text($systemMessage)->asAssistant(),
            Response::text($userMessage),
        ];
    }
}
