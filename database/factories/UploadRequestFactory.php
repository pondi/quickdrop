<?php

namespace Database\Factories;

use App\Models\UploadRequest;
use App\Models\QuickDropUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UploadRequestFactory extends Factory
{
    protected $model = UploadRequest::class;

    public function definition(): array
    {
        $expirationHours = fake()->randomElement([1, 6, 12, 24, 48, 72, 168]);
        
        return [
            'title' => fake()->sentence(3),
            'comment' => fake()->boolean(50) ? fake()->paragraph() : null,
            'reference_number' => fake()->boolean(50) ? fake()->regexify('[A-Z]{3}-[0-9]{6}') : null,
            'quickdrop_user_id' => QuickDropUser::factory(),
            'unique_request_id' => Str::random(32),
            'verification_token' => Str::random(128),
            'expires_at' => now()->addHours($expirationHours),
            'status' => 'active',
            'is_encrypted' => fake()->boolean(20),
            'key_verification_hash' => null,
            'allow_public_download' => true,
            'allow_public_delete' => false,
            'allow_public_upload' => true,
            'max_downloads' => fake()->boolean(30) ? fake()->numberBetween(1, 100) : null,
            'downloads_count' => 0,
            'is_active' => true,
            'deactivated_at' => null,
            'last_downloaded_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function encrypted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_encrypted' => true,
        ]);
    }

    public function withReferenceNumber(string $referenceNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_number' => $referenceNumber ?? fake()->regexify('[A-Z]{3}-[0-9]{6}'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
    }

    public function withMaxDownloads(int $max): static
    {
        return $this->state(fn (array $attributes) => [
            'max_downloads' => $max,
        ]);
    }

    public function downloaded(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'downloads_count' => $count,
            'last_downloaded_at' => now(),
        ]);
    }
}