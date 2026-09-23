<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class VendorSurveyTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Skipping Vendor Survey Templates seeding.');

            return;
        }

        // Only add templates that are missing so re-running never overwrites
        // customised weights or resurrects a template someone deleted.
        if (! $this->templateExists('Vendor Security Survey')) {
            $this->seedVendorSecuritySurvey($user->id);
        }

        if (! $this->templateExists('Vendor Security Survey (Internal)')) {
            $this->seedVendorSecuritySurveyInternal($user->id);
        }
    }

    private function templateExists(string $title): bool
    {
        return SurveyTemplate::withTrashed()->where('title', $title)->exists();
    }

    private function seedVendorSecuritySurvey(int $userId): void
    {
        $template = SurveyTemplate::updateOrCreate(
            ['title' => 'Vendor Security Survey'],
            [
                'description' => '<p>This survey assesses the security posture of third-party vendors to help identify and manage risks associated with external partnerships. Please answer all questions honestly and completely.</p>',
                'status' => SurveyTemplateStatus::ACTIVE,
                'type' => SurveyType::VENDOR_ASSESSMENT,
                'created_by_id' => $userId,
            ]
        );

        $questions = [
            // Governance & Security Program (Questions 1-3)
            [
                'category' => 'Governance & Security Program',
                'question_text' => 'Does your organization maintain a formally documented information security program approved by executive leadership?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Governance & Security Program',
                'question_text' => 'Is information security oversight assigned to a dedicated role or function (e.g., CISO or security team)?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Governance & Security Program',
                'question_text' => 'Are security policies reviewed and updated at least annually?',
                'risk_weight' => 5,
            ],

            // Data Protection & Privacy (Questions 4-6)
            [
                'category' => 'Data Protection & Privacy',
                'question_text' => 'Will your organization store, process, or transmit our non-public data only as contractually authorized?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Data Protection & Privacy',
                'question_text' => 'Is all sensitive or confidential data encrypted both in transit and at rest?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Data Protection & Privacy',
                'question_text' => 'Is access to our data restricted to personnel with a legitimate business need?',
                'risk_weight' => 5,
            ],

            // Identity & Access Management (Questions 7-9)
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Is multi-factor authentication enforced for all administrative and privileged access?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Are user accounts promptly disabled or removed upon role change or termination?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Are access rights reviewed on a regular and documented basis?',
                'risk_weight' => 5,
            ],

            // Infrastructure & Operations Security (Questions 10-12)
            [
                'category' => 'Infrastructure & Operations Security',
                'question_text' => 'Are systems hosting our data protected by up-to-date endpoint security or equivalent controls?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Infrastructure & Operations Security',
                'question_text' => 'Are critical security patches applied within defined timeframes based on risk?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Infrastructure & Operations Security',
                'question_text' => 'Are systems continuously logged and monitored for security events and suspicious activity?',
                'risk_weight' => 5,
            ],

            // Incident Response & Resilience (Questions 13-15)
            [
                'category' => 'Incident Response & Resilience',
                'question_text' => 'Do you maintain a documented incident response plan that includes security incidents?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Incident Response & Resilience',
                'question_text' => 'Are customers notified of security incidents affecting their data within a defined timeframe?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Incident Response & Resilience',
                'question_text' => 'Do you perform regular data backups and test restoration procedures?',
                'risk_weight' => 5,
            ],

            // Third-Party & Supply Chain Risk (Questions 16-17)
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Are subcontractors or downstream vendors required to meet security standards comparable to your own?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Do you formally assess security risk before onboarding new third-party vendors?',
                'risk_weight' => 5,
            ],

            // Compliance & Assurance (Questions 18-19)
            [
                'category' => 'Compliance & Assurance',
                'question_text' => 'Have you completed an independent security or compliance assessment within the last 12 months?',
                'risk_weight' => 5,
            ],
            [
                'category' => 'Compliance & Assurance',
                'question_text' => 'Can you provide security assurance documentation upon request?',
                'risk_weight' => 5,
            ],

            // Legal, Risk & Accountability (Question 20)
            [
                'category' => 'Legal, Risk & Accountability',
                'question_text' => 'Do you maintain cyber or information security insurance appropriate to the services provided?',
                'risk_weight' => 5,
            ],
        ];

        $this->seedQuestions($template, $questions);
    }

    private function seedVendorSecuritySurveyInternal(int $userId): void
    {
        $template = SurveyTemplate::updateOrCreate(
            ['title' => 'Vendor Security Survey (Internal)'],
            [
                'description' => '<p>This internal assessment is designed to evaluate a vendor\'s security posture using publicly available information. Complete this survey based on your research of the vendor\'s website, trust center, and other public sources.</p>',
                'status' => SurveyTemplateStatus::ACTIVE,
                'type' => SurveyType::VENDOR_ASSESSMENT,
                'created_by_id' => $userId,
            ]
        );

        $questions = [
            // Corporate & Jurisdictional Transparency (Question 1)
            [
                'category' => 'Corporate & Jurisdictional Transparency',
                'question_text' => 'Does the vendor publicly disclose its legal entity name, country of incorporation, and primary operating jurisdiction?',
                'help_text' => 'Evidence source: Vendor website, legal or about pages',
                'risk_weight' => 7,
            ],

            // Data Locale & Residency (Questions 2-3)
            [
                'category' => 'Data Locale & Residency',
                'question_text' => 'Does the vendor publicly disclose where customer data is stored or processed (countries or regions)?',
                'help_text' => 'Evidence source: Trust center, data residency documentation',
                'risk_weight' => 7,
            ],
            [
                'category' => 'Data Locale & Residency',
                'question_text' => 'Is the vendor\'s primary data processing located in jurisdictions with strong data protection and rule-of-law frameworks (e.g., US, EU, UK, Canada)?',
                'help_text' => 'Evidence source: Infrastructure or hosting disclosures',
                'risk_weight' => 7,
            ],

            // Security Program Visibility (Questions 4-5)
            [
                'category' => 'Security Program Visibility',
                'question_text' => 'Does the vendor maintain a publicly accessible security or trust center describing cybersecurity controls?',
                'help_text' => 'Evidence source: Vendor trust or security webpage',
                'risk_weight' => 7,
            ],
            [
                'category' => 'Security Program Visibility',
                'question_text' => 'Does the vendor explicitly state the use of encryption for customer data both in transit and at rest?',
                'help_text' => 'Evidence source: Security documentation or FAQs',
                'risk_weight' => 7,
            ],

            // Identity & Access Management (Question 6)
            [
                'category' => 'Identity & Access Management',
                'question_text' => 'Does the vendor publicly state that multi-factor authentication is enforced for privileged or administrative access?',
                'help_text' => 'Evidence source: Security documentation or compliance statements',
                'risk_weight' => 7,
            ],

            // Monitoring & Detection (Question 7)
            [
                'category' => 'Monitoring & Detection',
                'question_text' => 'Does the vendor describe the use of logging, monitoring, or security event detection capabilities?',
                'help_text' => 'Evidence source: Security or operations documentation',
                'risk_weight' => 7,
            ],

            // Vulnerability & Patch Management (Question 8)
            [
                'category' => 'Vulnerability & Patch Management',
                'question_text' => 'Does the vendor state that vulnerabilities are regularly scanned for and remediated based on risk?',
                'help_text' => 'Evidence source: Security documentation, trust center',
                'risk_weight' => 7,
            ],

            // Incident Response Transparency (Question 9)
            [
                'category' => 'Incident Response Transparency',
                'question_text' => 'Does the vendor publicly describe an incident response and customer breach notification process?',
                'help_text' => 'Evidence source: Security documentation, terms, or FAQs',
                'risk_weight' => 7,
            ],

            // Certifications & Attestations (Questions 10-11)
            [
                'category' => 'Certifications & Attestations',
                'question_text' => 'Does the vendor publicly claim a current SOC 2, ISO 27001, or equivalent independent security certification?',
                'help_text' => 'Evidence source: Trust center, compliance statements',
                'risk_weight' => 7,
            ],
            [
                'category' => 'Certifications & Attestations',
                'question_text' => 'Does the vendor specify the scope of its security certification or audit (e.g., systems, services, regions)?',
                'help_text' => 'Evidence source: Audit summaries or trust documentation',
                'risk_weight' => 6,
            ],

            // Third-Party & Supply Chain Risk (Questions 12-13)
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Does the vendor publicly disclose the use of subprocessors or critical third-party service providers?',
                'help_text' => 'Evidence source: Subprocessor lists or privacy disclosures',
                'risk_weight' => 6,
            ],
            [
                'category' => 'Third-Party & Supply Chain Risk',
                'question_text' => 'Does the vendor state that subprocessors are subject to security or compliance requirements?',
                'help_text' => 'Evidence source: Subprocessor documentation or trust center',
                'risk_weight' => 6,
            ],

            // Security History & Reputation (Question 14)
            [
                'category' => 'Security History & Reputation',
                'question_text' => 'Is there no evidence of unresolved, undisclosed, or poorly handled material security incidents in public reporting?',
                'help_text' => 'Evidence source: Reputable news outlets, breach databases',
                'risk_weight' => 7,
            ],

            // Operational Security Maturity (Question 15)
            [
                'category' => 'Operational Security Maturity',
                'question_text' => 'Is there public evidence of ongoing security investment or maturity (e.g., regular security updates, disclosures, or security roles)?',
                'help_text' => 'Evidence source: Blogs, job postings, public communications',
                'risk_weight' => 6,
            ],
        ];

        $this->seedQuestions($template, $questions, true);
    }

    private function seedQuestions(SurveyTemplate $template, array $questions, bool $includeHelpText = false): void
    {
        foreach ($questions as $index => $question) {
            $helpText = $includeHelpText && isset($question['help_text'])
                ? $question['category'].' | '.$question['help_text']
                : $question['category'];

            SurveyQuestion::updateOrCreate(
                [
                    'survey_template_id' => $template->id,
                    'question_text' => $question['question_text'],
                ],
                [
                    'question_type' => QuestionType::BOOLEAN,
                    'help_text' => $helpText,
                    'is_required' => true,
                    'allow_comments' => true,
                    'sort_order' => $index + 1,
                    'risk_weight' => $question['risk_weight'],
                    'risk_impact' => RiskImpact::POSITIVE,
                    'options' => null,
                    'option_scores' => null,
                ]
            );
        }
    }
}
