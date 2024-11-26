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

class SP80053LowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserting data into 'standards' table
        DB::table('standards')->insert([
            'name' => 'NIST SP 800-53 Security Baseline (Low)',
            'code' => '800-53 (Low)',
            'authority' => 'NIST',
            'reference_url' => 'https://csrc.nist.gov/publications/detail/sp/800-53/rev-5/final',
            'description' => 'The NIST Special Publication 800-53 Low Baseline is a set of federal security controls
            specifically designed for information systems with a low impact level in terms of confidentiality,
            integrity, and availability. This baseline, part of the broader NIST 800-53 framework, aims to provide a
            foundational level of security for systems where the potential damage from a security breach is considered
            limited or minor. The low baseline controls are tailored to be less stringent than those for moderate or
            high-impact systems, striking a balance between necessary security measures and resource allocation. These
            controls cover various aspects of information security such as access control, audit and accountability,
            incident response, and system and information integrity, but with a scope and depth commensurate with the
            lower risk level. The NIST SP 800-53 Low Baseline is integral for organizations that need to comply with
            federal standards, offering a structured approach to securing systems while aligning with the overall risk
            management strategy.',
        ]);

        $csv = Reader::createFromPath(resource_path('data/sp80053Low.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        // Retrieve the standard_id using DB Query Builder
        $standardId = DB::table('standards')->where('code', '800-53 (Low)')->value('id');

        foreach ($records as $record) {
            // Inserting data into 'controls' table
            DB::table('controls')->insert([
                'standard_id' => $standardId,
                'code' => $record['Code'],
                'title' => $record['Title'],
                'type' => $record['Type'] ?? ControlType::OTHER,
                'category' => $record['Category'] ?? ControlCategory::UNKNOWN,
                'enforcement' => $record['Enforcement'] ?? ControlEnforcementCategory::UNKNOWN,
                'discussion' => HelperController::linesToParagraphs($record['Discussion'], 'control-discussion-text'),
                'description' => HelperController::linesToParagraphs($record['Description'], 'control-description-text'),
            ]);
        }
    }
}
