<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table doesn't exist before creating
        $tableExists = Schema::hasTable('assets');

        Schema::create('assets', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Core Identification
            $table->string('asset_tag')->unique()->index();
            $table->string('serial_number')->nullable()->index();
            $table->string('name');
            $table->foreignId('asset_type_id')->nullable()->constrained('taxonomies')->cascadeOnDelete();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('taxonomies')->cascadeOnDelete();

            // Hardware Specifications
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('processor')->nullable();
            $table->integer('ram_gb')->nullable();
            $table->string('storage_type')->nullable();
            $table->integer('storage_capacity_gb')->nullable();
            $table->string('graphics_card')->nullable();
            $table->decimal('screen_size', 4, 2)->nullable();
            $table->string('mac_address')->nullable()->index();
            $table->string('ip_address')->nullable()->index();
            $table->string('hostname')->nullable()->index();
            $table->string('operating_system')->nullable();
            $table->string('os_version')->nullable();

            // Assignment & Location
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->string('room')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();

            // Financial Information
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('depreciation_method')->nullable();
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->decimal('current_value', 10, 2)->nullable();
            $table->decimal('residual_value', 10, 2)->nullable();

            // Warranty & Support
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->string('warranty_type')->nullable();
            $table->string('warranty_provider')->nullable();
            $table->string('support_contract_number')->nullable();
            $table->date('support_expiry_date')->nullable();

            // Lifecycle Management
            $table->date('received_date')->nullable();
            $table->date('deployment_date')->nullable();
            $table->date('last_audit_date')->nullable();
            $table->date('next_audit_date')->nullable();
            $table->date('retirement_date')->nullable();
            $table->date('disposal_date')->nullable();
            $table->string('disposal_method')->nullable();
            $table->integer('expected_life_years')->nullable();

            // Maintenance & Service
            $table->timestamp('last_maintenance_date')->nullable();
            $table->timestamp('next_maintenance_date')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->foreignId('condition_id')->nullable()->constrained('taxonomies')->cascadeOnDelete();

            // Software & Licensing
            $table->text('license_key')->nullable();
            $table->string('license_type')->nullable();
            $table->integer('license_seats')->nullable();
            $table->date('license_expiry_date')->nullable();

            // Security & Compliance
            $table->boolean('encryption_enabled')->default(false);
            $table->boolean('antivirus_installed')->default(false);
            $table->timestamp('last_security_scan')->nullable();
            $table->foreignId('compliance_status_id')->nullable()->constrained('taxonomies')->cascadeOnDelete();
            $table->foreignId('data_classification_id')->nullable()->constrained('taxonomies')->cascadeOnDelete();

            // Relationships & Dependencies
            $table->foreignId('parent_asset_id')->nullable()->constrained('assets')->nullOnDelete();

            // Additional Metadata
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('tags')->nullable();
            $table->string('image_url')->nullable();
            $table->string('qr_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            // Standard Timestamps
            $table->timestamps();
            $table->softDeletes();
        });

        // If table didn't exist before (fresh creation), seed the asset taxonomies
        if (! $tableExists) {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\AssetTaxonomySeeder',
                '--force' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
