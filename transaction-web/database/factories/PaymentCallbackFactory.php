<?php

namespace Database\Factories;

use App\Models\PaymentCallback;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentCallback>
 */
class PaymentCallbackFactory extends Factory
{
    protected $model = PaymentCallback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gatewayStatuses = ['pending', 'settlement', 'capture', 'deny', 'cancel', 'expire', 'failure'];
        $status = $this->faker->randomElement($gatewayStatuses);
        
        return [
            'transaction_id' => Transaction::factory(),
            'gateway_transaction_id' => $this->faker->uuid(),
            'gateway_status' => $status,
            'raw_payload' => $this->generateMidtransPayload($status),
            'signature' => $this->faker->sha256(),
            'verified' => $this->faker->boolean(85), // 85% chance of being verified
            'received_at' => $this->faker->dateTimeThisMonth(),
            'processed_at' => $this->faker->optional(0.7)->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate that the callback is for a successful payment.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_status' => $this->faker->randomElement(['settlement', 'capture']),
            'verified' => true,
            'processed_at' => $this->faker->dateTimeThisMonth(),
        ]);
    }

    /**
     * Indicate that the callback is for a failed payment.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_status' => $this->faker->randomElement(['deny', 'cancel', 'expire', 'failure']),
            'verified' => true,
            'processed_at' => $this->faker->dateTimeThisMonth(),
        ]);
    }

    /**
     * Indicate that the callback is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_status' => 'pending',
            'verified' => true,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the callback is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified' => false,
            'processed_at' => null,
        ]);
    }

    /**
     * Generate realistic Midtrans payload.
     */
    private function generateMidtransPayload(string $status): array
    {
        $orderId = 'TXN-' . strtoupper($this->faker->bothify('##??##??##'));
        $amount = $this->faker->numberBetween(10000, 1000000);
        $paymentType = $this->faker->randomElement(['credit_card', 'bank_transfer', 'echannel', 'gopay']);

        $basePayload = [
            'va_numbers' => [
                [
                    'va_number' => $this->faker->numerify('############'),
                    'bank' => $this->faker->randomElement(['bca', 'bni', 'bri', 'permata']),
                ]
            ],
            'transaction_time' => $this->faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
            'transaction_status' => $status,
            'transaction_id' => $this->faker->uuid(),
            'status_message' => $this->getStatusMessage($status),
            'status_code' => $this->getStatusCode($status),
            'signature_key' => $this->faker->sha512(),
            'settlement_time' => $status === 'settlement' ? $this->faker->dateTimeThisMonth()->format('Y-m-d H:i:s') : null,
            'payment_type' => $paymentType,
            'order_id' => $orderId,
            'merchant_id' => 'G' . $this->faker->numerify('########'),
            'gross_amount' => (string) $amount,
            'fraud_status' => $this->faker->randomElement(['accept', 'challenge', 'deny']),
            'currency' => 'IDR',
        ];

        // Add payment type specific fields
        if ($paymentType === 'credit_card') {
            $basePayload['masked_card'] = $this->faker->creditCardNumber() . '****';
            $basePayload['card_type'] = $this->faker->randomElement(['visa', 'mastercard']);
            $basePayload['bank'] = $this->faker->randomElement(['bca', 'bni', 'mandiri']);
        } elseif ($paymentType === 'gopay') {
            $basePayload['account_id'] = $this->faker->phoneNumber();
        }

        return $basePayload;
    }

    /**
     * Get status message based on gateway status.
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Transaction is pending',
            'settlement' => 'Transaction is completed',
            'capture' => 'Transaction is captured',
            'deny' => 'Transaction is denied',
            'cancel' => 'Transaction is cancelled',
            'expire' => 'Transaction is expired',
            'failure' => 'Transaction failed',
            default => 'Unknown status',
        };
    }

    /**
     * Get status code based on gateway status.
     */
    private function getStatusCode(string $status): string
    {
        return match ($status) {
            'pending' => '201',
            'settlement', 'capture' => '200',
            'deny' => '202',
            'cancel' => '202',
            'expire' => '202',
            'failure' => '202',
            default => '500',
        };
    }
}
