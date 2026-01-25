<?php

namespace Database\Seeders;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\AuditResource;
use App\Http\Controllers\HelperController;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Risk;
use App\Models\Standard;
use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private Faker $faker;

    // constructor
    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * Run the database demo seeds.
     */
    public function run(): void
    {

        // Create 10 users from factory
        \App\Models\User::factory(10)->create();

        $standard = Standard::create([
            'name' => 'OpenGRC Demo Security Standard 1.0',
            'code' => 'OpenGRC-1.0',
            'authority' => 'Lee Mangold',
            'status' => 'In Scope',
            'description' => 'OpenGRC Demo Security Standard 1.0 is a conceptual framework designed for demonstration purposes within the realm of cybersecurity. This standard encompasses a comprehensive set of guidelines and best practices aimed at fortifying digital infrastructure and safeguarding sensitive data. It integrates key principles across six critical domains: Legal, Ethical, Environmental, Governance, Risk Management, and Compliance (OpenGRC), offering a holistic approach to cybersecurity. Tailored for educational and demonstrative scenarios, this standard serves as a pedagogical tool to illustrate effective cybersecurity strategies. It emphasizes the importance of legal compliance, ethical hacking, environmental awareness in digital contexts, governance structures, proactive risk management, and adherence to compliance standards. The OpenGRC Demo Security Standard 1.0 is designed to be adaptable, allowing it to be applied in various hypothetical scenarios to demonstrate the impact and implementation of robust cybersecurity measures in a controlled environment',
        ]);

        $controlsData = [

            'L1' => [
                'standard_id' => $standard->id,
                'code' => 'L1',
                'title' => 'Implementation of Enterprise Detection and Response (EDR) Tool',
                'description' => HelperController::linesToParagraphs(
                    "This control involves the deployment of an Enterprise Detection and Response (EDR) tool across the organization's network. The EDR tool is designed to continuously monitor, detect, and respond to cyber threats in real time. It should be integrated into the existing IT infrastructure and configured to provide comprehensive coverage of all endpoints. Regular updates and maintenance of the EDR system are essential to ensure its effectiveness against evolving cyber threats.",
                    'control-description-text'
                ),
                'discussion' => "Implementing an EDR tool is crucial for modern cybersecurity defense, as it provides enhanced visibility into network activities and potential security breaches. EDR tools are instrumental in identifying sophisticated threats that traditional antivirus solutions might miss. They enable real-time analysis and automated response to incidents, reducing the time to detect and mitigate threats. Best practices include ensuring compatibility with existing systems, regular training for IT staff on the EDR tool's functionalities, and conducting periodic reviews to update threat detection capabilities in line with emerging cyber threats.",
            ],
            'L2' => [
                'standard_id' => $standard->id,
                'code' => 'L2',
                'title' => 'Mandatory Encryption of Sensitive Data',
                'description' => HelperController::linesToParagraphs(
                    'This control mandates the encryption of all sensitive data, both at rest and in transit. The implementation includes the use of industry-standard encryption protocols and algorithms. Encryption keys should be securely managed and rotated periodically to maintain data integrity and confidentiality.',
                    'control-description-text'
                ),
                'discussion' => 'Encrypting sensitive data is a fundamental aspect of protecting it from unauthorized access and breaches. The use of strong encryption methods helps in safeguarding data against cyber threats. Key management practices are crucial for ensuring the effectiveness of encryption and preventing unauthorized access.',
            ],
            'L3' => [
                'standard_id' => $standard->id,
                'code' => 'L3',
                'title' => 'Conducting Regular Security Audits',
                'description' => HelperController::linesToParagraphs(
                    'This control requires conducting regular security audits to assess the effectiveness of existing security measures. The audits should include a thorough review of IT systems, policies, and procedures. External auditors may be engaged for unbiased assessments.',
                    'control-description-text'
                ),
                'discussion' => 'Regular security audits are crucial for identifying potential vulnerabilities and gaps in the security framework. They provide insights into areas needing improvement and help in maintaining high security standards. Best practices include a mix of internal and external audits for a comprehensive assessment.',
            ],
            'L4' => [
                'standard_id' => $standard->id,
                'code' => 'L4',
                'title' => 'Comprehensive Incident Response Planning',
                'description' => HelperController::linesToParagraphs(
                    'This control focuses on developing and maintaining a comprehensive incident response plan. The plan should outline procedures for responding to various types of cybersecurity incidents, roles and responsibilities, communication strategies, and recovery processes.',
                    'control-description-text'
                ),
                'discussion' => 'An effective incident response plan is critical for minimizing the impact of cyber incidents. It ensures a coordinated and timely response, thereby reducing downtime and financial losses. Regular drills and updates to the plan are recommended as best practices.',
            ],
            'L5' => [
                'standard_id' => $standard->id,
                'code' => 'L5',
                'title' => 'Robust User Access Management',
                'description' => HelperController::linesToParagraphs(
                    'This control involves implementing robust user access management protocols. It includes strict user authentication, authorization mechanisms, and regular reviews of user access rights. The principle of least privilege should be enforced, granting access only as necessary for roles and responsibilities.',
                    'control-description-text'
                ),
                'discussion' => 'Proper user access management is essential to prevent unauthorized access to sensitive information. It reduces the risk of internal and external breaches. Regular audits of user access levels and the enforcement of the least privilege principle are best practices.',
            ],
            'L6' => [
                'standard_id' => $standard->id,
                'code' => 'L6',
                'title' => 'Regular Security Awareness Training',
                'description' => HelperController::linesToParagraphs(
                    'This control requires conducting regular security awareness training for all employees. The training should cover topics such as phishing, password security, and safe internet practices. The aim is to equip staff with knowledge and best practices to recognize and prevent security threats.',
                    'control-description-text'
                ),
                'discussion' => 'Security awareness training plays a vital role in enhancing the overall security posture of an organization. Educating employees about cybersecurity risks and how to avoid them helps in preventing many security incidents. Continuous education and training updates are recommended.',
            ],
            'L7' => [
                'standard_id' => $standard->id,
                'code' => 'L7',
                'title' => 'Implementation of Secure Network Architecture',
                'description' => HelperController::linesToParagraphs(
                    'This control involves the design and implementation of a secure network architecture. Key components include firewalls, intrusion detection systems, and network segmentation. The architecture should be regularly reviewed and updated in accordance with the latest security standards and threat landscape.',
                    'control-description-text'
                ),
                'discussion' => 'A secure network architecture is fundamental in protecting organizational assets from cyber threats. It serves as the first line of defense against external attacks. Regular updates and adherence to network security best practices are crucial for maintaining a robust network defense.',
            ],
            'L8' => [
                'standard_id' => $standard->id,
                'code' => 'L8',
                'title' => 'Proactive Vulnerability Management',
                'description' => HelperController::linesToParagraphs(
                    'This control focuses on establishing a proactive vulnerability management process. It includes regular scanning for vulnerabilities, timely patching of software, and assessing the risks associated with identified vulnerabilities. The goal is to mitigate vulnerabilities before they can be exploited.',
                    'control-description-text'
                ),
                'discussion' => 'Effective vulnerability management is key to reducing the attack surface of an organization. Proactively identifying and addressing vulnerabilities prevents potential exploits and strengthens security. Best practices include regular scans, prompt patching, and continuous monitoring.',
            ],
            'L9' => [
                'standard_id' => $standard->id,
                'code' => 'L9',
                'title' => 'Enforcement of Multi-Factor Authentication',
                'description' => HelperController::linesToParagraphs(
                    'This control mandates the use of Multi-Factor Authentication (MFA) for accessing critical systems and data. MFA adds an additional layer of security by requiring two or more verification factors, which significantly reduces the risk of unauthorized access.',
                    'control-description-text'
                ),
                'discussion' => 'MFA is a critical security measure in today’s threat landscape. It provides enhanced security by requiring multiple forms of verification, making it much harder for unauthorized users to gain access. Implementation should be user-friendly to encourage compliance.',
            ],
            'L10' => [
                'standard_id' => $standard->id,
                'code' => 'L10',
                'title' => 'Comprehensive Third-Party Risk Management',
                'description' => HelperController::linesToParagraphs(
                    'This control involves developing a comprehensive third-party risk management program. It includes conducting due diligence on third-party vendors, regularly assessing their security postures, and ensuring contractual agreements include robust security requirements.',
                    'control-description-text'
                ),
                'discussion' => 'Managing third-party risks is essential as organizations increasingly rely on external vendors for critical services. Ensuring that third-parties adhere to high-security standards helps in mitigating risks associated with data breaches and cyber-attacks originating from these entities.',
            ],
        ];

        $implementationsData = [

            'IMPL-L1' => [
                'code' => 'IMPL-L1',
                'title' => 'EDR Deployment on Workstations',
                'details' => 'Enterprise Detection and Response (EDR) tool, specifically Microsoft Defender, is deployed across all workstations within the organization. Configuration settings are optimized for maximum detection efficiency and minimal performance impact. Continuous monitoring and automatic updates are enabled to ensure up-to-date protection against emerging threats.',
                'notes' => 'Currently, Microsoft Defender is deployed on all Windows workstations through Group Policy Object (GPO). However, the implementation does not yet cover servers and Linux machines. A plan is needed to extend EDR coverage to these systems, ensuring comprehensive protection across the entire network. Additionally, compatibility and deployment strategies for non-Windows systems need to be developed.',
                'status' => ImplementationStatus::FULL->value,
            ],

            'IMPL-L2' => [
                'code' => 'IMPL-L2',
                'title' => 'Data Encryption',
                'details' => 'All sensitive data stored on company servers and transmitted over the network is encrypted using AES-256 encryption standards. Encryption keys are managed through a centralized key management system with strict access controls.',
                'notes' => 'Data encryption is fully implemented for data at rest. Work is ongoing to ensure encryption for data in transit across all platforms. Key rotation policy needs to be established and automated.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],

            'IMPL-L3' => [
                'code' => 'IMPL-L3',
                'title' => 'Security Audits',
                'details' => 'Quarterly security audits are conducted by an internal team, supplemented by an annual audit performed by an external agency. The focus is on network security, policy compliance, and incident response readiness.',
                'notes' => 'Internal audit processes are well-established. Need to finalize the contract with the external agency for annual audits. Also, integrating audit findings into continuous improvement processes is in progress.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],

            'IMPL-L4' => [
                'code' => 'IMPL-L4',
                'title' => 'Incident Response Plan',
                'details' => 'A detailed incident response plan has been developed, covering a range of potential scenarios. Regular training and simulated incident response exercises are conducted to ensure preparedness.',
                'notes' => 'The plan is currently being reviewed for updates based on recent threat landscape changes. Need to schedule the next round of simulation exercises for the new team members.',
                'status' => ImplementationStatus::FULL->value,
            ],

            'IMPL-L5' => [
                'code' => 'IMPL-L5',
                'title' => 'Access Management',
                'details' => 'Access management is enforced using a centralized identity management system. Regular audits are performed to ensure adherence to the least privilege principle and to update access rights based on role changes.',
                'notes' => 'Implementation of the new identity management system is ongoing. Transition from the old system requires careful handling of legacy data and access rights.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],

            'IMPL-L6' => [
                'code' => 'IMPL-L6',
                'title' => 'Cybersecurity Awareness Training',
                'details' => 'All employees undergo mandatory cybersecurity awareness training upon onboarding, with annual refresher courses. The training program includes modules on phishing, secure password practices, and secure internet usage.',
                'notes' => 'The training curriculum is currently being updated to include recent cybersecurity trends and threats. Additionally, exploring options for more interactive and engaging training methods.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],

            'IMPL-L7' => [
                'code' => 'IMPL-L7',
                'title' => 'Network Layered Defense',
                'details' => 'Network architecture includes layered defenses with firewalls, IDS/IPS, and segregated network zones. Regular reviews and updates are conducted to ensure the architecture aligns with current threat intelligence.',
                'notes' => 'Recent network expansion has introduced new challenges in maintaining segmentation. An audit of the current network setup is planned to identify potential improvements.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],

            'IMPL-L8' => [
                'code' => 'IMPL-L8',
                'title' => 'Vulnerability Scanning and Patch Management',
                'details' => 'Vulnerability scanning is conducted bi-weekly, with immediate action on critical vulnerabilities. Patch management is automated for standard software and manually reviewed for critical systems.',
                'notes' => 'Integrating newer scanning tools to cover cloud and containerized environments. Need to streamline patch management for quicker response.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],

            'IMPL-L9' => [
                'code' => 'IMPL-L9',
                'title' => 'MFA Enforcement',
                'details' => 'MFA is enforced for access to all internal systems and cloud services. The implementation includes a combination of hardware tokens, SMS, and mobile app authentication.',
                'notes' => 'User adoption of MFA has been successful, but there are ongoing challenges with hardware token distribution for remote workers. Exploring more scalable solutions.',
                'status' => ImplementationStatus::FULL->value,
            ],

            'IMPL-L10' => [
                'code' => 'IMPL-L10',
                'title' => 'Third-Party Risk Management',
                'details' => 'A third-party risk management program is established, including regular security assessments of vendors and incorporation of security clauses in vendor contracts.',
                'notes' => 'Currently updating the risk assessment framework to include newer types of third-party services. Need to work on improving the contract negotiation process for better security alignment.',
                'status' => ImplementationStatus::PARTIAL->value,
            ],
        ];

        $controlImplementationMap = [
            'L1' => ['IMPL-L1'],
            'L2' => ['IMPL-L2'],
            'L3' => ['IMPL-L3'],
            'L4' => ['IMPL-L4'],
            'L5' => ['IMPL-L5'],
            'L6' => ['IMPL-L6'],
            'L7' => ['IMPL-L7'],
            'L8' => ['IMPL-L8'],
            'L9' => ['IMPL-L9'],
            'L10' => ['IMPL-L10'],
        ];

        DB::transaction(function () use ($controlsData, $implementationsData, $controlImplementationMap) {
            // Insert Controls
            $controlModels = [];
            foreach ($controlsData as $controlData) {
                $controlModels[$controlData['code']] = Control::create($controlData);
            }

            // Insert Implementations
            $implementationModels = [];
            foreach ($implementationsData as $implementationData) {
                $implementationModels[$implementationData['code']] = Implementation::create($implementationData);
            }

            // Establish Relationships
            foreach ($controlImplementationMap as $controlCode => $implementationCodes) {
                if (isset($controlModels[$controlCode])) {
                    $implementationIds = [];
                    foreach ($implementationCodes as $implCode) {
                        if (isset($implementationModels[$implCode])) {
                            $implementationIds[] = $implementationModels[$implCode]->id;
                        }
                    }
                    $controlModels[$controlCode]->implementations()->attach($implementationIds);
                }
            }
        });

        $audit = Audit::create([
            'title' => 'Audit of OpenGRC-1.0 standards',
            'description' => 'Annual internal best practice audit performed in alignment with OpenGRC-1.0 Demonstration Standards',
            'start_date' => '2024-01-01',
            'end_date' => '2024-02-01',
            'audit_type' => 'standards',
            'manager_id' => 1,
        ]);

        foreach ($standard->controls as $ctl) {
            $audit_item = AuditItem::create(
                [
                    'audit_id' => $audit->id,
                    'user_id' => 1,
                    'auditor_notes' => 'Audit performed on this standard.',
                    'status' => array_rand([WorkflowStatus::COMPLETED->value => WorkflowStatus::COMPLETED, WorkflowStatus::INPROGRESS->value => WorkflowStatus::INPROGRESS, WorkflowStatus::NOTSTARTED->value => WorkflowStatus::NOTSTARTED], 1),
                    'effectiveness' => array_rand([Effectiveness::EFFECTIVE->value => Effectiveness::EFFECTIVE, Effectiveness::PARTIAL->value => Effectiveness::PARTIAL, Effectiveness::INEFFECTIVE->value => Effectiveness::INEFFECTIVE, Effectiveness::UNKNOWN->value => Effectiveness::UNKNOWN], 1),
                    'applicability' => array_rand([Applicability::NOTAPPLICABLE->value => Applicability::UNKNOWN, Applicability::NOTAPPLICABLE->value => Applicability::NOTAPPLICABLE, Applicability::APPLICABLE->value => Applicability::APPLICABLE], 1),
                    'auditable_type' => Control::class,
                    'auditable_id' => $ctl->id,
                ]
            );
            $audit_item->save();

            // Create a data request and a data request response for each control.
            $dataRequest = \App\Models\DataRequest::create([
                'code' => 'DR-'.$ctl->code.'-001',
                'created_by_id' => 1,
                'assigned_to_id' => rand(1, 10),
                'audit_id' => $audit->id,
                'audit_item_id' => $audit_item->id,
                'status' => 'Pending',
                'details' => 'Please provide evidence of the implementation of this control',
            ]);

            // Create a data request response for the $dataRequest and assign to a random user
            \App\Models\DataRequestResponse::create([
                'requester_id' => 1,
                'requestee_id' => rand(1, 10),
                'data_request_id' => $dataRequest->id,
                'response' => 'The control is implemented as per the defined standards. The EDR tool is deployed on all workstations and configured for optimal performance. Regular updates and monitoring are in place to ensure effective threat detection and response.',
            ]);

        }

        // Close the Audit
        AuditResource::completeAudit($audit);

        // Create 10 risks from factory
        Risk::factory(10)->create();

        // Create 10 vendors
        $vendors = [];
        $vendorNames = [
            'Microsoft Corporation',
            'Adobe Systems',
            'Salesforce Inc.',
            'Amazon Web Services',
            'Atlassian',
            'Zoom Video Communications',
            'Slack Technologies',
            'DocuSign Inc.',
            'Dropbox Inc.',
            'ServiceNow Inc.',
        ];

        $users = \App\Models\User::all();
        $vendorStatuses = ['Accepted', 'Pending', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Accepted', 'Pending', 'Accepted', 'Accepted'];
        $riskRatings = ['Low', 'Medium', 'Low', 'Medium', 'Low', 'Low', 'Medium', 'Low', 'Low', 'Medium'];

        foreach ($vendorNames as $index => $vendorName) {
            $vendors[] = \App\Models\Vendor::create([
                'name' => $vendorName,
                'description' => $this->faker->sentence(10),
                'url' => $this->faker->url(),
                'vendor_manager_id' => $users->random()->id,
                'status' => $vendorStatuses[$index],
                'risk_rating' => $riskRatings[$index],
                'notes' => $this->faker->optional()->paragraph(),
            ]);
        }

        // Create 25 applications (3 vendors have 2-3 applications each)
        $applications = [
            // Microsoft (vendor 0) - 3 applications
            ['name' => 'Microsoft 365', 'vendor_id' => $vendors[0]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Cloud-based productivity suite including Office apps, email, and collaboration tools', 'status' => 'Approved', 'url' => 'https://www.microsoft.com/microsoft-365'],
            ['name' => 'Microsoft Azure', 'vendor_id' => $vendors[0]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Cloud computing platform for infrastructure, platform, and software services', 'status' => 'Approved', 'url' => 'https://azure.microsoft.com'],
            ['name' => 'Microsoft Defender', 'vendor_id' => $vendors[0]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Enterprise endpoint detection and response solution', 'status' => 'Approved', 'url' => 'https://www.microsoft.com/security/business/threat-protection/microsoft-defender-endpoint'],

            // Adobe (vendor 1) - 2 applications
            ['name' => 'Adobe Creative Cloud', 'vendor_id' => $vendors[1]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Creative suite for design, video, and web development', 'status' => 'Approved', 'url' => 'https://www.adobe.com/creativecloud.html'],
            ['name' => 'Adobe Acrobat Pro', 'vendor_id' => $vendors[1]->id, 'owner_id' => $users->random()->id, 'type' => 'Desktop', 'description' => 'PDF creation and editing software', 'status' => 'Approved', 'url' => 'https://www.adobe.com/acrobat.html'],

            // Atlassian (vendor 4) - 3 applications
            ['name' => 'Jira Software', 'vendor_id' => $vendors[4]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Project and issue tracking software for agile teams', 'status' => 'Approved', 'url' => 'https://www.atlassian.com/software/jira'],
            ['name' => 'Confluence', 'vendor_id' => $vendors[4]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Team collaboration and knowledge management platform', 'status' => 'Approved', 'url' => 'https://www.atlassian.com/software/confluence'],
            ['name' => 'Bitbucket', 'vendor_id' => $vendors[4]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Git repository management and CI/CD platform', 'status' => 'Approved', 'url' => 'https://bitbucket.org'],

            // Single applications from other vendors
            ['name' => 'Salesforce CRM', 'vendor_id' => $vendors[2]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Customer relationship management platform', 'status' => 'Approved', 'url' => 'https://www.salesforce.com'],
            ['name' => 'AWS EC2', 'vendor_id' => $vendors[3]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Elastic cloud computing infrastructure', 'status' => 'Approved', 'url' => 'https://aws.amazon.com/ec2'],
            ['name' => 'Zoom Meetings', 'vendor_id' => $vendors[5]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Video conferencing and webinar platform', 'status' => 'Approved', 'url' => 'https://zoom.us'],
            ['name' => 'Slack Enterprise', 'vendor_id' => $vendors[6]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Team communication and collaboration platform', 'status' => 'Approved', 'url' => 'https://slack.com'],
            ['name' => 'DocuSign eSignature', 'vendor_id' => $vendors[7]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Electronic signature and document management', 'status' => 'Approved', 'url' => 'https://www.docusign.com'],
            ['name' => 'Dropbox Business', 'vendor_id' => $vendors[8]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Cloud file storage and sharing platform', 'status' => 'Approved', 'url' => 'https://www.dropbox.com/business'],
            ['name' => 'ServiceNow ITSM', 'vendor_id' => $vendors[9]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'IT service management and workflow automation', 'status' => 'Approved', 'url' => 'https://www.servicenow.com'],

            // Additional standalone applications - need to create vendors for these too
            ['name' => 'GitHub Enterprise', 'vendor_id' => $vendors[0]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Code hosting and version control platform', 'status' => 'Approved', 'url' => 'https://github.com/enterprise'],
            ['name' => 'Okta Identity Cloud', 'vendor_id' => $vendors[1]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Identity and access management solution', 'status' => 'Approved', 'url' => 'https://www.okta.com'],
            ['name' => 'Splunk Enterprise', 'vendor_id' => $vendors[2]->id, 'owner_id' => $users->random()->id, 'type' => 'Server', 'description' => 'Security information and event management (SIEM)', 'status' => 'Approved', 'url' => 'https://www.splunk.com'],
            ['name' => 'CrowdStrike Falcon', 'vendor_id' => $vendors[3]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Cloud-native endpoint protection platform', 'status' => 'Approved', 'url' => 'https://www.crowdstrike.com'],
            ['name' => 'Tableau', 'vendor_id' => $vendors[4]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Business intelligence and analytics platform', 'status' => 'Approved', 'url' => 'https://www.tableau.com'],
            ['name' => 'Workday HCM', 'vendor_id' => $vendors[5]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Human capital management system', 'status' => 'Approved', 'url' => 'https://www.workday.com'],
            ['name' => 'Zendesk Support', 'vendor_id' => $vendors[6]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Customer service and support ticketing system', 'status' => 'Approved', 'url' => 'https://www.zendesk.com'],
            ['name' => 'Monday.com', 'vendor_id' => $vendors[7]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Work operating system for project management', 'status' => 'Approved', 'url' => 'https://monday.com'],
            ['name' => 'LastPass Enterprise', 'vendor_id' => $vendors[8]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Password management and vault solution', 'status' => 'Approved', 'url' => 'https://www.lastpass.com'],
            ['name' => 'Box Enterprise', 'vendor_id' => $vendors[9]->id, 'owner_id' => $users->random()->id, 'type' => 'SaaS', 'description' => 'Cloud content management and file sharing', 'status' => 'Approved', 'url' => 'https://www.box.com'],
        ];

        foreach ($applications as $appData) {
            \App\Models\Application::create($appData);
        }

        // Create 25 IT assets with hierarchical taxonomy support
        // Get parent taxonomy IDs
        $assetTypeParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'asset-type')->where('type', 'asset')->first();
        $assetStatusParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'asset-status')->where('type', 'asset')->first();
        $assetConditionParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'asset-condition')->where('type', 'asset')->first();

        // Get child term IDs by name
        $assetTypeIds = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $assetTypeParent->id)->pluck('id', 'name');
        $assetStatusIds = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $assetStatusParent->id)->pluck('id', 'name');
        $conditionIds = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $assetConditionParent->id)->pluck('id', 'name');

        $assetsData = [
            // Laptops
            [
                'asset_tag' => 'LAP-001',
                'name' => 'Dell Latitude 5520',
                'serial_number' => 'DL5520-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Laptop'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'Dell',
                'model' => 'Latitude 5520',
                'processor' => 'Intel Core i7-1185G7',
                'ram_gb' => 16,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 512,
                'screen_size' => 15.6,
                'operating_system' => 'Windows 11 Pro',
                'os_version' => '22H2',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(400, 700)),
                'purchase_price' => 1299.99,
                'current_value' => 899.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'LAP-002',
                'name' => 'MacBook Pro 14"',
                'serial_number' => 'MBP14-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Laptop'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Apple',
                'model' => 'MacBook Pro 14-inch M2',
                'processor' => 'Apple M2 Pro',
                'ram_gb' => 32,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 1024,
                'screen_size' => 14.2,
                'operating_system' => 'macOS',
                'os_version' => 'Sonoma 14.1',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(100, 400)),
                'purchase_price' => 2899.99,
                'current_value' => 2299.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'LAP-003',
                'name' => 'HP EliteBook 840',
                'serial_number' => 'HP840-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Laptop'),
                'status_id' => $assetStatusIds->get('Available'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'HP',
                'model' => 'EliteBook 840 G9',
                'processor' => 'Intel Core i5-1235U',
                'ram_gb' => 16,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 256,
                'screen_size' => 14.0,
                'operating_system' => 'Windows 11 Pro',
                'os_version' => '22H2',
                'purchase_date' => now()->subDays(rand(400, 700)),
                'purchase_price' => 1199.99,
                'current_value' => 799.99,
                'is_active' => true,
            ],

            // Desktops
            [
                'asset_tag' => 'DSK-001',
                'name' => 'Dell OptiPlex 7090',
                'serial_number' => 'OP7090-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Desktop'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'Dell',
                'model' => 'OptiPlex 7090 Tower',
                'processor' => 'Intel Core i7-11700',
                'ram_gb' => 32,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 1024,
                'operating_system' => 'Windows 11 Pro',
                'os_version' => '22H2',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(500, 800)),
                'purchase_price' => 1499.99,
                'current_value' => 899.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'DSK-002',
                'name' => 'HP Z2 Workstation',
                'serial_number' => 'HPZ2-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Desktop'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'HP',
                'model' => 'Z2 Tower G9',
                'processor' => 'Intel Xeon W-1370P',
                'ram_gb' => 64,
                'storage_type' => 'NVMe',
                'storage_capacity_gb' => 2048,
                'graphics_card' => 'NVIDIA RTX A4000',
                'operating_system' => 'Windows 11 Pro',
                'os_version' => '22H2',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(200, 500)),
                'purchase_price' => 3499.99,
                'current_value' => 2799.99,
                'is_active' => true,
            ],

            // Servers
            [
                'asset_tag' => 'SRV-001',
                'name' => 'Dell PowerEdge R750',
                'serial_number' => 'PE-R750-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Server'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Dell',
                'model' => 'PowerEdge R750',
                'processor' => 'Dual Intel Xeon Gold 6338',
                'ram_gb' => 256,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 8192,
                'operating_system' => 'Ubuntu Server',
                'os_version' => '22.04 LTS',
                'building' => 'Main Office',
                'floor' => 'Basement',
                'room' => 'Server Room A',
                'purchase_date' => now()->subDays(rand(300, 600)),
                'purchase_price' => 12999.99,
                'current_value' => 9999.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'SRV-002',
                'name' => 'HPE ProLiant DL380',
                'serial_number' => 'HPE-DL380-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Server'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'HPE',
                'model' => 'ProLiant DL380 Gen10 Plus',
                'processor' => 'Dual Intel Xeon Silver 4314',
                'ram_gb' => 128,
                'storage_type' => 'HDD',
                'storage_capacity_gb' => 16384,
                'operating_system' => 'Windows Server',
                'os_version' => '2022',
                'building' => 'Main Office',
                'floor' => 'Basement',
                'room' => 'Server Room A',
                'purchase_date' => now()->subDays(rand(400, 700)),
                'purchase_price' => 8999.99,
                'current_value' => 5999.99,
                'is_active' => true,
            ],

            // Monitors
            [
                'asset_tag' => 'MON-001',
                'name' => 'Dell UltraSharp 27"',
                'serial_number' => 'DU27-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Monitor'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'Dell',
                'model' => 'U2723DE',
                'screen_size' => 27.0,
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(200, 500)),
                'purchase_price' => 549.99,
                'current_value' => 399.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'MON-002',
                'name' => 'LG UltraFine 4K',
                'serial_number' => 'LG4K-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Monitor'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'LG',
                'model' => '27UP850-W',
                'screen_size' => 27.0,
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(100, 400)),
                'purchase_price' => 699.99,
                'current_value' => 549.99,
                'is_active' => true,
            ],

            // Phones
            [
                'asset_tag' => 'PHN-001',
                'name' => 'iPhone 14 Pro',
                'serial_number' => 'IPH14P-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Phone'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Apple',
                'model' => 'iPhone 14 Pro',
                'storage_type' => 'Flash',
                'storage_capacity_gb' => 256,
                'screen_size' => 6.1,
                'operating_system' => 'iOS',
                'os_version' => '17.1',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(200, 500)),
                'purchase_price' => 1099.99,
                'current_value' => 799.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'PHN-002',
                'name' => 'Samsung Galaxy S23',
                'serial_number' => 'SGS23-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Phone'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'Samsung',
                'model' => 'Galaxy S23',
                'storage_type' => 'Flash',
                'storage_capacity_gb' => 128,
                'screen_size' => 6.1,
                'operating_system' => 'Android',
                'os_version' => '14',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(150, 400)),
                'purchase_price' => 799.99,
                'current_value' => 599.99,
                'is_active' => true,
            ],

            // Tablets
            [
                'asset_tag' => 'TAB-001',
                'name' => 'iPad Pro 12.9"',
                'serial_number' => 'IPADP-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Tablet'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Apple',
                'model' => 'iPad Pro 12.9-inch M2',
                'storage_type' => 'Flash',
                'storage_capacity_gb' => 512,
                'screen_size' => 12.9,
                'operating_system' => 'iPadOS',
                'os_version' => '17.1',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(100, 400)),
                'purchase_price' => 1499.99,
                'current_value' => 1199.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'TAB-002',
                'name' => 'Microsoft Surface Pro 9',
                'serial_number' => 'MSP9-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Tablet'),
                'status_id' => $assetStatusIds->get('Available'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'Microsoft',
                'model' => 'Surface Pro 9',
                'processor' => 'Intel Core i7-1255U',
                'ram_gb' => 16,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 256,
                'screen_size' => 13.0,
                'operating_system' => 'Windows 11 Pro',
                'os_version' => '22H2',
                'purchase_date' => now()->subDays(rand(150, 400)),
                'purchase_price' => 1399.99,
                'current_value' => 999.99,
                'is_active' => true,
            ],

            // Network Equipment
            [
                'asset_tag' => 'NET-001',
                'name' => 'Cisco Catalyst 9300',
                'serial_number' => 'CSC9300-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Network Equipment'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Cisco',
                'model' => 'Catalyst 9300-48P',
                'building' => 'Main Office',
                'floor' => 'Basement',
                'room' => 'Network Closet',
                'purchase_date' => now()->subDays(rand(400, 700)),
                'purchase_price' => 8999.99,
                'current_value' => 6499.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'NET-002',
                'name' => 'Ubiquiti UniFi AP',
                'serial_number' => 'UAP-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Network Equipment'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'Ubiquiti',
                'model' => 'UniFi AP AC Pro',
                'building' => 'Main Office',
                'floor' => '2',
                'room' => 'Conference Room',
                'purchase_date' => now()->subDays(rand(300, 600)),
                'purchase_price' => 149.99,
                'current_value' => 89.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'NET-003',
                'name' => 'Fortinet FortiGate 100F',
                'serial_number' => 'FG100F-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Network Equipment'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Fortinet',
                'model' => 'FortiGate 100F',
                'building' => 'Main Office',
                'floor' => 'Basement',
                'room' => 'Server Room A',
                'purchase_date' => now()->subDays(rand(200, 500)),
                'purchase_price' => 2499.99,
                'current_value' => 1899.99,
                'is_active' => true,
            ],

            // Peripherals
            [
                'asset_tag' => 'PRT-001',
                'name' => 'HP LaserJet Pro',
                'serial_number' => 'HPL-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Peripheral'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Good'),
                'manufacturer' => 'HP',
                'model' => 'LaserJet Pro M404dn',
                'building' => 'Main Office',
                'floor' => '2',
                'room' => 'Print Room',
                'purchase_date' => now()->subDays(rand(300, 600)),
                'purchase_price' => 279.99,
                'current_value' => 179.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'PRT-002',
                'name' => 'Canon imageCLASS',
                'serial_number' => 'CAN-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Peripheral'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Fair'),
                'manufacturer' => 'Canon',
                'model' => 'imageCLASS MF445dw',
                'building' => 'Main Office',
                'floor' => '1',
                'room' => 'Reception',
                'purchase_date' => now()->subDays(rand(500, 800)),
                'purchase_price' => 399.99,
                'current_value' => 199.99,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'KEY-001',
                'name' => 'Logitech MX Keys',
                'serial_number' => 'LGMX-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Peripheral'),
                'status_id' => $assetStatusIds->get('In Use'),
                'condition_id' => $conditionIds->get('Excellent'),
                'manufacturer' => 'Logitech',
                'model' => 'MX Keys Advanced',
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 365)),
                'purchase_date' => now()->subDays(rand(100, 300)),
                'purchase_price' => 99.99,
                'current_value' => 79.99,
                'is_active' => true,
            ],

            // Software Licenses
            [
                'asset_tag' => 'LIC-001',
                'name' => 'Adobe Creative Cloud',
                'asset_type_id' => $assetTypeIds->get('Software License'),
                'status_id' => $assetStatusIds->get('In Use'),
                'license_type' => 'Per-user subscription',
                'license_seats' => 15,
                'license_expiry_date' => now()->addMonths(6),
                'assigned_to_user_id' => $users->random()->id,
                'assigned_at' => now()->subDays(rand(30, 180)),
                'purchase_date' => now()->subYear(),
                'purchase_price' => 7199.85,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'LIC-002',
                'name' => 'Microsoft 365 E5',
                'asset_type_id' => $assetTypeIds->get('Software License'),
                'status_id' => $assetStatusIds->get('In Use'),
                'license_type' => 'Per-user subscription',
                'license_seats' => 50,
                'license_expiry_date' => now()->addYear(),
                'purchase_date' => now()->subMonths(3),
                'purchase_price' => 28800.00,
                'is_active' => true,
            ],
            [
                'asset_tag' => 'LIC-003',
                'name' => 'Jira Software Cloud',
                'asset_type_id' => $assetTypeIds->get('Software License'),
                'status_id' => $assetStatusIds->get('In Use'),
                'license_type' => 'Per-user subscription',
                'license_seats' => 25,
                'license_expiry_date' => now()->addMonths(9),
                'purchase_date' => now()->subMonths(3),
                'purchase_price' => 2625.00,
                'is_active' => true,
            ],

            // Retired/Other assets
            [
                'asset_tag' => 'LAP-004',
                'name' => 'Dell Latitude E7470',
                'serial_number' => 'DLE7470-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Laptop'),
                'status_id' => $assetStatusIds->get('Retired'),
                'condition_id' => $conditionIds->get('Poor'),
                'manufacturer' => 'Dell',
                'model' => 'Latitude E7470',
                'processor' => 'Intel Core i5-6300U',
                'ram_gb' => 8,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 256,
                'screen_size' => 14.0,
                'operating_system' => 'Windows 10 Pro',
                'os_version' => '21H2',
                'purchase_date' => now()->subYears(5),
                'purchase_price' => 1299.99,
                'current_value' => 199.99,
                'retirement_date' => now()->subMonths(2),
                'is_active' => false,
            ],
            [
                'asset_tag' => 'SRV-003',
                'name' => 'Dell PowerEdge R640',
                'serial_number' => 'PE-R640-'.strtoupper($this->faker->bothify('???###')),
                'asset_type_id' => $assetTypeIds->get('Server'),
                'status_id' => $assetStatusIds->get('In Repair'),
                'condition_id' => $conditionIds->get('Fair'),
                'manufacturer' => 'Dell',
                'model' => 'PowerEdge R640',
                'processor' => 'Dual Intel Xeon Silver 4210',
                'ram_gb' => 128,
                'storage_type' => 'SSD',
                'storage_capacity_gb' => 4096,
                'operating_system' => 'VMware ESXi',
                'os_version' => '7.0',
                'building' => 'Main Office',
                'floor' => 'Basement',
                'room' => 'Server Room B',
                'purchase_date' => now()->subYears(3),
                'purchase_price' => 9999.99,
                'current_value' => 4999.99,
                'is_active' => true,
            ],
        ];

        foreach ($assetsData as $assetData) {
            \App\Models\Asset::create($assetData);
        }

    }
}
