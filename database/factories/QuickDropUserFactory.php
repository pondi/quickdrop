<?php

namespace Database\Factories;

use App\Models\QuickDropUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QuickDropUserFactory extends Factory
{
    protected $model = QuickDropUser::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'email_verified_at' => now(),
            'last_login_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-30 days', 'now') : null,
            'is_active' => true,
            'timezone' => fake()->timezone(),
            'preferences' => [],
            'storage_used' => fake()->numberBetween(0, 1073741824),
            'storage_limit' => 5368709120,
            'notify_on_upload_complete' => true,
            'notify_on_download' => true,
            'notify_on_expiration_warning' => true,
            'notify_marketing' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withEmailNotificationsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_on_upload_complete' => false,
            'notify_on_download' => false,
            'notify_on_expiration_warning' => false,
            'notify_marketing' => false,
        ]);
    }
}