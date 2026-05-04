<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ASR — Antenna Structure Registration (47 CFR Part 17)
        // Towers >200ft AGL or near airports must be ASR-registered with the FCC.
        Schema::create('fcc_asr_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('asr_number')->unique()->comment('FCC ASR # (7 digits)');
            $table->string('owner');
            $table->string('structure_type')->nullable()->comment('Guyed, Self-supporting, Monopole, Building');
            $table->decimal('overall_height_meters', 8, 2)->nullable()->comment('Above ground level');
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('faa_study_number')->nullable()->comment('FAA aeronautical study');
            $table->enum('lighting_type', ['none', 'red_only', 'medium_dual', 'high_dual', 'medium_white', 'high_white'])
                ->nullable();
            $table->enum('painting_required', ['none', 'aviation_orange_white'])->nullable();
            $table->date('last_inspection_date')->nullable();
            $table->date('next_inspection_due')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // EAS Tests — required by 47 CFR Part 11
        // RWT (weekly), RMT (monthly), NPT (annual national test)
        Schema::create('fcc_eas_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->enum('test_type', ['RWT', 'RMT', 'NPT', 'state_test'])
                ->comment('Required Weekly/Monthly Test, National Periodic Test, State');
            $table->enum('direction', ['received', 'originated'])->default('received');
            $table->dateTime('test_datetime');
            $table->string('originator_code', 8)->nullable()->comment('EAS originator (e.g. EAN, PEP, EAS, CIV, WXR)');
            $table->string('event_code', 8)->nullable()->comment('e.g. RWT, RMT, EAS, EAN, NPT');
            $table->string('location_codes')->nullable()->comment('FIPS codes received');
            $table->boolean('audio_intelligible')->nullable();
            $table->boolean('visual_message_present')->nullable();
            $table->text('comments')->nullable();
            $table->boolean('filed_in_etrs')->default(false)->comment('Filed in FCC ETRS');
            $table->date('etrs_filed_date')->nullable();
            $table->string('logged_by')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'test_datetime']);
            $table->index(['test_type']);
        });

        // Issues/Programs Lists — quarterly per 47 CFR §73.3526(e)(12)
        // Top community issues + programs that addressed them. Filed within 10 days of quarter end.
        Schema::create('fcc_issues_programs_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->year('quarter_year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->date('placed_in_file_date')->nullable();
            $table->enum('status', ['draft', 'placed_in_file', 'late_filed'])->default('draft');
            $table->text('preparer_notes')->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'quarter_year', 'quarter']);
        });

        Schema::create('fcc_issues_programs_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('fcc_issues_programs_lists')->cascadeOnDelete();
            $table->string('issue')->comment('Community issue addressed (e.g. Mental Health, Local Economy)');
            $table->string('program_title');
            $table->string('program_description', 1000)->nullable();
            $table->dateTime('aired_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->enum('program_type', ['news', 'public_affairs', 'pia', 'documentary', 'interview', 'other'])
                ->default('public_affairs');
            $table->timestamps();
        });

        // Public File Documents — online inspection file (47 CFR §73.3526)
        Schema::create('fcc_public_file_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->enum('document_type', [
                'authorization', 'application', 'contour_map', 'ownership_report',
                'eeo_public_file', 'political_file', 'issues_programs_list',
                'children_tv_report', 'shared_service_agreement', 'time_brokerage_agreement',
                'joint_sales_agreement', 'biennial_ownership_report',
                'donor_list', 'station_id_announcement', 'public_notice', 'letter_to_public',
                'other',
            ]);
            $table->string('title');
            $table->date('document_date')->nullable();
            $table->date('uploaded_to_lms_date')->nullable()->comment('Filed in FCC LMS');
            $table->date('retention_until')->nullable()->comment('Required retention end (typically license term)');
            $table->string('lms_url')->nullable();
            $table->text('notes')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['license_id', 'document_type']);
        });

        // Tower Lighting Inspections — quarterly per 47 CFR §17.47
        Schema::create('fcc_tower_lighting_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asr_registration_id')->nullable()->constrained('fcc_asr_registrations')->nullOnDelete();
            $table->foreignId('facility_id')->nullable()->constrained('fcc_facilities')->nullOnDelete();
            $table->date('inspection_date');
            $table->string('inspector_name')->nullable();
            $table->enum('result', ['operational', 'minor_issue', 'failed'])->default('operational');
            $table->boolean('automatic_monitor_observed')->default(true)->comment('Auto-alarm system verified');
            $table->boolean('manual_observation_performed')->default(true)->comment('§17.47 24-hour observation');
            $table->text('findings')->nullable();
            $table->text('corrective_action')->nullable();
            $table->date('next_inspection_due');
            $table->timestamps();

            $table->index(['inspection_date']);
        });

        // Tower Lighting Outage NOTAMs — 47 CFR §17.48 requires immediate FAA notification
        Schema::create('fcc_tower_lighting_outages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asr_registration_id')->constrained('fcc_asr_registrations')->cascadeOnDelete();
            $table->dateTime('outage_observed_at');
            $table->dateTime('faa_notified_at')->nullable()->comment('Required within reasonable time');
            $table->string('notam_number')->nullable()->comment('FAA NOTAM identifier');
            $table->dateTime('repaired_at')->nullable();
            $table->dateTime('faa_cancellation_at')->nullable();
            $table->enum('failure_type', ['top_beacon', 'side_marker', 'monitor_alarm', 'partial', 'total'])
                ->default('partial');
            $table->text('cause')->nullable();
            $table->text('actions_taken')->nullable();
            $table->timestamps();

            $table->index(['outage_observed_at']);
        });

        // Political File — political ad tracking (47 CFR §73.1943, §76.1701)
        Schema::create('fcc_political_file_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->date('order_date');
            $table->string('candidate_or_issue')->comment('Candidate name or ballot issue');
            $table->string('sponsor')->comment('Buying entity (committee, PAC, candidate)');
            $table->enum('office', [
                'federal_president', 'federal_senate', 'federal_house',
                'state_governor', 'state_legislature', 'state_other',
                'local', 'ballot_initiative', 'issue_ad',
            ])->nullable();
            $table->date('flight_start_date')->nullable();
            $table->date('flight_end_date')->nullable();
            $table->unsignedInteger('spots_purchased')->nullable();
            $table->decimal('rate_per_spot', 10, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->boolean('lowest_unit_rate_window')->default(false)
                ->comment('LUR applies: 45d before primary / 60d before general');
            $table->string('contract_pdf_path')->nullable();
            $table->date('uploaded_to_public_file_date')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'order_date']);
        });

        // Station Log — daily operational entries (47 CFR §73.1820)
        Schema::create('fcc_station_log_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->dateTime('logged_at');
            $table->enum('entry_type', [
                'transmitter_reading', 'eas_test', 'tower_lighting_check',
                'sign_on', 'sign_off', 'station_id', 'maintenance',
                'power_change', 'directional_pattern_check', 'incident', 'other',
            ]);
            $table->string('summary');
            $table->json('readings')->nullable()->comment('e.g. {"power_kw":50.1, "swr":1.05}');
            $table->string('logged_by')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'logged_at']);
            $table->index(['entry_type']);
        });

        // Regulatory Fees — annual per FCC Form 159
        Schema::create('fcc_regulatory_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->year('fiscal_year');
            $table->string('fee_category')->comment('e.g. AM Class A, FM Class C, TV Markets 1-10');
            $table->decimal('amount_due', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('confirmation_number')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'waiver_requested'])->default('pending');
            $table->timestamps();

            $table->unique(['license_id', 'fiscal_year']);
        });

        // FCC Form Filings History — 323, EEO 397, 2100-H, etc.
        Schema::create('fcc_form_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained('fcc_licenses')->cascadeOnDelete();
            $table->string('form_number')->comment('e.g. 323, 323-E, 397, 2100-H, 2100-A');
            $table->string('form_title');
            $table->date('filed_date');
            $table->string('file_number')->nullable()->comment('FCC-assigned file number');
            $table->enum('status', ['filed', 'pending_review', 'granted', 'returned', 'dismissed', 'denied'])
                ->default('filed');
            $table->text('notes')->nullable();
            $table->string('filed_by')->nullable();
            $table->timestamps();

            $table->index(['license_id', 'form_number']);
            $table->index(['filed_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcc_form_filings');
        Schema::dropIfExists('fcc_regulatory_fees');
        Schema::dropIfExists('fcc_station_log_entries');
        Schema::dropIfExists('fcc_political_file_entries');
        Schema::dropIfExists('fcc_tower_lighting_outages');
        Schema::dropIfExists('fcc_tower_lighting_inspections');
        Schema::dropIfExists('fcc_public_file_documents');
        Schema::dropIfExists('fcc_issues_programs_entries');
        Schema::dropIfExists('fcc_issues_programs_lists');
        Schema::dropIfExists('fcc_eas_tests');
        Schema::dropIfExists('fcc_asr_registrations');
    }
};
