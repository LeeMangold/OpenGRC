<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class SP800218Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserting data into 'standards' table
        DB::table('standards')->insert([
            'name' => 'NIST SP 800-218',
            'code' => '800-218',
            'authority' => 'NIST',
            'reference_url' => 'https://csrc.nist.gov/publications/detail/sp/800-218/final',
            'description' => 'This publication provides a framework for the development of security controls for
            information systems and organizations. It is intended for use by organizations and individuals
            responsible for the development, implementation, and maintenance of security controls for
            information systems and organizations.',
        ]);

        $csv = Reader::createFromPath(resource_path('data/sp800-218.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        $standardId = DB::table('standards')->where('code', '800-218')->value('id');

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
