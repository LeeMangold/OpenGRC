<?php

namespace Database\Seeders\Demo;

use App\Enums\QuestionType;
use App\Enums\RecurrenceFrequency;
use App\Enums\RiskImpact;
use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\Approval;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use Illuminate\Database\Seeder;

class DemoSurveysSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $this->seedSurveyTemplates();
        $this->seedSurveys();
    }

    private function seedSurveyTemplates(): void
    {
        // Vendor Security Assessment Template
        $vendorTemplate = SurveyTemplate::create([
            'title' => 'Vendor Security Assessment Questionnaire',
            'description' => 'Comprehensive security questionnaire for evaluating vendor security posture.',
            'status' => SurveyTemplateStatus::ACTIVE,
            'type' => SurveyType::VENDOR_ASSESSMENT,
            'created_by_id' => $this->context->users[0]->id,
        ]);
        $this->context->surveyTemplates['vendor'] = $vendorTemplate;

        $vendorQuestions = [
            ['text' => 'Does your organization have a documented information security policy?', 'type' => QuestionType::BOOLEAN, 'required' => true, 'risk_weight' => 3],
            ['text' => 'Do you have SOC 2 Type II certification?', 'type' => QuestionType::BOOLEAN, 'required' => true, 'risk_weight' => 5],
            ['text' => 'Describe your data encryption practices (at rest and in transit).', 'type' => QuestionType::LONG_TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'What access control mechanisms are in place?', 'type' => QuestionType::LONG_TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'Do you conduct regular security training for employees?', 'type' => QuestionType::BOOLEAN, 'required' => true, 'risk_weight' => 2],
            ['text' => 'How frequently do you conduct vulnerability assessments?', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'risk_weight' => 3, 'options' => ['Weekly', 'Monthly', 'Quarterly', 'Annually', 'Never']],
            ['text' => 'Do you have a documented incident response plan?', 'type' => QuestionType::BOOLEAN, 'required' => true, 'risk_weight' => 4],
            ['text' => 'Please upload your latest security certification or audit report.', 'type' => QuestionType::FILE, 'required' => false, 'risk_weight' => 5],
            ['text' => 'Which compliance frameworks do you adhere to?', 'type' => QuestionType::MULTIPLE_CHOICE, 'required' => true, 'risk_weight' => 4, 'options' => ['SOC 2', 'ISO 27001', 'HIPAA', 'PCI DSS', 'GDPR', 'FedRAMP', 'None']],
            ['text' => 'Describe your business continuity and disaster recovery capabilities.', 'type' => QuestionType::LONG_TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'What is your average time to patch critical vulnerabilities?', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'risk_weight' => 4, 'options' => ['Within 24 hours', 'Within 72 hours', 'Within 1 week', 'Within 30 days', 'No formal SLA']],
            ['text' => 'Primary security contact name', 'type' => QuestionType::TEXT, 'required' => true, 'risk_weight' => 1],
        ];

        foreach ($vendorQuestions as $index => $q) {
            SurveyQuestion::create([
                'survey_template_id' => $vendorTemplate->id,
                'question_text' => $q['text'],
                'question_type' => $q['type'],
                'is_required' => $q['required'],
                'sort_order' => $index + 1,
                'risk_weight' => $q['risk_weight'],
                'risk_impact' => RiskImpact::NEGATIVE,
                'options' => $q['options'] ?? null,
                'allow_comments' => true,
            ]);
        }

        // Vendor Data Privacy Assessment Template
        $privacyVendorTemplate = SurveyTemplate::create([
            'title' => 'Vendor Data Privacy Assessment',
            'description' => 'Assessment questionnaire focused on vendor data privacy practices and GDPR/CCPA compliance.',
            'status' => SurveyTemplateStatus::ACTIVE,
            'type' => SurveyType::VENDOR_ASSESSMENT,
            'created_by_id' => $this->context->users[0]->id,
        ]);
        $this->context->surveyTemplates['vendor_privacy'] = $privacyVendorTemplate;

        $privacyQuestions = [
            ['text' => 'Do you process personal data on behalf of your clients?', 'type' => QuestionType::BOOLEAN, 'required' => true, 'risk_weight' => 5],
            ['text' => 'What categories of personal data do you process?', 'type' => QuestionType::MULTIPLE_CHOICE, 'required' => true, 'risk_weight' => 4, 'options' => ['Names/Contact Info', 'Financial Data', 'Health Information', 'Biometric Data', 'Location Data', 'Behavioral Data', 'None']],
            ['text' => 'In which countries/regions do you store or process data?', 'type' => QuestionType::TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'Describe your data retention and deletion policies.', 'type' => QuestionType::LONG_TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'Do you have a Data Protection Officer (DPO)?', 'type' => QuestionType::BOOLEAN, 'required' => true, 'risk_weight' => 3],
            ['text' => 'How do you handle data subject access requests (DSARs)?', 'type' => QuestionType::LONG_TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'Do you use sub-processors? If yes, list them.', 'type' => QuestionType::LONG_TEXT, 'required' => true, 'risk_weight' => 4],
            ['text' => 'What is your data breach notification timeframe?', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'risk_weight' => 5, 'options' => ['Within 24 hours', 'Within 48 hours', 'Within 72 hours', 'Within 1 week', 'No formal SLA']],
            ['text' => 'Please upload your privacy policy document.', 'type' => QuestionType::FILE, 'required' => false, 'risk_weight' => 3],
            ['text' => 'Are you certified under any privacy frameworks?', 'type' => QuestionType::MULTIPLE_CHOICE, 'required' => true, 'risk_weight' => 3, 'options' => ['Privacy Shield (legacy)', 'BCRs', 'APEC CBPR', 'TRUSTe', 'None']],
        ];

        foreach ($privacyQuestions as $index => $q) {
            SurveyQuestion::create([
                'survey_template_id' => $privacyVendorTemplate->id,
                'question_text' => $q['text'],
                'question_type' => $q['type'],
                'is_required' => $q['required'],
                'sort_order' => $index + 1,
                'risk_weight' => $q['risk_weight'],
                'risk_impact' => RiskImpact::NEGATIVE,
                'options' => $q['options'] ?? null,
                'allow_comments' => true,
            ]);
        }

        // Internal Checklist Templates
        $this->createChecklistTemplates();

        // New Employee Security Onboarding Questionnaire
        $questionnaireTemplate = SurveyTemplate::create([
            'title' => 'New Employee Security Onboarding',
            'description' => 'Security awareness questionnaire for new employees.',
            'status' => SurveyTemplateStatus::ACTIVE,
            'type' => SurveyType::QUESTIONNAIRE,
            'created_by_id' => $this->context->users[0]->id,
        ]);
        $this->context->surveyTemplates['questionnaire'] = $questionnaireTemplate;

        $onboardingQuestions = [
            ['text' => 'I have read and understand the Information Security Policy.', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'I have completed security awareness training.', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'I have set up multi-factor authentication on all required accounts.', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'Which security practices are you familiar with?', 'type' => QuestionType::MULTIPLE_CHOICE, 'required' => true, 'options' => ['Phishing recognition', 'Password best practices', 'Data classification', 'Incident reporting', 'Clean desk policy']],
            ['text' => 'My workstation is encrypted with full-disk encryption.', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'I know how to report a security incident.', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'I understand my responsibilities for handling sensitive data.', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'My direct supervisor/manager name', 'type' => QuestionType::TEXT, 'required' => true],
            ['text' => 'Department', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['Engineering', 'Sales', 'Marketing', 'HR', 'Finance', 'Operations', 'IT', 'Legal', 'Other']],
            ['text' => 'Additional questions or concerns about security', 'type' => QuestionType::LONG_TEXT, 'required' => false],
        ];

        foreach ($onboardingQuestions as $index => $q) {
            SurveyQuestion::create([
                'survey_template_id' => $questionnaireTemplate->id,
                'question_text' => $q['text'],
                'question_type' => $q['type'],
                'is_required' => $q['required'],
                'sort_order' => $index + 1,
                'options' => $q['options'] ?? null,
                'allow_comments' => true,
            ]);
        }

        // Security Incident Post-Mortem Questionnaire
        $incidentTemplate = SurveyTemplate::create([
            'title' => 'Security Incident Post-Mortem',
            'description' => 'Post-incident review questionnaire to capture lessons learned.',
            'status' => SurveyTemplateStatus::ACTIVE,
            'type' => SurveyType::QUESTIONNAIRE,
            'created_by_id' => $this->context->users[0]->id,
        ]);
        $this->context->surveyTemplates['incident_review'] = $incidentTemplate;

        $incidentQuestions = [
            ['text' => 'Incident ID/Reference Number', 'type' => QuestionType::TEXT, 'required' => true],
            ['text' => 'Incident classification', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['Critical', 'High', 'Medium', 'Low']],
            ['text' => 'Was the incident detected by internal monitoring?', 'type' => QuestionType::BOOLEAN, 'required' => true],
            ['text' => 'How was the incident detected?', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['SIEM alert', 'User report', 'Automated scanning', 'Third-party notification', 'Random discovery', 'Other']],
            ['text' => 'What was the root cause?', 'type' => QuestionType::LONG_TEXT, 'required' => true],
            ['text' => 'Which systems were affected?', 'type' => QuestionType::MULTIPLE_CHOICE, 'required' => true, 'options' => ['Production servers', 'Database', 'Network', 'Endpoints', 'Cloud services', 'Third-party systems']],
            ['text' => 'Time to detect (hours)', 'type' => QuestionType::TEXT, 'required' => true],
            ['text' => 'Time to contain (hours)', 'type' => QuestionType::TEXT, 'required' => true],
            ['text' => 'Time to remediate (hours)', 'type' => QuestionType::TEXT, 'required' => true],
            ['text' => 'What went well during the response?', 'type' => QuestionType::LONG_TEXT, 'required' => true],
            ['text' => 'What could be improved?', 'type' => QuestionType::LONG_TEXT, 'required' => true],
            ['text' => 'Recommended preventive measures', 'type' => QuestionType::LONG_TEXT, 'required' => true],
            ['text' => 'Upload incident report documentation', 'type' => QuestionType::FILE, 'required' => false],
        ];

        foreach ($incidentQuestions as $index => $q) {
            SurveyQuestion::create([
                'survey_template_id' => $incidentTemplate->id,
                'question_text' => $q['text'],
                'question_type' => $q['type'],
                'is_required' => $q['required'],
                'sort_order' => $index + 1,
                'options' => $q['options'] ?? null,
                'allow_comments' => true,
            ]);
        }
    }

    private function createChecklistTemplates(): void
    {
        // Monthly Security Operations Checklist
        $this->createChecklistTemplate(
            'Monthly Security Operations Checklist',
            'Comprehensive monthly security verification checklist covering all operational security areas.',
            RecurrenceFrequency::MONTHLY,
            1,
            'recurrence_day_of_month',
            [
                ['text' => 'Reviewed and validated all user access permissions', 'type' => QuestionType::BOOLEAN, 'required' => true, 'help' => 'Check for terminated employees, role changes, and unnecessary privileges'],
                ['text' => 'Verified backup completion and tested restore procedures', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Reviewed security monitoring alerts and SIEM logs', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Analyzed vulnerability scan results and remediation status', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified patch status on all critical systems', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Reviewed firewall rules and network configurations', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked endpoint protection status across all devices', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Number of security incidents this month', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['0', '1-2', '3-5', '6-10', 'More than 10']],
                ['text' => 'Summary of significant findings or concerns', 'type' => QuestionType::LONG_TEXT, 'required' => false],
                ['text' => 'Reviewer name', 'type' => QuestionType::TEXT, 'required' => true],
            ],
            'checklist'
        );

        // Weekly Access Review Checklist
        $this->createChecklistTemplate(
            'Weekly Access Review Checklist',
            'Weekly review of access controls and privileged account usage.',
            RecurrenceFrequency::WEEKLY,
            1,
            'recurrence_day_of_week',
            [
                ['text' => 'Reviewed new user access requests', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified terminated employee accounts are disabled', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked for dormant accounts (no login > 30 days)', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Reviewed privileged account usage logs', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Number of new access requests processed', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Details of any access-related incidents or concerns', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_access'
        );

        // Daily Security Operations Checklist
        $this->createChecklistTemplate(
            'Daily Security Operations Checklist',
            'Daily operational security tasks and monitoring checks.',
            RecurrenceFrequency::DAILY,
            1,
            'recurrence_day_of_week',
            [
                ['text' => 'Reviewed overnight security alerts', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked system availability and performance', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified backup jobs completed successfully', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Reviewed failed login attempts', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Total number of alerts requiring investigation', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Shift handoff notes', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_daily'
        );

        // Quarterly Risk Assessment Checklist
        $this->createChecklistTemplate(
            'Quarterly Risk Assessment Review',
            'Quarterly review of organizational risk posture and control effectiveness.',
            RecurrenceFrequency::QUARTERLY,
            null,
            null,
            [
                ['text' => 'Reviewed and updated risk register', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Assessed control effectiveness for high-risk areas', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Reviewed vendor risk assessments', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Overall risk posture assessment', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['Improved', 'Stable', 'Slightly Degraded', 'Significantly Degraded']],
                ['text' => 'Key findings and recommendations', 'type' => QuestionType::LONG_TEXT, 'required' => true],
            ],
            'checklist_risk'
        );

        // Incident Response Readiness Checklist
        $this->createChecklistTemplate(
            'Incident Response Readiness Checklist',
            'Monthly verification of incident response capabilities and resources.',
            RecurrenceFrequency::MONTHLY,
            15,
            'recurrence_day_of_month',
            [
                ['text' => 'Verified incident response team contact information is current', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Confirmed on-call schedule is up to date', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Tested communication channels', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified access to forensic tools and resources', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Last tabletop exercise date', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Recommended improvements', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_ir'
        );

        // Weekly Change Management Review
        $this->createChecklistTemplate(
            'Weekly Change Management Review',
            'Review of all changes implemented in the past week.',
            RecurrenceFrequency::WEEKLY,
            5,
            'recurrence_day_of_week',
            [
                ['text' => 'All changes this week followed the change management process', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Emergency changes were properly documented', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'All changes had proper approvals', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Total number of changes implemented', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Number of failed/rolled back changes', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Lessons learned or process improvements needed', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_change'
        );

        // Monthly Physical Security Inspection
        $this->createChecklistTemplate(
            'Monthly Physical Security Inspection',
            'Physical security controls verification for office and data center facilities.',
            RecurrenceFrequency::MONTHLY,
            1,
            'recurrence_day_of_month',
            [
                ['text' => 'Verified all access card readers are functioning', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked security camera coverage and recording', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Reviewed visitor logs for anomalies', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified server room environmental controls', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Inspector name and badge number', 'type' => QuestionType::TEXT, 'required' => true],
            ],
            'checklist_physical'
        );

        // Monthly Compliance Monitoring Checklist
        $this->createChecklistTemplate(
            'Monthly Compliance Monitoring Checklist',
            'Verification of compliance with regulatory requirements and internal policies.',
            RecurrenceFrequency::MONTHLY,
            5,
            'recurrence_day_of_month',
            [
                ['text' => 'Reviewed compliance training completion rates', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified policy acknowledgments are current', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked audit log retention compliance', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Training completion percentage', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['100%', '90-99%', '80-89%', '70-79%', 'Below 70%']],
                ['text' => 'Compliance gaps or concerns', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_compliance'
        );

        // Monthly Cloud Security Review
        $this->createChecklistTemplate(
            'Monthly Cloud Security Review',
            'Security review of cloud infrastructure and services.',
            RecurrenceFrequency::MONTHLY,
            10,
            'recurrence_day_of_month',
            [
                ['text' => 'Reviewed IAM roles and policies', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked for publicly exposed resources', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified encryption settings for storage', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified MFA for all privileged accounts', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Number of misconfigured resources found', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Critical findings requiring immediate action', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_cloud'
        );

        // Quarterly Business Continuity Review
        $this->createChecklistTemplate(
            'Quarterly Business Continuity Review',
            'Quarterly review of business continuity and disaster recovery readiness.',
            RecurrenceFrequency::QUARTERLY,
            null,
            null,
            [
                ['text' => 'Verified BCP/DR documentation is current', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Tested failover procedures this quarter', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified backup restoration capabilities', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Date of last full DR test', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Test results and identified gaps', 'type' => QuestionType::LONG_TEXT, 'required' => true],
            ],
            'checklist_bcp'
        );

        // Quarterly Security Awareness Review
        $this->createChecklistTemplate(
            'Quarterly Security Awareness Review',
            'Review of security awareness program effectiveness.',
            RecurrenceFrequency::QUARTERLY,
            null,
            null,
            [
                ['text' => 'Reviewed overall training completion rates', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Analyzed phishing simulation results', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Phishing simulation click rate', 'type' => QuestionType::SINGLE_CHOICE, 'required' => true, 'options' => ['< 5%', '5-10%', '10-20%', '20-30%', '> 30%']],
                ['text' => 'Number of employees completing training', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Recommendations for next quarter', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_training'
        );

        // Monthly Data Protection Review
        $this->createChecklistTemplate(
            'Monthly Data Protection Review',
            'Monthly review of data protection controls and privacy compliance.',
            RecurrenceFrequency::MONTHLY,
            20,
            'recurrence_day_of_month',
            [
                ['text' => 'Reviewed data access logs for sensitive systems', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Verified DLP policies are functioning', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Checked encryption status for data at rest', 'type' => QuestionType::BOOLEAN, 'required' => true],
                ['text' => 'Number of DLP alerts this month', 'type' => QuestionType::TEXT, 'required' => true],
                ['text' => 'Data protection concerns or incidents', 'type' => QuestionType::LONG_TEXT, 'required' => false],
            ],
            'checklist_data'
        );
    }

    private function createChecklistTemplate(
        string $title,
        string $description,
        RecurrenceFrequency $frequency,
        ?int $recurrenceValue,
        ?string $recurrenceField,
        array $questions,
        string $key
    ): void {
        $templateData = [
            'title' => $title,
            'description' => $description,
            'status' => SurveyTemplateStatus::ACTIVE,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'created_by_id' => $this->context->users[0]->id,
            'default_assignee_id' => $this->context->users[array_rand(array_slice($this->context->users, 0, 4))]->id,
            'recurrence_frequency' => $frequency,
            'recurrence_interval' => 1,
        ];

        if ($recurrenceField && $recurrenceValue !== null) {
            $templateData[$recurrenceField] = $recurrenceValue;
        }

        $template = SurveyTemplate::create($templateData);
        $this->context->surveyTemplates[$key] = $template;

        foreach ($questions as $index => $q) {
            SurveyQuestion::create([
                'survey_template_id' => $template->id,
                'question_text' => $q['text'],
                'question_type' => $q['type'],
                'is_required' => $q['required'],
                'sort_order' => $index + 1,
                'options' => $q['options'] ?? null,
                'help_text' => $q['help'] ?? null,
                'allow_comments' => true,
            ]);
        }
    }

    private function seedSurveys(): void
    {
        $completedSurveys = [];

        // Vendor Assessment Surveys
        foreach ($this->context->vendors as $index => $vendor) {
            $status = match ($index % 7) {
                0, 1, 2 => SurveyStatus::COMPLETED,
                3 => SurveyStatus::PENDING_SCORING,
                4 => SurveyStatus::IN_PROGRESS,
                5 => SurveyStatus::SENT,
                default => SurveyStatus::DRAFT,
            };

            $survey = Survey::create([
                'survey_template_id' => $this->context->surveyTemplates['vendor']->id,
                'title' => 'Annual Security Assessment - '.$vendor->name,
                'type' => SurveyType::VENDOR_ASSESSMENT,
                'status' => $status,
                'vendor_id' => $vendor->id,
                'respondent_name' => $this->context->faker->name(),
                'respondent_email' => strtolower(str_replace(' ', '.', $this->context->faker->name())).'@'.strtolower(str_replace([' ', ',', '.'], '', $vendor->name)).'.com',
                'created_by_id' => $this->context->users[array_rand($this->context->users)]->id,
                'approver_id' => $status === SurveyStatus::COMPLETED ? $this->context->users[0]->id : null,
                'due_date' => now()->addDays(rand(7, 45)),
                'expiration_date' => now()->addDays(90),
                'completed_at' => $status === SurveyStatus::COMPLETED ? now()->subDays(rand(1, 30)) : null,
            ]);

            if ($status === SurveyStatus::COMPLETED) {
                $completedSurveys[] = $survey;
            }

            $this->addSurveyResponses($survey, $status, $this->context->surveyTemplates['vendor']);
        }

        // Privacy assessments for select vendors
        $privacyVendors = array_slice($this->context->vendors, 0, 8);
        foreach ($privacyVendors as $index => $vendor) {
            $status = match ($index % 4) {
                0, 1 => SurveyStatus::COMPLETED,
                2 => SurveyStatus::IN_PROGRESS,
                default => SurveyStatus::SENT,
            };

            $survey = Survey::create([
                'survey_template_id' => $this->context->surveyTemplates['vendor_privacy']->id,
                'title' => 'Data Privacy Assessment - '.$vendor->name,
                'type' => SurveyType::VENDOR_ASSESSMENT,
                'status' => $status,
                'vendor_id' => $vendor->id,
                'respondent_name' => $this->context->faker->name(),
                'respondent_email' => 'privacy@'.strtolower(str_replace([' ', ',', '.'], '', $vendor->name)).'.com',
                'created_by_id' => $this->context->users[1]->id,
                'approver_id' => $status === SurveyStatus::COMPLETED ? $this->context->users[0]->id : null,
                'due_date' => now()->addDays(rand(14, 60)),
                'expiration_date' => now()->addDays(120),
                'completed_at' => $status === SurveyStatus::COMPLETED ? now()->subDays(rand(5, 45)) : null,
            ]);

            if ($status === SurveyStatus::COMPLETED) {
                $completedSurveys[] = $survey;
            }

            $this->addSurveyResponses($survey, $status, $this->context->surveyTemplates['vendor_privacy']);
        }

        // Internal Checklists
        $checklistTemplateKeys = [
            'checklist', 'checklist_access', 'checklist_daily', 'checklist_risk',
            'checklist_ir', 'checklist_change', 'checklist_physical', 'checklist_compliance',
            'checklist_cloud', 'checklist_bcp', 'checklist_training', 'checklist_data',
        ];

        foreach ($checklistTemplateKeys as $templateKey) {
            if (! isset($this->context->surveyTemplates[$templateKey])) {
                continue;
            }

            $template = $this->context->surveyTemplates[$templateKey];
            $frequency = $template->recurrence_frequency;

            $instanceCount = match ($frequency) {
                RecurrenceFrequency::DAILY => 14,
                RecurrenceFrequency::WEEKLY => 8,
                RecurrenceFrequency::MONTHLY => 6,
                RecurrenceFrequency::QUARTERLY => 4,
                RecurrenceFrequency::YEARLY => 2,
                default => 3,
            };

            for ($i = 0; $i < $instanceCount; $i++) {
                $periodDate = match ($frequency) {
                    RecurrenceFrequency::DAILY => now()->subDays($i),
                    RecurrenceFrequency::WEEKLY => now()->subWeeks($i),
                    RecurrenceFrequency::MONTHLY => now()->subMonths($i),
                    RecurrenceFrequency::QUARTERLY => now()->subMonths($i * 3),
                    RecurrenceFrequency::YEARLY => now()->subYears($i),
                    default => now()->subMonths($i),
                };

                $status = match (true) {
                    $i === 0 => rand(0, 1) === 0 ? SurveyStatus::IN_PROGRESS : SurveyStatus::DRAFT,
                    $i === 1 => rand(0, 2) === 0 ? SurveyStatus::IN_PROGRESS : SurveyStatus::COMPLETED,
                    default => SurveyStatus::COMPLETED,
                };

                $titleSuffix = match ($frequency) {
                    RecurrenceFrequency::DAILY => $periodDate->format('M j, Y'),
                    RecurrenceFrequency::WEEKLY => 'Week of '.$periodDate->startOfWeek()->format('M j, Y'),
                    RecurrenceFrequency::MONTHLY => $periodDate->format('F Y'),
                    RecurrenceFrequency::QUARTERLY => 'Q'.ceil($periodDate->month / 3).' '.$periodDate->year,
                    RecurrenceFrequency::YEARLY => $periodDate->format('Y'),
                    default => $periodDate->format('F Y'),
                };

                $survey = Survey::create([
                    'survey_template_id' => $template->id,
                    'title' => str_replace(['Monthly ', 'Weekly ', 'Daily ', 'Quarterly '], '', $template->title).' - '.$titleSuffix,
                    'type' => SurveyType::INTERNAL_CHECKLIST,
                    'status' => $status,
                    'assigned_to_id' => $template->default_assignee_id ?? $this->context->users[array_rand($this->context->users)]->id,
                    'created_by_id' => $this->context->users[0]->id,
                    'approver_id' => $status === SurveyStatus::COMPLETED ? $this->context->users[rand(0, 1)]->id : null,
                    'due_date' => $periodDate->copy()->endOfDay(),
                    'completed_at' => $status === SurveyStatus::COMPLETED ? $periodDate->copy()->subHours(rand(1, 48)) : null,
                ]);

                if ($status === SurveyStatus::COMPLETED) {
                    $completedSurveys[] = $survey;
                }

                $this->addSurveyResponses($survey, $status, $template);
            }
        }

        // Employee onboarding questionnaires
        $employeeNames = [
            'Alex Johnson', 'Sarah Williams', 'Michael Chen', 'Emily Rodriguez',
            'David Kim', 'Jessica Brown', 'Ryan Martinez', 'Amanda Taylor',
            'Christopher Lee', 'Nicole Davis', 'Matthew Wilson', 'Ashley Garcia',
        ];

        foreach ($employeeNames as $index => $name) {
            $status = match ($index % 4) {
                0, 1, 2 => SurveyStatus::COMPLETED,
                default => SurveyStatus::IN_PROGRESS,
            };

            $hireDate = now()->subDays(rand(7, 180));

            $survey = Survey::create([
                'survey_template_id' => $this->context->surveyTemplates['questionnaire']->id,
                'title' => 'Security Onboarding - '.$name,
                'type' => SurveyType::QUESTIONNAIRE,
                'status' => $status,
                'respondent_name' => $name,
                'respondent_email' => strtolower(str_replace(' ', '.', $name)).'@company.com',
                'assigned_to_id' => $this->context->users[array_rand($this->context->users)]->id,
                'created_by_id' => $this->context->users[1]->id,
                'due_date' => $hireDate->copy()->addDays(7),
                'completed_at' => $status === SurveyStatus::COMPLETED ? $hireDate->copy()->addDays(rand(1, 5)) : null,
            ]);

            if ($status === SurveyStatus::COMPLETED) {
                $completedSurveys[] = $survey;
            }

            $this->addSurveyResponses($survey, $status, $this->context->surveyTemplates['questionnaire']);
        }

        // Incident post-mortem questionnaires
        $incidentNames = [
            'Phishing Campaign Detection',
            'Unauthorized Access Attempt',
            'DDoS Mitigation Response',
            'Ransomware Near-Miss',
        ];

        foreach ($incidentNames as $index => $incidentName) {
            $status = $index < 3 ? SurveyStatus::COMPLETED : SurveyStatus::IN_PROGRESS;

            $survey = Survey::create([
                'survey_template_id' => $this->context->surveyTemplates['incident_review']->id,
                'title' => 'Post-Mortem: '.$incidentName,
                'type' => SurveyType::QUESTIONNAIRE,
                'status' => $status,
                'assigned_to_id' => $this->context->users[2]->id,
                'created_by_id' => $this->context->users[0]->id,
                'due_date' => now()->subDays(rand(1, 30))->addDays(14),
                'completed_at' => $status === SurveyStatus::COMPLETED ? now()->subDays(rand(5, 60)) : null,
            ]);

            if ($status === SurveyStatus::COMPLETED) {
                $completedSurveys[] = $survey;
            }

            $this->addSurveyResponses($survey, $status, $this->context->surveyTemplates['incident_review']);
        }

        // Add Approvals to completed surveys
        foreach ($completedSurveys as $survey) {
            if (rand(1, 10) <= 7) {
                $approver = $this->context->users[rand(0, min(2, count($this->context->users) - 1))];
                $approvedAt = $survey->completed_at?->copy()->addHours(rand(1, 72)) ?? now()->subDays(rand(1, 30));

                Approval::create([
                    'approvable_type' => Survey::class,
                    'approvable_id' => $survey->id,
                    'approver_id' => $approver->id,
                    'approver_name' => $approver->name,
                    'approver_email' => $approver->email,
                    'signature' => $approver->name,
                    'notes' => $this->context->faker->optional(0.4)->randomElement([
                        'Reviewed and approved. All responses look complete.',
                        'Approved with minor recommendations noted.',
                        'Good work on this assessment. Approved.',
                        'Reviewed all responses. Meets requirements.',
                        null,
                    ]),
                    'approved_at' => $approvedAt,
                ]);
            }
        }
    }

    private function addSurveyResponses(Survey $survey, SurveyStatus $status, SurveyTemplate $template): void
    {
        if (! in_array($status, [SurveyStatus::COMPLETED, SurveyStatus::PENDING_SCORING, SurveyStatus::IN_PROGRESS])) {
            return;
        }

        $questions = $template->questions;
        $answerPercentage = match ($status) {
            SurveyStatus::COMPLETED, SurveyStatus::PENDING_SCORING => 100,
            SurveyStatus::IN_PROGRESS => rand(30, 80),
            default => 0,
        };

        /** @var SurveyQuestion $question */
        foreach ($questions as $question) {
            if ($status === SurveyStatus::IN_PROGRESS && rand(1, 100) > $answerPercentage) {
                continue;
            }

            $answerValue = $this->generateAnswerForQuestion($question);

            if ($answerValue !== null) {
                SurveyAnswer::create([
                    'survey_id' => $survey->id,
                    'survey_question_id' => $question->id,
                    'answer_value' => $answerValue,
                    'comment' => $this->context->faker->optional(0.25)->randomElement([
                        'See attached documentation for details.',
                        'This was verified during our last review.',
                        'Additional context provided in our policies.',
                        'Working on improvements in this area.',
                        'Need to follow up on this item.',
                        null,
                    ]),
                ]);
            }
        }
    }

    private function generateAnswerForQuestion(SurveyQuestion $question): ?array
    {
        return match ($question->question_type) {
            QuestionType::BOOLEAN => [rand(0, 10) > 2 ? 'Yes' : 'No'],
            QuestionType::TEXT => [$this->context->faker->randomElement([
                $this->context->faker->name(),
                $this->context->faker->company(),
                (string) rand(1, 100),
                $this->context->faker->date('Y-m-d'),
                $this->context->faker->sentence(3),
            ])],
            QuestionType::LONG_TEXT => [$this->context->faker->randomElement([
                $this->context->faker->paragraph(3),
                $this->context->faker->paragraph(2)."\n\n".$this->context->faker->paragraph(2),
                'We have implemented comprehensive measures including: '.$this->context->faker->paragraph(2),
                'Our approach involves multiple layers of protection. '.$this->context->faker->paragraph(3),
                $this->context->faker->paragraph(4),
            ])],
            QuestionType::SINGLE_CHOICE => $question->options ? [$question->options[array_rand($question->options)]] : null,
            QuestionType::MULTIPLE_CHOICE => $question->options ? $this->getRandomMultipleChoiceAnswers($question->options) : null,
            QuestionType::FILE => null,
            default => null,
        };
    }

    private function getRandomMultipleChoiceAnswers(array $options): array
    {
        $numSelections = rand(1, min(4, count($options)));
        $shuffled = $options;
        shuffle($shuffled);

        return array_slice($shuffled, 0, $numSelections);
    }
}
