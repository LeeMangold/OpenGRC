<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use League\Csv\Reader;
use League\Csv\Statement;

class CJISv6Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserting data into 'standards' table
        DB::table('standards')->insert([
            'name' => 'CJIS Security Policy v6.0',
            'code' => 'CJIS-v6',
            'authority' => 'FBI CJIS',
            'reference_url' => 'https://www.fbi.gov/services/cjis/cjis-security-policy-resource-center',
            'description' => 'The CJIS Security Policy provides Criminal Justice Agencies (CJA) and Noncriminal Justice Agencies (NCJA) with a minimum set of security requirements for access to FBI CJIS Division systems and information. The policy covers the life cycle of criminal justice information (CJI), from creation and collection through dissemination and destruction. The requirements are intended to ensure the protection, confidentiality, integrity, and availability of CJI regardless of whether the information is at rest or in transit.',
        ]);

        $csv = Reader::createFromPath(resource_path('data/cjis-v6.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        // Retrieve the standard_id using DB Query Builder
        $standardId = DB::table('standards')->where('code', 'CJIS-v6')->value('id');

        // Check if the new columns exist (in case migration hasn't been run)
        $hasAuditSanctionDate = Schema::hasColumn('controls', 'audit_sanction_date');
        $hasPriority = Schema::hasColumn('controls', 'priority');

        foreach ($records as $record) {
            // Build the base control data
            $controlData = [
                'standard_id' => $standardId,
                'code' => $record['code'],
                'title' => $record['title'],
                'description' => $record['requirement'],
                'type' => ControlType::OTHER,
                'category' => ControlCategory::UNKNOWN,
                'enforcement' => ControlEnforcementCategory::MANDATORY,
                'discussion' => '',
            ];

            // Only add these fields if the columns exist
            if ($hasAuditSanctionDate) {
                $controlData['audit_sanction_date'] = $record['audit_sanction_date'] ?: null;
            }
            if ($hasPriority) {
                $controlData['priority'] = $record['priority'] ?: null;
            }

            // Inserting data into 'controls' table
            DB::table('controls')->insert($controlData);
        }
    }
}
