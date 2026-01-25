<?php

namespace Tests\Unit;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_model_has_correct_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
            'url',
            'logo',
            'vendor_manager_id',
            'contact_name',
            'contact_email',
            'contact_phone',
            'address',
            'status',
            'risk_rating',
            'risk_score',
            'risk_score_calculated_at',
            'notes',
        ];

        $vendor = new Vendor;

        $this->assertEquals($fillable, $vendor->getFillable());
    }

    public function test_vendor_model_has_correct_casts()
    {
        $vendor = new Vendor;

        $casts = $vendor->getCasts();

        $this->assertEquals(VendorStatus::class, $casts['status']);
        $this->assertEquals(VendorRiskRating::class, $casts['risk_rating']);
        $this->assertEquals('array', $casts['logo']);
        $this->assertEquals('integer', $casts['risk_score']);
        $this->assertEquals('datetime', $casts['risk_score_calculated_at']);
    }

    public function test_vendor_can_be_created_with_fillable_attributes()
    {
        $vendorData = [
            'name' => 'Test Vendor',
            'description' => 'A test vendor description',
            'url' => 'https://test-vendor.com',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@test-vendor.com',
            'contact_phone' => '555-1234',
            'address' => '123 Test St',
            'status' => VendorStatus::ACTIVE,
            'risk_rating' => VendorRiskRating::MEDIUM,
            'risk_score' => 65,
            'notes' => 'Test notes',
        ];

        $vendor = Vendor::create($vendorData);

        $this->assertDatabaseHas('vendors', [
            'name' => 'Test Vendor',
            'contact_email' => 'john@test-vendor.com',
            'risk_score' => 65,
        ]);

        $this->assertEquals('Test Vendor', $vendor->name);
        $this->assertEquals(VendorStatus::ACTIVE, $vendor->status);
        $this->assertEquals(VendorRiskRating::MEDIUM, $vendor->risk_rating);
    }

    public function test_vendor_belongs_to_vendor_manager()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create(['vendor_manager_id' => $user->id]);

        $this->assertInstanceOf(User::class, $vendor->vendorManager);
        $this->assertEquals($user->id, $vendor->vendorManager->id);
    }

    public function test_vendor_status_enum_casting()
    {
        $vendor = Vendor::factory()->create(['status' => VendorStatus::ACTIVE]);

        $this->assertInstanceOf(VendorStatus::class, $vendor->status);
        $this->assertEquals(VendorStatus::ACTIVE, $vendor->status);
    }

    public function test_vendor_risk_rating_enum_casting()
    {
        $vendor = Vendor::factory()->create(['risk_rating' => VendorRiskRating::HIGH]);

        $this->assertInstanceOf(VendorRiskRating::class, $vendor->risk_rating);
        $this->assertEquals(VendorRiskRating::HIGH, $vendor->risk_rating);
    }

    public function test_vendor_uses_soft_deletes()
    {
        $vendor = Vendor::factory()->create(['name' => 'Test Vendor']);

        // Soft delete the vendor
        $vendor->delete();

        // Should not exist in normal queries
        $this->assertNull(Vendor::find($vendor->id));

        // But should exist in withTrashed queries
        $this->assertNotNull(Vendor::withTrashed()->find($vendor->id));
        $this->assertTrue(Vendor::withTrashed()->find($vendor->id)->trashed());
    }

    public function test_vendor_can_have_null_vendor_manager()
    {
        $vendor = Vendor::factory()->create(['vendor_manager_id' => null]);

        $this->assertNull($vendor->vendor_manager_id);
        $this->assertNull($vendor->vendorManager);
    }
}
