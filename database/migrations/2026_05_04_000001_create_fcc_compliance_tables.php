<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcc_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('facility_id')->unique()->comment('FCC Facility ID (FRN-style)');
            $table->string('name');
            $table->string('community_of_license')->nullable();
            $table->string('state', 2)->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->decimal('antenna_haat_meters', 8, 2)->nullable()->comment('Height Above Average Terrain');
            $table->decimal('antenna_amsl_meters', 8, 2)->nullable()->comment('Above Mean Sea Level');
            $table->string('asr_number')->nullable()->comment('Antenna Structure Registration #');
            $table->string('owner')->nullable();
            $table->string('contact_engineer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fcc_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('frn')->index()->comment('FCC Registration Number');
            $table->string('call_sign')->unique();
            $table->string('licensee');
            $table->enum('service', [
                'AM', 'FM', 'TV', 'LPFM', 'LPTV', 'FX', 'TX', 'DT', 'DC', 'DD', 'CA', 'OTHER'
            ])->default('FM')->comment('FCC service code');
            $table->string('channel_or_frequency')->nullable()->comment('e.g. 98.7 MHz, Ch. 27');
            $table->date('grant_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('last_renewal_date')->nullable();
            $table->enum('status', ['active', 'expiring_soon', 'at_risk', 'non_compliant', 'silent', 'cancelled'])
                ->default('active');
            $table->decimal('compliance_score', 5, 2)->default(100.00)->comment('0-100 derived score');
            $table->foreignId('facility_id')->nullable()->constrained('fcc_facilities')->nullOnDelete();
            $table->json('license_class_data')->nullable()->comment('Class A/B/C, ERP kW, etc.');
            $table->text('public_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['expiration_date']);
        });

        Schema::create('fcc_transmitters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('rated_power_kw', 8, 3)->nullable();
            $table->decimal('authorized_erp_kw', 8, 3)->nullable()->comment('Effective Radiated Power');
            $table->decimal('measured_power_kw', 8, 3)->nullable();
            $table->date('last_proof_of_performance')->nullable();
            $table->date('next_proof_due')->nullable();
            $table->boolean('eas_endec_present')->default(false);
            $table->string('eas_endec_model')->nullable();
            $table->enum('status', ['operating', 'standby', 'offline', 'maintenance'])->default('operating');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fcc_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_number')->unique()->comment('e.g. 73.3526, 73.1212');
            $table->string('part')->nullable()->comment('e.g. Part 73, Part 11');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', [
                'technical_standards', 'operational_rules', 'eas_requirements',
                'public_file_rules', 'ownership_control', 'reporting_requirements'
            ])->default('operational_rules');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('quarterly_filing_required')->default(false);
            $table->timestamps();
        });

        Schema::create('fcc_license_rule_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('fcc_licenses')->cascadeOnDelete();
            $table->foreignId('fcc_rule_id')->constrained('fcc_rules')->cascadeOnDelete();
            $table->enum('status', ['compliant', 'at_risk', 'non_compliant', 'not_applicable'])
                ->default('compliant');
            $table->date('last_evaluated_at')->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->timestamps();

            $table->unique(['license_id', 'fcc_rule_id']);
            $table->index(['status']);
        });

        Schema::create('fcc_deadlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained('fcc_licenses')->cascadeOnDelete();
            $table->string('title');
            $table->enum('deadline_type', [
                'quarterly_eas_test', 'public_file_upload', 'license_renewal',
                'issues_programs_list', 'ownership_report', 'eeo_report',
                'children_tv_report', 'tower_lighting', 'other'
            ])->default('other');
            $table->date('due_date');
            $table->enum('status', ['upcoming', 'due_soon', 'overdue', 'completed'])->default('upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['due_date']);
            $table->index(['status']);
        });

        Schema::create('fcc_compliance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->nullable()->constrained('fcc_licenses')->nullOnDelete();
            $table->string('event_type')->comment('e.g. eas_test_filed, public_file_uploaded, technical_review_passed');
            $table->string('summary');
            $table->json('payload')->nullable();
            $table->string('actor')->nullable()->comment('user/email/system');
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['event_type']);
            $table->index(['occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcc_compliance_events');
        Schema::dropIfExists('fcc_deadlines');
        Schema::dropIfExists('fcc_license_rule_status');
        Schema::dropIfExists('fcc_rules');
        Schema::dropIfExists('fcc_transmitters');
        Schema::dropIfExists('fcc_licenses');
        Schema::dropIfExists('fcc_facilities');
    }
};
