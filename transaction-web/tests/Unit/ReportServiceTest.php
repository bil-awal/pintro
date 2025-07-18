<?php

namespace Tests\Unit;

use App\Services\ReportService;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService();
    }

    /** @test */
    public function it_calculates_financial_stats_correctly()
    {
        // Create test data
        $user = User::factory()->create();
        
        // Create transactions with different statuses
        Transaction::factory()->completed()->create([
            'user_id' => $user->id,
            'amount' => 100000,
            'created_at' => now()->subDays(5),
        ]);
        
        Transaction::factory()->completed()->create([
            'user_id' => $user->id,
            'amount' => 200000,
            'created_at' => now()->subDays(3),
        ]);
        
        Transaction::factory()->failed()->create([
            'user_id' => $user->id,
            'amount' => 50000,
            'created_at' => now()->subDays(2),
        ]);
        
        Transaction::factory()->pending()->create([
            'user_id' => $user->id,
            'amount' => 75000,
            'created_at' => now()->subDay(),
        ]);

        $stats = $this->reportService->getFinancialStats();

        $this->assertEquals(4, $stats['total_transactions']);
        $this->assertEquals(300000, $stats['total_volume']); // Only completed transactions
        $this->assertEquals(1, $stats['failed_count']);
        $this->assertEquals(1, $stats['pending_count']);
        $this->assertEquals(75.0, $stats['success_rate']); // 3 out of 4 successful (including pending as not failed)
    }

    /** @test */
    public function it_generates_transaction_stats_by_type()
    {
        $user = User::factory()->create();
        
        // Create different transaction types
        Transaction::factory()->topup()->completed()->count(2)->create([
            'user_id' => $user->id,
            'amount' => 100000,
        ]);
        
        Transaction::factory()->payment()->completed()->count(3)->create([
            'user_id' => $user->id,
            'amount' => 50000,
        ]);
        
        Transaction::factory()->transfer()->completed()->create([
            'user_id' => $user->id,
            'amount' => 25000,
        ]);

        $stats = $this->reportService->getTransactionStatsByType();

        $this->assertCount(3, $stats);
        
        $topupStats = collect($stats)->firstWhere('type', 'topup');
        $this->assertEquals(2, $topupStats['count']);
        $this->assertEquals(200000, $topupStats['total_amount']);
        $this->assertEquals(100000, $topupStats['avg_amount']);
        
        $paymentStats = collect($stats)->firstWhere('type', 'payment');
        $this->assertEquals(3, $paymentStats['count']);
        $this->assertEquals(150000, $paymentStats['total_amount']);
    }

    /** @test */
    public function it_gets_daily_transaction_trends()
    {
        $user = User::factory()->create();
        
        // Create transactions on different days
        Transaction::factory()->completed()->create([
            'user_id' => $user->id,
            'amount' => 100000,
            'created_at' => now()->subDays(5)->startOfDay(),
        ]);
        
        Transaction::factory()->completed()->count(2)->create([
            'user_id' => $user->id,
            'amount' => 50000,
            'created_at' => now()->subDays(3)->startOfDay(),
        ]);
        
        Transaction::factory()->failed()->create([
            'user_id' => $user->id,
            'amount' => 25000,
            'created_at' => now()->subDays(3)->startOfDay(),
        ]);

        $trends = $this->reportService->getDailyTransactionTrends(7);

        $this->assertIsArray($trends);
        
        // Find the day with 3 transactions
        $dayWithThreeTransactions = collect($trends)->firstWhere('count', 3);
        $this->assertNotNull($dayWithThreeTransactions);
        $this->assertEquals(2, $dayWithThreeTransactions['completed_count']);
        $this->assertEquals(1, $dayWithThreeTransactions['failed_count']);
        $this->assertEquals(125000, $dayWithThreeTransactions['total_amount']);
    }

    /** @test */
    public function it_gets_top_users_by_volume()
    {
        // Create users with different transaction volumes
        $highVolumeUser = User::factory()->create(['first_name' => 'High', 'last_name' => 'Volume']);
        $lowVolumeUser = User::factory()->create(['first_name' => 'Low', 'last_name' => 'Volume']);
        
        // High volume user transactions
        Transaction::factory()->completed()->count(3)->create([
            'user_id' => $highVolumeUser->id,
            'amount' => 1000000,
        ]);
        
        // Low volume user transactions
        Transaction::factory()->completed()->create([
            'user_id' => $lowVolumeUser->id,
            'amount' => 100000,
        ]);

        $topUsers = $this->reportService->getTopUsersByVolume(5);

        $this->assertCount(2, $topUsers);
        $this->assertEquals($highVolumeUser->id, $topUsers[0]['id']);
        $this->assertEquals(3, $topUsers[0]['transaction_count']);
        $this->assertEquals(3000000, $topUsers[0]['total_volume']);
        
        $this->assertEquals($lowVolumeUser->id, $topUsers[1]['id']);
        $this->assertEquals(1, $topUsers[1]['transaction_count']);
        $this->assertEquals(100000, $topUsers[1]['total_volume']);
    }

    /** @test */
    public function it_calculates_growth_percentage_correctly()
    {
        $reportService = new ReportService();
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($reportService);
        $method = $reflection->getMethod('calculateGrowthPercentage');
        $method->setAccessible(true);
        
        // Test normal growth
        $this->assertEquals(100.0, $method->invoke($reportService, 100, 200));
        $this->assertEquals(50.0, $method->invoke($reportService, 100, 150));
        $this->assertEquals(-50.0, $method->invoke($reportService, 100, 50));
        
        // Test zero previous value
        $this->assertEquals(100.0, $method->invoke($reportService, 0, 100));
        $this->assertEquals(0.0, $method->invoke($reportService, 0, 0));
        
        // Test exact same values
        $this->assertEquals(0.0, $method->invoke($reportService, 100, 100));
    }

    /** @test */
    public function it_generates_comprehensive_report()
    {
        $user = User::factory()->create();
        Transaction::factory()->completed()->count(3)->create(['user_id' => $user->id]);

        $report = $this->reportService->generateComprehensiveReport();

        $this->assertArrayHasKey('financial_stats', $report);
        $this->assertArrayHasKey('transaction_by_type', $report);
        $this->assertArrayHasKey('transaction_by_status', $report);
        $this->assertArrayHasKey('daily_trends', $report);
        $this->assertArrayHasKey('top_users', $report);
        $this->assertArrayHasKey('payment_methods', $report);
        $this->assertArrayHasKey('failed_analysis', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('period', $report);
    }

    /** @test */
    public function it_gets_real_time_metrics()
    {
        $user = User::factory()->create();
        
        // Create today's transactions
        Transaction::factory()->completed()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $metrics = $this->reportService->getRealTimeMetrics();

        $this->assertArrayHasKey('today', $metrics);
        $this->assertArrayHasKey('yesterday', $metrics);
        $this->assertArrayHasKey('growth', $metrics);
        $this->assertArrayHasKey('last_updated', $metrics);
        
        $this->assertEquals(2, $metrics['today']['today_transactions']);
        $this->assertArrayHasKey('transactions', $metrics['growth']);
        $this->assertArrayHasKey('volume', $metrics['growth']);
    }
}
