<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

class SP800171r3Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserting data into 'standards' table
        DB::table('standards')->insert([
            'name' => 'NIST SP 800-171r3',
            'code' => '800-171r3',
            'authority' => 'NIST',
            'reference_url' => 'https://csrc.nist.gov/pubs/sp/800/171/r3/final',
            'description' => 'The protection of Controlled Unclassified Information (CUI) is of paramount importance to federal agencies and can directly impact the ability of the Federal Government to successfully conduct its essential missions and functions. This publication provides federal agencies with recommended security requirements for protecting the confidentiality of CUI when the information is resident in nonfederal systems and organizations. The requirements apply to components of nonfederal systems that process, store, or transmit CUI or that provide protection for such components. The security requirements are intended for use by federal agencies in contractual vehicles or other agreements established between those agencies and nonfederal organizations. This publication can be used in conjunction with its companion publication, NIST Special Publication 800-171A, which provides a comprehensive set of procedures to assess the security requirements.',
        ]);

        $csv = Reader::createFromPath(resource_path('data/sp800-171r3.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        // Retrieve the standard_id using DB Query Builder
        $standardId = DB::table('standards')->where('code', '800-171r3')->value('id');

        foreach ($records as $record) {
            // Inserting data into 'controls' table
            DB::table('controls')->insert([
                'standard_id' => $standardId,
                'code' => $record['Identifier'],
                'title' => $record['Security Requirement'],
                'type' => $record['Type'] ?? ControlType::OTHER,
                'category' => $record['Category'] ?? ControlCategory::UNKNOWN,
                'enforcement' => $record['Enforcement'] ?? ControlEnforcementCategory::MANDATORY,
                'discussion' => '',
                'description' => $record['Description'],
            ]);
        }
    }
}
