<?php

namespace Tests\Unit;

use App\Enums\QuotaType;
use App\Exceptions\QuotaExceededException;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_check_returns_true_when_quota_is_disabled(): void
    {
        putenv('AI_PROMPT_QUOTA=0');

        $result = QuotaService::check(QuotaType::AI_PROMPT, 100);

        $this->assertTrue($result);
    }

    public function test_check_returns_true_when_under_limit(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 50);

        $result = QuotaService::check(QuotaType::AI_PROMPT, 30);

        $this->assertTrue($result);
    }

    public function test_check_throws_exception_when_over_limit(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 80);

        $this->expectException(QuotaExceededException::class);
        QuotaService::check(QuotaType::AI_PROMPT, 30);
    }

    public function test_check_available_returns_php_int_max_when_unlimited(): void
    {
        putenv('AI_PROMPT_QUOTA=0');

        $available = QuotaService::checkAvailable(QuotaType::AI_PROMPT);

        $this->assertEquals(PHP_INT_MAX, $available);
    }

    public function test_check_available_returns_remaining_capacity(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 30);

        $available = QuotaService::checkAvailable(QuotaType::AI_PROMPT);

        $this->assertEquals(70, $available);
    }

    public function test_check_available_returns_zero_when_exceeded(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 120);

        $available = QuotaService::checkAvailable(QuotaType::AI_PROMPT);

        $this->assertEquals(0, $available);
    }

    public function test_has_capacity_returns_true_when_under_limit(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 50);

        $hasCapacity = QuotaService::hasCapacity(QuotaType::AI_PROMPT, 30);

        $this->assertTrue($hasCapacity);
    }

    public function test_has_capacity_returns_false_when_over_limit(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 80);

        $hasCapacity = QuotaService::hasCapacity(QuotaType::AI_PROMPT, 30);

        $this->assertFalse($hasCapacity);
    }

    public function test_record_increments_usage(): void
    {
        $newTotal = QuotaService::record(QuotaType::AI_PROMPT, 25);

        $this->assertEquals(25, $newTotal);
        $this->assertEquals(25, QuotaService::getUsage(QuotaType::AI_PROMPT));
    }

    public function test_record_increments_existing_usage(): void
    {
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 30);

        $newTotal = QuotaService::record(QuotaType::AI_PROMPT, 20);

        $this->assertEquals(50, $newTotal);
        $this->assertEquals(50, QuotaService::getUsage(QuotaType::AI_PROMPT));
    }

    public function test_get_usage_returns_cached_value(): void
    {
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 42);

        $usage = QuotaService::getUsage(QuotaType::AI_PROMPT);

        $this->assertEquals(42, $usage);
    }

    public function test_get_usage_returns_zero_when_not_cached(): void
    {
        $usage = QuotaService::getUsage(QuotaType::AI_PROMPT);

        $this->assertEquals(0, $usage);
    }

    public function test_get_limit_returns_env_value(): void
    {
        putenv('AI_PROMPT_QUOTA=200');

        $limit = QuotaService::getLimit(QuotaType::AI_PROMPT);

        $this->assertEquals(200, $limit);
    }

    public function test_get_limit_returns_default_when_env_not_set(): void
    {
        // Remove the env var to test default behavior
        putenv('AI_RESPONSE_QUOTA');

        $limit = QuotaService::getLimit(QuotaType::AI_RESPONSE);

        $this->assertEquals(1000000, $limit);
    }

    public function test_get_stats_returns_complete_statistics(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 75);

        $stats = QuotaService::getStats(QuotaType::AI_PROMPT);

        $this->assertIsArray($stats);
        $this->assertEquals('ai_prompt', $stats['quota_type']);
        $this->assertEquals('AI Prompt Tokens', $stats['label']);
        $this->assertEquals(75, $stats['usage']);
        $this->assertEquals(100, $stats['limit']);
        $this->assertEquals(25, $stats['remaining']);
        $this->assertEquals(75.0, $stats['percentage_used']);
        $this->assertFalse($stats['is_exceeded']);
        $this->assertArrayHasKey('resets_at', $stats);
    }

    public function test_get_stats_shows_exceeded_when_over_limit(): void
    {
        putenv('AI_PROMPT_QUOTA=100');
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 120);

        $stats = QuotaService::getStats(QuotaType::AI_PROMPT);

        $this->assertTrue($stats['is_exceeded']);
        $this->assertEquals(0, $stats['remaining']);
        $this->assertEquals(120.0, $stats['percentage_used']);
    }

    public function test_get_all_stats_returns_all_quota_types(): void
    {
        $allStats = QuotaService::getAllStats();

        $this->assertIsArray($allStats);
        $this->assertArrayHasKey('ai_prompt', $allStats);
        $this->assertArrayHasKey('ai_response', $allStats);
        $this->assertCount(2, $allStats);
    }

    public function test_reset_clears_quota_cache(): void
    {
        $cacheKey = QuotaType::AI_PROMPT->getCacheKey();
        Cache::put($cacheKey, 50);

        QuotaService::reset(QuotaType::AI_PROMPT);

        $this->assertFalse(Cache::has($cacheKey));
        $this->assertEquals(0, QuotaService::getUsage(QuotaType::AI_PROMPT));
    }
}
