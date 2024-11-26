<?php

namespace Database\Seeders;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\StandardStatus;
use App\Http\Controllers\HelperController;
use App\Models\Standard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use League\Csv\Statement;

class CSCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inserting data into 'standards' table
        DB::table('standards')->insert([
            'name' => 'CIS Critical Security Controls',
            'code' => 'CSCv8',
            'authority' => 'Center for Internet Security (CIS)',
            'status' => StandardStatus::IN_SCOPE,
            'reference_url' => 'https://www.cisecurity.org/controls/cis-controls-list/',
            'description' => "The 18 CIS Critical Security Controls represent a comprehensive and authoritative set of
            cybersecurity standards designed to provide organizations with a roadmap for effective cyber defense. These
            controls are meticulously structured to cover a wide array of security measures, ranging from asset and
            software inventory management to incident response and penetration testing. Developed by a consortium of
            experts, these controls prioritize a proactive and layered approach to cybersecurity, ensuring that every
            aspect of an organization's digital infrastructure is safeguarded. By addressing both preventive and
            responsive strategies, these controls serve as a foundational framework that organizations can implement
            to significantly reduce their vulnerability to cyber threats and bolster their overall security posture.
            This framework is not only practical but also adaptive, allowing for integration with existing security
            protocols and the evolving landscape of cyber threats.",
        ]);

        $csv = Reader::createFromPath(resource_path('data/csc8.csv'), 'r');
        $csv->setHeaderOffset(0);
        $records = (new Statement)->process($csv);

        // Retrieve the standard_id using DB Query Builder
        $standardId = DB::table('standards')->where('code', 'CSCv8')->value('id');

        foreach ($records as $record) {
            // Inserting data into 'controls' table
            DB::table('controls')->insert([
                'standard_id' => $standardId,
                'code' => $record['id'],
                'title' => $record['title'],
                'type' => $record['Type'] ?? ControlType::OTHER,
                'category' => $record['Category'] ?? ControlCategory::UNKNOWN,
                'enforcement' => $record['Enforcement'] ?? ControlEnforcementCategory::UNKNOWN,
                'discussion' => '',
                'description' => HelperController::linesToParagraphs($record['description'], 'control-description-text'),
            ]);
        }
    }
}
