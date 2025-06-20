<?php

namespace Database\Factories;

use App\Models\MagicLink;
use App\Models\QuickDropUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MagicLinkFactory extends Factory
{
    protected $model = MagicLink::class;

    public function definition(): array
    {
        $user = QuickDropUser::factory()->create();
        
        return [
            'quickdrop_user_id' => $user->id,
            'email' => $user->email,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
            'purpose' => 'login',
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_at' => now(),
        ]);
    }
}