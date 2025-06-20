<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\QuickDropUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $userType = fake()->randomElement(['users', 'quickdrop_users']);
        $eventTypes = [
            'login' => 'auth',
            'logout' => 'auth', 
            'create' => 'quickdrop',
            'update' => 'quickdrop',
            'delete' => 'quickdrop',
            'download' => 'file',
            'upload' => 'file',
        ];
        
        $eventType = fake()->randomKey($eventTypes);
        
        return [
            'event_type' => $eventType,
            'event_category' => $eventTypes[$eventType],
            'description' => fake()->sentence(),
            'model_type' => fake()->optional()->randomElement([
                'App\\Models\\UploadRequest',
                'App\\Models\\UploadObject',
                'App\\Models\\QuickDropUser',
            ]),
            'model_id' => fake()->optional()->numberBetween(1, 100),
            'user_id' => $userType === 'users' ? User::factory() : QuickDropUser::factory(),
            'user_type' => $userType,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'url' => fake()->url(),
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forAdmin(User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
            'user_type' => 'users',
        ]);
    }

    public function forQuickDropUser(QuickDropUser $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? QuickDropUser::factory(),
            'user_type' => 'quickdrop_users',
        ]);
    }

    public function withEventType(string $eventType, string $category, string $description = null): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => $eventType,
            'event_category' => $category,
            'description' => $description ?? fake()->sentence(),
        ]);
    }
}