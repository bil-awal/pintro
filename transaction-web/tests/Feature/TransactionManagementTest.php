<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for authentication
        $this->admin = Admin::factory()->superAdmin()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function admin_can_view_transactions_list()
    {
        $this->actingAs($this->admin, 'admin');

        Transaction::factory()->count(5)->create();

        $response = $this->get('/admin/transactions');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_approve_pending_transaction()
    {
        $this->actingAs($this->admin, 'admin');

        $transaction = Transaction::factory()->pending()->create();

        $this->assertTrue($transaction->canBeApproved());
        $this->assertEquals('pending', $transaction->status);

        // Simulate approval
        $transaction->approve($this->admin->id);

        $this->assertEquals('completed', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->approved_at);
        $this->assertEquals($this->admin->id, $transaction->fresh()->approved_by);
    }

    /** @test */
    public function admin_can_reject_pending_transaction()
    {
        $this->actingAs($this->admin, 'admin');

        $transaction = Transaction::factory()->pending()->create();

        $this->assertTrue($transaction->canBeRejected());
        $this->assertEquals('pending', $transaction->status);

        // Simulate rejection
        $transaction->reject($this->admin->id);

        $this->assertEquals('failed', $transaction->fresh()->status);
        $this->assertNotNull($transaction->fresh()->approved_at);
        $this->assertEquals($this->admin->id, $transaction->fresh()->approved_by);
    }

    /** @test */
    public function cannot_approve_already_completed_transaction()
    {
        $this->actingAs($this->admin, 'admin');

        $transaction = Transaction::factory()->completed()->create();

        $this->assertFalse($transaction->canBeApproved());
        $this->assertEquals('completed', $transaction->status);

        // Try to approve again - should not change anything
        $result = $transaction->approve($this->admin->id);

        $this->assertFalse($result);
        $this->assertEquals('completed', $transaction->fresh()->status);
    }

    /** @test */
    public function transaction_has_proper_formatted_attributes()
    {
        $transaction = Transaction::factory()->create([
            'amount' => 100000.50,
            'fee' => 2500.25,
        ]);

        $this->assertEquals('Rp 100.001', $transaction->formatted_amount);
        $this->assertEquals('Rp 2.500', $transaction->formatted_fee);
        $this->assertEquals(102500.75, $transaction->total_amount);
        $this->assertEquals('Rp 102.501', $transaction->formatted_total_amount);
    }

    /** @test */
    public function transaction_scope_filters_work_correctly()
    {
        // Create transactions with different statuses
        Transaction::factory()->pending()->count(3)->create();
        Transaction::factory()->completed()->count(2)->create();
        Transaction::factory()->failed()->count(1)->create();

        $this->assertEquals(3, Transaction::pending()->count());
        $this->assertEquals(2, Transaction::completed()->count());
        $this->assertEquals(1, Transaction::byStatus('failed')->count());
        
        // Test today scope
        $this->assertEquals(6, Transaction::today()->count());
    }

    /** @test */
    public function transaction_belongs_to_user()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($user->id, $transaction->user->id);
        $this->assertEquals($user->email, $transaction->user->email);
    }

    /** @test */
    public function transaction_can_have_from_and_to_accounts_for_transfers()
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();
        
        $transaction = Transaction::factory()->transfer()->create([
            'from_account_id' => $fromUser->id,
            'to_account_id' => $toUser->id,
        ]);

        $this->assertInstanceOf(User::class, $transaction->fromAccount);
        $this->assertInstanceOf(User::class, $transaction->toAccount);
        $this->assertEquals($fromUser->id, $transaction->fromAccount->id);
        $this->assertEquals($toUser->id, $transaction->toAccount->id);
    }

    /** @test */
    public function transaction_status_colors_are_correct()
    {
        $pendingTransaction = Transaction::factory()->pending()->create();
        $completedTransaction = Transaction::factory()->completed()->create();
        $failedTransaction = Transaction::factory()->failed()->create();

        $this->assertEquals('warning', $pendingTransaction->status_color);
        $this->assertEquals('success', $completedTransaction->status_color);
        $this->assertEquals('danger', $failedTransaction->status_color);
    }
}
