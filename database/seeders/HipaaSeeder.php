<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Http\Controllers\HelperController;
use App\Models\Standard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

class HipaaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('standards')->insert([
            'name' => 'HIPAA Security Rule',
            'code' => 'HIPAA-Security',
            'authority' => 'CMS',
            'reference_url' => 'https://www.hhs.gov/hipaa/for-professionals/security/laws-regulations/index.html',
            'description' => "The HIPAA Security Rule is a federal regulation that sets national standards for
            protecting individuals' electronic protected health information (ePHI). It mandates that covered entities
            and their business associates implement administrative, physical, and technical safeguards to ensure the
            confidentiality, integrity, and availability of ePHI. The rule is designed to be flexible and scalable,
            allowing organizations to assess their own risks and implement appropriate measures accordingly. Compliance
            with the Security Rule helps prevent unauthorized access, data breaches, and other security incidents that
            could compromise patient privacy and trust.",
        ]);

        $csv = Reader::createFromPath(resource_path('data/hipaa-security-rule.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        // Retrieve the standard_id using DB Query Builder
        $standardId = DB::table('standards')->where('code', 'HIPAA-Security')->value('id');

        foreach ($records as $record) {
            // Inserting data into 'controls' table
            DB::table('controls')->insert([
                'standard_id' => $standardId,
                'code' => $record['Code'],
                'title' => $record['Title'],
                'type' => $record['Type'] ?? ControlType::OTHER,
                'category' => $record['Category'] ?? ControlCategory::UNKNOWN,
                'enforcement' => $record['Enforcement'] ?? ControlEnforcementCategory::UNKNOWN,
                'discussion' => $record['Discussion'],
                'description' => HelperController::linesToParagraphs($record['Details'], 'control-description-text'),
            ]);
        }
    }
}
