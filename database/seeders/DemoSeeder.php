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

    }
}
