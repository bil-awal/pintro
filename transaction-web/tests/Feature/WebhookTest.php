<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\PaymentCallback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function midtrans_webhook_creates_payment_callback()
    {
        $transaction = Transaction::factory()->pending()->create([
            'transaction_id' => 'TXN-TEST-123',
            'payment_gateway_id' => 'midtrans-123',
        ]);

        $webhookPayload = [
            'order_id' => 'TXN-TEST-123',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => hash('sha512', 'TXN-TEST-123' . '200' . '100000.00' . config('services.midtrans.server_key')),
            'transaction_id' => 'midtrans-123',
            'payment_type' => 'bank_transfer',
            'transaction_time' => now()->format('Y-m-d H:i:s'),
            'settlement_time' => now()->format('Y-m-d H:i:s'),
        ];

        $response = $this->postJson('/api/webhooks/midtrans/notification', $webhookPayload);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'order_id',
                        'status'
                    ]
                ]);

        // Check that payment callback was created
        $this->assertDatabaseHas('payment_callbacks', [
            'transaction_id' => 'TXN-TEST-123',
            'gateway_status' => 'success', // Mapped from settlement
            'verified' => true,
        ]);
    }

    /** @test */
    public function midtrans_webhook_rejects_invalid_signature()
    {
        $transaction = Transaction::factory()->pending()->create([
            'transaction_id' => 'TXN-TEST-123',
        ]);

        $webhookPayload = [
            'order_id' => 'TXN-TEST-123',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'invalid_signature',
            'transaction_id' => 'midtrans-123',
        ];

        $response = $this->postJson('/api/webhooks/midtrans/notification', $webhookPayload);

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                ]);

        // Check that no payment callback was created
        $this->assertDatabaseMissing('payment_callbacks', [
            'transaction_id' => 'TXN-TEST-123',
        ]);
    }

    /** @test */
    public function go_service_webhook_updates_transaction_status()
    {
        $transaction = Transaction::factory()->pending()->create([
            'transaction_id' => 'TXN-TEST-456',
        ]);

        $webhookPayload = [
            'transaction_id' => 'TXN-TEST-456',
            'status' => 'completed',
            'user_id' => 'USR-123',
            'amount' => 100000,
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->withHeaders([
            'X-API-Key' => config('services.go_transaction.api_key'),
        ])->postJson('/api/webhooks/go-service/notification', $webhookPayload);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'transaction_id',
                        'status'
                    ]
                ]);

        // Check that transaction status was updated
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'TXN-TEST-456',
            'status' => 'completed',
        ]);

        $transaction->refresh();
        $this->assertNotNull($transaction->processed_at);
    }

    /** @test */
    public function go_service_webhook_rejects_invalid_api_key()
    {
        $transaction = Transaction::factory()->pending()->create([
            'transaction_id' => 'TXN-TEST-456',
        ]);

        $webhookPayload = [
            'transaction_id' => 'TXN-TEST-456',
            'status' => 'completed',
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->withHeaders([
            'X-API-Key' => 'invalid_key',
        ])->postJson('/api/webhooks/go-service/notification', $webhookPayload);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized',
                ]);

        // Check that transaction status was not updated
        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'TXN-TEST-456',
            'status' => 'pending', // Should remain unchanged
        ]);
    }

    /** @test */
    public function webhook_health_check_returns_healthy_status()
    {
        $response = $this->getJson('/api/webhooks/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version',
                ]);

        $this->assertEquals('healthy', $response->json('status'));
    }

    /** @test */
    public function payment_callback_can_be_marked_as_processed()
    {
        $callback = PaymentCallback::factory()->create([
            'processed_at' => null,
        ]);

        $this->assertNull($callback->processed_at);

        $result = $callback->markAsProcessed();

        $this->assertTrue($result);
        $this->assertNotNull($callback->fresh()->processed_at);
    }

    /** @test */
    public function payment_callback_can_be_marked_as_verified()
    {
        $callback = PaymentCallback::factory()->unverified()->create();

        $this->assertFalse($callback->verified);

        $result = $callback->markAsVerified();

        $this->assertTrue($result);
        $this->assertTrue($callback->fresh()->verified);
    }

    /** @test */
    public function payment_callback_status_detection_works()
    {
        $successfulCallback = PaymentCallback::factory()->successful()->create();
        $failedCallback = PaymentCallback::factory()->failed()->create();

        $this->assertTrue($successfulCallback->isSuccessful());
        $this->assertFalse($successfulCallback->isFailed());

        $this->assertTrue($failedCallback->isFailed());
        $this->assertFalse($failedCallback->isSuccessful());
    }
}
