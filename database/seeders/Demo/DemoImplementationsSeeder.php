<?php

namespace Database\Seeders\Demo;

use App\Enums\ImplementationStatus;
use App\Models\Implementation;
use Illuminate\Database\Seeder;

class DemoImplementationsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        $this->seedImplementations();
        $this->createTscImplementations();
    }

    private function seedImplementations(): void
    {
        $implementationsData = [
            [
                'code' => 'IMPL-L1',
                'title' => 'EDR Deployment on Workstations',
                'details' => 'Enterprise Detection and Response (EDR) tool, specifically Microsoft Defender for Endpoint, is deployed across all workstations within the organization.',
                'notes' => 'Currently deployed on all Windows workstations through GPO. Linux coverage in progress.',
                'status' => ImplementationStatus::FULL,
            ],
            [
                'code' => 'IMPL-L2',
                'title' => 'Data Encryption Program',
                'details' => 'All sensitive data stored on company servers is encrypted using AES-256 encryption. TLS 1.3 enforced for data in transit.',
                'notes' => 'Data at rest encryption complete. Working on certificate management automation.',
                'status' => ImplementationStatus::PARTIAL,
            ],
            [
                'code' => 'IMPL-L3',
                'title' => 'Security Audit Program',
                'details' => 'Quarterly internal security audits supplemented by annual external penetration testing and SOC 2 audit.',
                'notes' => 'Internal audit processes established. External auditor engaged for annual review.',
                'status' => ImplementationStatus::FULL,
            ],
            [
                'code' => 'IMPL-L4',
                'title' => 'Incident Response Program',
                'details' => 'Comprehensive incident response plan with defined playbooks for common scenarios. 24/7 on-call rotation established.',
                'notes' => 'Tabletop exercises conducted quarterly. Automated alerting configured.',
                'status' => ImplementationStatus::FULL,
            ],
            [
                'code' => 'IMPL-L5',
                'title' => 'Identity and Access Management',
                'details' => 'Centralized identity management using Okta. Role-based access control implemented with quarterly access reviews.',
                'notes' => 'Migrating legacy applications to SSO. Privileged access management in deployment.',
                'status' => ImplementationStatus::PARTIAL,
            ],
            [
                'code' => 'IMPL-L6',
                'title' => 'Security Awareness Program',
                'details' => 'Annual security awareness training for all employees with monthly phishing simulations.',
                'notes' => 'Training platform integrated with HRIS. Completion tracking automated.',
                'status' => ImplementationStatus::FULL,
            ],
            [
                'code' => 'IMPL-L7',
                'title' => 'Network Security Architecture',
                'details' => 'Zero-trust network architecture with micro-segmentation. Next-gen firewalls deployed at all egress points.',
                'notes' => 'Network segmentation project 80% complete. Cloud network security being enhanced.',
                'status' => ImplementationStatus::PARTIAL,
            ],
            [
                'code' => 'IMPL-L8',
                'title' => 'Vulnerability Management Program',
                'details' => 'Weekly vulnerability scanning with automated prioritization. SLA-based remediation timelines.',
                'notes' => 'Integrating container scanning. Expanding coverage to cloud workloads.',
                'status' => ImplementationStatus::PARTIAL,
            ],
            [
                'code' => 'IMPL-L9',
                'title' => 'MFA Enforcement',
                'details' => 'MFA required for all internal applications and VPN access. Hardware tokens for privileged users.',
                'notes' => 'Passwordless authentication pilot in progress.',
                'status' => ImplementationStatus::FULL,
            ],
            [
                'code' => 'IMPL-L10',
                'title' => 'Vendor Security Assessment Program',
                'details' => 'Risk-tiered vendor assessment process with security questionnaires and document reviews.',
                'notes' => 'Continuous monitoring implementation planned for Q3.',
                'status' => ImplementationStatus::PARTIAL,
            ],
            [
                'code' => 'IMPL-BC1',
                'title' => 'Backup and Recovery',
                'details' => 'Automated daily backups with off-site replication. Annual recovery testing.',
                'notes' => 'Implementing immutable backup storage.',
                'status' => ImplementationStatus::FULL,
            ],
            [
                'code' => 'IMPL-LOG1',
                'title' => 'Centralized Logging and SIEM',
                'details' => 'Splunk SIEM deployed with log aggregation from all critical systems. 90-day retention.',
                'notes' => 'Expanding correlation rules. Adding cloud log sources.',
                'status' => ImplementationStatus::PARTIAL,
            ],
        ];

        foreach ($implementationsData as $implData) {
            $this->context->implementations[] = Implementation::create([
                'code' => $implData['code'],
                'title' => $implData['title'],
                'details' => $implData['details'],
                'notes' => $implData['notes'],
                'status' => $implData['status']->value,
                'implementation_owner_id' => $this->context->users[array_rand($this->context->users)]->id,
            ]);
        }

        // Link OpenGRC controls to implementations (first 12 implementations for first 12 controls)
        $openGrcControls = array_filter($this->context->controls, fn ($c) => ! in_array($c, $this->context->tscControls, true));
        foreach (array_values($openGrcControls) as $index => $control) {
            if (isset($this->context->implementations[$index])) {
                $control->implementations()->attach($this->context->implementations[$index]->id);
            }
        }
    }

    private function createTscImplementations(): void
    {
        $implementationTemplates = [
            // Access Control implementations
            ['title' => 'Role-Based Access Control System', 'details' => 'Implemented RBAC using Azure AD with defined roles for different job functions. Access requests require manager approval through ServiceNow workflow.', 'notes' => 'Quarterly access reviews conducted.'],
            ['title' => 'Privileged Access Management', 'details' => 'CyberArk PAM solution deployed for privileged account management. Session recording enabled for all administrative access.', 'notes' => 'Just-in-time access provisioning enabled.'],
            ['title' => 'Single Sign-On Integration', 'details' => 'Okta SSO deployed across all SaaS applications. SAML 2.0 federation configured with on-premise Active Directory.', 'notes' => 'MFA enforced for all SSO sessions.'],

            // Network Security implementations
            ['title' => 'Next-Generation Firewall Deployment', 'details' => 'Palo Alto Networks firewalls deployed at network perimeter with application-layer filtering and threat prevention enabled.', 'notes' => 'IPS signatures updated daily.'],
            ['title' => 'Network Segmentation', 'details' => 'VLAN-based network segmentation implemented separating production, development, and corporate networks. Inter-VLAN routing restricted by firewall policies.', 'notes' => 'Micro-segmentation in progress for cloud workloads.'],
            ['title' => 'Intrusion Detection System', 'details' => 'Darktrace AI-powered network detection deployed across all network segments. Real-time alerting to SOC team.', 'notes' => 'Behavioral baselines established for all endpoints.'],
            ['title' => 'Web Application Firewall', 'details' => 'Cloudflare WAF deployed for all public-facing applications with OWASP Top 10 rule sets enabled.', 'notes' => 'Custom rules added for application-specific threats.'],

            // Data Protection implementations
            ['title' => 'Database Encryption', 'details' => 'Transparent Data Encryption (TDE) enabled on all SQL Server and PostgreSQL databases. Key management via Azure Key Vault.', 'notes' => 'Customer data encrypted with AES-256.'],
            ['title' => 'Data Loss Prevention', 'details' => 'Microsoft Purview DLP policies configured to detect and block sensitive data exfiltration via email, cloud storage, and endpoint channels.', 'notes' => 'PII and PCI patterns monitored.'],
            ['title' => 'Backup Encryption', 'details' => 'All backup data encrypted using AES-256 before transmission to offsite storage. Encryption keys rotated quarterly.', 'notes' => 'Backup integrity verified weekly.'],
            ['title' => 'TLS Encryption for Data in Transit', 'details' => 'TLS 1.3 enforced for all external communications. Internal services use mutual TLS authentication.', 'notes' => 'Certificate management automated via Let\'s Encrypt.'],

            // Monitoring & Logging implementations
            ['title' => 'Security Information and Event Management', 'details' => 'Splunk Enterprise deployed as centralized SIEM. Log sources include firewalls, endpoints, cloud services, and applications.', 'notes' => '90-day hot storage, 1-year cold storage retention.'],
            ['title' => 'Endpoint Detection and Response', 'details' => 'CrowdStrike Falcon deployed on all endpoints with real-time threat detection and automated response capabilities.', 'notes' => 'Threat hunting performed weekly.'],
            ['title' => 'Cloud Security Monitoring', 'details' => 'AWS CloudTrail and Azure Monitor configured for all cloud accounts. Alerts configured for high-risk API calls.', 'notes' => 'GuardDuty enabled for AWS threat detection.'],
            ['title' => 'Application Performance Monitoring', 'details' => 'Datadog APM deployed across all production applications with distributed tracing and error tracking.', 'notes' => 'SLO dashboards published for stakeholders.'],

            // Vulnerability Management implementations
            ['title' => 'Automated Vulnerability Scanning', 'details' => 'Qualys vulnerability scanner configured for weekly authenticated scans of all internal and external assets.', 'notes' => 'Critical vulnerabilities require 7-day remediation SLA.'],
            ['title' => 'Container Security Scanning', 'details' => 'Snyk integrated into CI/CD pipeline for container image scanning. Vulnerabilities blocked at build time based on severity.', 'notes' => 'Base images updated monthly.'],
            ['title' => 'Penetration Testing Program', 'details' => 'Annual third-party penetration testing conducted by certified ethical hackers. Scope includes external network, web applications, and social engineering.', 'notes' => 'Findings tracked in Jira until remediation.'],
            ['title' => 'Patch Management System', 'details' => 'WSUS and Ansible deployed for automated patch deployment. Critical patches deployed within 72 hours of release.', 'notes' => 'Patch compliance reported monthly.'],

            // Incident Response implementations
            ['title' => 'Incident Response Platform', 'details' => 'PagerDuty configured for incident alerting and on-call management. Runbooks documented for common incident types.', 'notes' => 'MTTR tracked and reported weekly.'],
            ['title' => 'Security Orchestration and Automation', 'details' => 'Splunk SOAR deployed for automated incident triage and response. Playbooks created for phishing, malware, and unauthorized access.', 'notes' => 'Average response time reduced by 60%.'],
            ['title' => 'Forensic Investigation Toolkit', 'details' => 'Dedicated forensic workstation with EnCase and Volatility for incident investigation. Chain of custody procedures documented.', 'notes' => 'Forensic images stored in air-gapped storage.'],

            // Business Continuity implementations
            ['title' => 'Disaster Recovery Site', 'details' => 'Hot standby environment maintained in secondary AWS region with automated failover via Route 53 health checks.', 'notes' => 'DR tests conducted quarterly.'],
            ['title' => 'Automated Backup System', 'details' => 'Veeam backup solution deployed for all virtual machines with hourly incremental and daily full backups.', 'notes' => 'Immutable backups enabled for ransomware protection.'],
            ['title' => 'High Availability Architecture', 'details' => 'Multi-AZ deployment for all critical services with automatic failover. Load balancers configured with health checks.', 'notes' => '99.99% uptime SLA achieved.'],

            // Change Management implementations
            ['title' => 'Change Advisory Board Process', 'details' => 'Weekly CAB meetings to review and approve changes. Emergency change process documented for critical fixes.', 'notes' => 'Change success rate tracked in ServiceNow.'],
            ['title' => 'Infrastructure as Code', 'details' => 'Terraform used for all infrastructure provisioning. Changes deployed through CI/CD pipeline with automated testing.', 'notes' => 'Drift detection enabled via Terraform Cloud.'],
            ['title' => 'Code Review Requirements', 'details' => 'All code changes require peer review via GitHub pull requests. CODEOWNERS configured for critical paths.', 'notes' => 'Static analysis gates block merging of vulnerable code.'],

            // Security Awareness implementations
            ['title' => 'Phishing Simulation Program', 'details' => 'KnowBe4 platform used for monthly phishing simulations. Users who click are automatically enrolled in remedial training.', 'notes' => 'Click rate reduced from 15% to 3% over 12 months.'],
            ['title' => 'Security Awareness Training', 'details' => 'Annual security awareness training mandatory for all employees. Topics include phishing, password security, and data handling.', 'notes' => 'Training completion tracked in HRIS.'],
            ['title' => 'Secure Development Training', 'details' => 'OWASP-based secure coding training required for all developers. Annual certification renewal required.', 'notes' => 'Training integrated into onboarding process.'],

            // Compliance & Audit implementations
            ['title' => 'Compliance Management Platform', 'details' => 'OneTrust GRC platform deployed for compliance tracking. Evidence collection automated via API integrations.', 'notes' => 'SOC 2 controls mapped to platform.'],
            ['title' => 'Audit Log Retention', 'details' => 'All security logs retained for 7 years in immutable storage. Logs encrypted and integrity-verified.', 'notes' => 'WORM storage prevents log tampering.'],
            ['title' => 'Policy Management System', 'details' => 'SharePoint-based policy repository with version control and annual review workflow. Employee acknowledgment tracked.', 'notes' => 'Policy exceptions require CISO approval.'],

            // Physical Security implementations
            ['title' => 'Data Center Access Control', 'details' => 'Biometric access control deployed at all data center facilities. Visitor logs maintained and reviewed monthly.', 'notes' => 'CCTV footage retained for 90 days.'],
            ['title' => 'Environmental Monitoring', 'details' => 'Temperature, humidity, and water leak sensors deployed in server rooms. Alerts sent to facilities team.', 'notes' => 'UPS and generator tested monthly.'],

            // Vendor Management implementations
            ['title' => 'Vendor Risk Assessment Program', 'details' => 'Security questionnaires required for all vendors handling sensitive data. Risk ratings determine review frequency.', 'notes' => 'Critical vendors assessed annually.'],
            ['title' => 'Third-Party Penetration Testing', 'details' => 'Critical vendors required to provide annual penetration test results or SOC 2 reports.', 'notes' => 'Vendor inventory maintained in ServiceNow.'],

            // Identity & Authentication implementations
            ['title' => 'Multi-Factor Authentication', 'details' => 'Duo MFA deployed for all applications. Hardware tokens provided for privileged users.', 'notes' => 'Passwordless authentication pilot in progress.'],
            ['title' => 'Password Policy Enforcement', 'details' => 'Active Directory password policy enforces 14-character minimum with complexity requirements. Password history prevents reuse.', 'notes' => 'Password manager recommended for all users.'],
            ['title' => 'Certificate-Based Authentication', 'details' => 'Smart card authentication deployed for administrative access to critical systems. PKI infrastructure maintained internally.', 'notes' => 'Certificate lifecycle automated.'],

            // Asset Management implementations
            ['title' => 'IT Asset Management System', 'details' => 'ServiceNow CMDB deployed for tracking all IT assets. Automated discovery via agents and network scanning.', 'notes' => 'Asset lifecycle managed from procurement to disposal.'],
            ['title' => 'Mobile Device Management', 'details' => 'Microsoft Intune deployed for all corporate mobile devices. Remote wipe capability enabled.', 'notes' => 'BYOD policy enforces container separation.'],
            ['title' => 'Software Asset Management', 'details' => 'Flexera deployed for software license tracking and compliance. Unauthorized software blocked via application whitelisting.', 'notes' => 'Shadow IT discovery enabled.'],
        ];

        $statuses = [
            ImplementationStatus::FULL,
            ImplementationStatus::FULL,
            ImplementationStatus::FULL,
            ImplementationStatus::PARTIAL,
            ImplementationStatus::PARTIAL,
            ImplementationStatus::NONE,
        ];

        $implCounter = 1;

        foreach ($this->context->tscControls as $control) {
            // Create 1-3 random implementations per TSC control
            $numImplementations = rand(1, 3);
            $usedTemplates = [];

            for ($i = 0; $i < $numImplementations; $i++) {
                // Select a random template that hasn't been used for this control
                do {
                    $templateIndex = array_rand($implementationTemplates);
                } while (in_array($templateIndex, $usedTemplates, true));

                $usedTemplates[] = $templateIndex;
                $template = $implementationTemplates[$templateIndex];

                $implementation = Implementation::create([
                    'code' => 'TSC-IMPL-'.str_pad((string) $implCounter++, 3, '0', STR_PAD_LEFT),
                    'title' => $template['title'],
                    'details' => $template['details'],
                    'notes' => $template['notes'],
                    'status' => $statuses[array_rand($statuses)]->value,
                    'implementation_owner_id' => $this->context->users[array_rand($this->context->users)]->id,
                ]);

                $this->context->implementations[] = $implementation;

                // Link this implementation to the TSC control
                $control->implementations()->attach($implementation->id);
            }
        }
    }
}
