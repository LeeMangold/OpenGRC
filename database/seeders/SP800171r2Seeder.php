<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Http\Controllers\HelperController;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

class SP800171r2Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserting data into 'standards' table
        DB::table('standards')->insert([
            'name' => 'NIST SP 800-171r2',
            'code' => '800-171r2',
            'authority' => 'NIST',
            'reference_url' => 'https://csrc.nist.gov/publications/detail/sp/800-171/rev-2/final',
            'description' => 'The protection of Controlled Unclassified Information (CUI) resident in nonfederal
            systems and organizations is of paramount importance to federal agencies and can directly impact the
            ability of the federal government to successfully conduct its essential missions and functions. This
            publication provides agencies with recommended security requirements for protecting the confidentiality
            of CUI when the information is resident in nonfederal systems and organizations; when the nonfederal
            organization is not collecting or maintaining information on behalf of a federal agency or using or
            operating a system on behalf of an agency; and where there are no specific safeguarding requirements
            for protecting the confidentiality of CUI prescribed by the authorizing law, regulation, or governmentwide
            policy for the CUI category listed in the CUI Registry. The requirements apply to all components of
            nonfederal systems and organizations that process, store, and/or transmit CUI, or that provide protection
            for such components. The security requirements are intended for use by federal agencies in contractual
            vehicles or other agreements established between those agencies and nonfederal organizations.',
        ]);

        $csv = Reader::createFromPath(resource_path('data/sp800-171r2.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        // Retrieve the standard_id using DB Query Builder
        $standardId = DB::table('standards')->where('code', '800-171r2')->value('id');

        foreach ($records as $record) {
            // Inserting data into 'controls' table
            DB::table('controls')->insert([
                'standard_id' => $standardId,
                'code' => $record['Identifier'],
                'title' => $record['Security Requirement'],
                'type' => $record['Type'] ?? ControlType::OTHER,
                'category' => $record['Category'] ?? ControlCategory::UNKNOWN,
                'enforcement' => $record['Enforcement'] ?? ControlEnforcementCategory::UNKNOWN,
                'discussion' => '',
                'description' => HelperController::linesToParagraphs($record['Description'], 'control-description-text'),
            ]);
        }
    }
}
