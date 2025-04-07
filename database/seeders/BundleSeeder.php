<?php

namespace Database\Seeders;

use App\Models\Bundle;
use Illuminate\Database\Seeder;

class BundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Bundle::create([
            'name' => 'Protecting Controlled Unclassified Information in Nonfederal Systems and Organizations',
            'code' => '800-171',
            'version' => '3.0.0',
            'authority' => 'NIST',
            'description' => 'The protection of Controlled Unclassified Information (CUI) is of paramount importance to federal agencies and can directly impact the ability of the Federal Government to successfully conduct its essential missions and functions. This publication provides federal agencies with recommended security requirements for protecting the confidentiality of CUI when the information is resident in nonfederal systems and organizations. The requirements apply to components of nonfederal systems that process, store, or transmit CUI or that provide protection for such components. The security requirements are intended for use by federal agencies in contractual vehicles or other agreements established between those agencies and nonfederal organizations. This publication can be used in conjunction with its companion publication, NIST Special Publication 800-171A, which provides a comprehensive set of procedures to assess the security requirements.',
            'source_url' => 'https://csrc.nist.gov/pubs/sp/800/171/r3/final',
            'filename' => 'NIST_SP-800-171_rev2.pdf',
            'image' => 'nist_sp-800-171_rev2.jpg',
        ]);

        Bundle::create([
            'name' => 'HIPAA Security Rule',
            'code' => 'HIPAA-Security',
            'version' => 'Current',
            'authority' => 'HHS',
            'description' => 'The HIPAA Security Rule establishes national standards to protect individuals’ electronic personal health information that is created, received, used, or maintained by a covered entity. It addresses the technical and non-technical safeguards that organizations must put in place to secure individuals’ ePHI.',
            'source_url' => 'https://www.hhs.gov/hipaa/for-professionals/security/index.html',
            'filename' => 'hipaa_security_rule.pdf',
            'image' => 'hipaa_security_rule.jpg',
        ]);

        Bundle::create([
            'name' => 'CIS Critical Security Controls v8',
            'code' => 'CSCv8',
            'version' => '8.0',
            'authority' => 'CIS',
            'description' => 'The CIS Critical Security Controls v8 are a prioritized set of actions that help protect organizations and data from known cyber attack vectors. They focus on key actions to defend and continuously improve security environments.',
            'source_url' => 'https://www.cisecurity.org/controls/v8',
            'filename' => 'cis_csc_v8.pdf',
            'image' => 'cis_csc_v8.jpg',
        ]);

        Bundle::create([
            'name' => 'Security and Privacy Controls for Federal Information Systems and Organizations (Low Impact)',
            'code' => '800-53-Low',
            'version' => '5.0.0',
            'authority' => 'NIST',
            'description' => 'NIST SP 800-53 provides a catalog of security and privacy controls for federal information systems and organizations. This low-impact baseline includes a minimal set of safeguards and countermeasures that protect low-impact systems and the information processed by those systems.',
            'source_url' => 'https://csrc.nist.gov/publications/detail/sp/800-53/rev-5/final',
            'filename' => 'nist_sp-800-53_rev5_low.pdf',
            'image' => 'nist_sp-800-53_rev5_low.jpg',
        ]);

        Bundle::create([
            'name' => 'Payment Card Industry Data Security Standard',
            'code' => 'PCI-DSS',
            'version' => '4.0',
            'authority' => 'PCI SSC',
            'description' => 'The PCI Data Security Standard provides an actionable framework for developing a robust payment card data security process including prevention, detection, and appropriate reaction to security incidents.',
            'source_url' => 'https://www.pcisecuritystandards.org/document_library',
            'filename' => 'pci_dss_v4.pdf',
            'image' => 'pci_dss_v4.jpg',
        ]);

        Bundle::create([
            'name' => 'ISO/IEC 27001',
            'code' => 'ISO-27001',
            'version' => '2022',
            'authority' => 'ISO',
            'description' => 'ISO/IEC 27001 is the leading international standard focused on information security management. It provides a framework of policies and procedures that includes all legal, physical, and technical controls involved in an organization’s information risk management processes.',
            'source_url' => 'https://www.iso.org/standard/82875.html',
            'filename' => 'iso_27001_2022.pdf',
            'image' => 'iso_27001_2022.jpg',
        ]);

        Bundle::create([
            'name' => 'SOC 2 Type 2',
            'code' => 'SOC2-Type2',
            'version' => 'Current',
            'authority' => 'AICPA',
            'description' => 'SOC 2 Type 2 reports cover the principles of security, availability, processing integrity, confidentiality, and privacy, and include testing of the design and operating effectiveness of an organization’s controls over a period of time.',
            'source_url' => 'https://www.aicpa.org/interestareas/frc/assuranceadvisoryservices/sorhome',
            'filename' => 'soc2_type2_report.pdf',
            'image' => 'soc2_type2.jpg',
        ]);

        // Bundle::factory()->count(10)->create();

    }
}
