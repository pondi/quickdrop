<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        $types = ['string', 'boolean', 'integer', 'json'];
        $groups = ['general', 'email', 'storage', 'security', 'appearance'];
        
        $type = $this->faker->randomElement($types);
        $value = match($type) {
            'boolean' => $this->faker->boolean() ? 'true' : 'false',
            'integer' => (string) $this->faker->numberBetween(1, 100),
            'json' => json_encode(['key' => 'value']),
            default => $this->faker->words(3, true),
        };

        return [
            'key' => $this->faker->unique()->slug(2),
            'value' => $value,
            'type' => $type,
            'group' => $this->faker->randomElement($groups),
            'label' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(),
            'validation_rules' => 'required',
            'order' => $this->faker->numberBetween(1, 100),
            'is_public' => $this->faker->boolean(70),
        ];
    }

    public function withKey(string $key, $value): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'value' => (string) $value,
        ]);
    }
}