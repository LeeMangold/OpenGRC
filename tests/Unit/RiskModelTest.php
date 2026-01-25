<?php

namespace Tests\Unit;

use App\Enums\MitigationType;
use App\Enums\RiskStatus;
use App\Models\Risk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_risk_model_has_correct_fillable_attributes()
    {
        $fillable = ['name', 'likelihood', 'impact'];

        $risk = new Risk;

        $this->assertEquals($fillable, $risk->getFillable());
    }

    public function test_risk_model_has_correct_casts()
    {
        $risk = new Risk;

        $casts = $risk->getCasts();

        $this->assertEquals('integer', $casts['id']);
        $this->assertEquals(MitigationType::class, $casts['action']);
        $this->assertEquals(RiskStatus::class, $casts['status']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_risk_can_be_created_with_fillable_attributes()
    {
        $riskData = [
            'name' => 'Test Risk',
            'likelihood' => 5,
            'impact' => 8,
        ];

        $risk = Risk::create($riskData);

        $this->assertDatabaseHas('risks', $riskData);
        $this->assertEquals('Test Risk', $risk->name);
        $this->assertEquals(5, $risk->likelihood);
        $this->assertEquals(8, $risk->impact);
    }

    public function test_searchable_as_returns_correct_index_name()
    {
        $risk = new Risk;

        $indexName = $risk->searchableAs();

        $this->assertEquals('risks_index', $indexName);
    }

    public function test_to_searchable_array_returns_array_representation()
    {
        $risk = Risk::factory()->create([
            'name' => 'Test Risk',
            'likelihood' => 5,
            'impact' => 8,
        ]);

        $searchableArray = $risk->toSearchableArray();

        $this->assertIsArray($searchableArray);
        $this->assertEquals('Test Risk', $searchableArray['name']);
        $this->assertEquals(5, $searchableArray['likelihood']);
        $this->assertEquals(8, $searchableArray['impact']);
    }

    public function test_next_returns_incremented_max_id()
    {
        // Create some existing risks
        Risk::factory()->create(['id' => 5]);
        Risk::factory()->create(['id' => 10]);
        Risk::factory()->create(['id' => 3]);

        $nextId = Risk::next();

        $this->assertEquals(11, $nextId); // Max is 10, so next should be 11
    }

    public function test_next_returns_one_when_no_risks_exist()
    {
        // Ensure no risks exist
        Risk::query()->delete();

        $nextId = Risk::next();

        $this->assertEquals(1, $nextId);
    }
}
