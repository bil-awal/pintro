<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['topup', 'payment', 'transfer', 'withdrawal'];
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        $paymentMethods = ['credit_card', 'bca_va', 'bni_va', 'bri_va', 'gopay', 'shopeepay', 'indomaret'];
        
        $type = $this->faker->randomElement($types);
        $amount = $this->faker->numberBetween(10000, 1000000);
        $fee = $amount * 0.025; // 2.5% fee

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'amount' => $amount,
            'fee' => $fee,
            'currency' => 'IDR',
            'description' => $this->generateDescription($type),
            'status' => $this->faker->randomElement($statuses),
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'payment_gateway_id' => $this->faker->optional(0.7)->uuid(),
            'metadata' => $this->generateMetadata($type),
            'processed_at' => $this->faker->optional(0.6)->dateTimeThisMonth(),
            'created_at' => $this->faker->dateTimeThisMonth(),
        ];
    }

    /**
     * Generate transaction for top-up type.
     */
    public function topup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'topup',
            'description' => 'Balance top-up via ' . $this->faker->randomElement(['Bank Transfer', 'Virtual Account', 'E-Wallet']),
            'from_account_id' => null,
            'to_account_id' => null,
        ]);
    }

    /**
     * Generate transaction for payment type.
     */
    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'payment',
            'description' => 'Payment for ' . $this->faker->randomElement(['E-commerce', 'Subscription', 'Service', 'Product']),
            'from_account_id' => null,
            'to_account_id' => null,
        ]);
    }

    /**
     * Generate transaction for transfer type.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'description' => 'Money transfer to ' . $this->faker->name(),
            'from_account_id' => User::factory(),
            'to_account_id' => User::factory(),
        ]);
    }

    /**
     * Generate completed transaction.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => $this->faker->dateTimeThisMonth(),
        ]);
    }

    /**
     * Generate pending transaction.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Generate failed transaction.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'processed_at' => $this->faker->dateTimeThisMonth(),
        ]);
    }

    /**
     * Generate description based on transaction type.
     */
    private function generateDescription(string $type): string
    {
        return match ($type) {
            'topup' => 'Balance top-up via ' . $this->faker->randomElement(['Bank Transfer', 'Virtual Account', 'Credit Card']),
            'payment' => 'Payment for ' . $this->faker->randomElement(['Online Shopping', 'Subscription', 'Bill Payment', 'Service Fee']),
            'transfer' => 'Transfer to ' . $this->faker->name(),
            'withdrawal' => 'Withdrawal to ' . $this->faker->randomElement(['Bank Account', 'E-Wallet']),
            default => 'Transaction',
        };
    }

    /**
     * Generate metadata based on transaction type.
     */
    private function generateMetadata(string $type): array
    {
        $baseMetadata = [
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'platform' => $this->faker->randomElement(['web', 'mobile', 'api']),
        ];

        return match ($type) {
            'topup' => array_merge($baseMetadata, [
                'bank_code' => $this->faker->randomElement(['BCA', 'BNI', 'BRI', 'MANDIRI']),
                'va_number' => $this->faker->numerify('############'),
            ]),
            'payment' => array_merge($baseMetadata, [
                'merchant_id' => $this->faker->uuid(),
                'product_category' => $this->faker->randomElement(['electronics', 'fashion', 'food', 'services']),
            ]),
            'transfer' => array_merge($baseMetadata, [
                'transfer_type' => $this->faker->randomElement(['instant', 'scheduled']),
                'notes' => $this->faker->optional()->sentence(),
            ]),
            default => $baseMetadata,
        };
    }
}
