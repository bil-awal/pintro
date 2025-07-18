<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'updated_by',
    ];

    /**
     * Get the admin who last updated this setting.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * Get setting value with proper type casting.
     */
    public function getValue()
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => (bool) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set setting value with proper type handling.
     */
    public function setValue($value): void
    {
        $this->value = match ($this->type) {
            'integer' => (string) (int) $value,
            'boolean' => (string) (bool) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Get setting by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->getValue() : $default;
    }

    /**
     * Set setting by key.
     */
    public static function set(string $key, $value, string $type = 'string', int $updatedBy = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'updated_by' => $updatedBy,
            ]
        );

        $setting->setValue($value);
        $setting->save();

        return $setting;
    }

    /**
     * Check if setting exists.
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Delete setting by key.
     */
    public static function forget(string $key): bool
    {
        return self::where('key', $key)->delete();
    }
}
