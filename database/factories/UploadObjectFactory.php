<?php

namespace Database\Factories;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UploadObjectFactory extends Factory
{
    protected $model = UploadObject::class;

    public function definition(): array
    {
        $filename = fake()->word() . '.' . fake()->randomElement(['pdf', 'jpg', 'png', 'docx', 'xlsx', 'txt', 'zip']);
        $storedName = Str::uuid() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $size = fake()->numberBetween(1024, 10485760); // 1KB to 10MB
        
        return [
            'owner_id' => null, // For QuickDrop users
            'quickdrop_owner_id' => null, // Will be set when creating with relationship
            'original_name' => $filename,
            'stored_name' => $storedName,
            'storage_path' => 'uploads/' . date('Y/m/d') . '/' . $storedName,
            'mime_type' => $this->getMimeType($filename),
            'unique_id' => Str::uuid(),
            'file_size' => $size,
            'file_extension' => pathinfo($filename, PATHINFO_EXTENSION),
            'file_hash' => md5(Str::random(32)),
            'version' => 1,
            'original_file_id' => null,
            'status' => 'complete',
            'is_encrypted' => false,
            'metadata' => null,
        ];
    }

    private function getMimeType(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    public function downloaded(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'download_count' => $count,
            'last_downloaded_at' => now(),
        ]);
    }

    public function image(): static
    {
        return $this->state(function (array $attributes) {
            $filename = fake()->word() . '.' . fake()->randomElement(['jpg', 'png', 'gif']);
            $storedName = Str::uuid() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            return [
                'original_name' => $filename,
                'stored_name' => $storedName,
                'storage_path' => 'uploads/' . date('Y/m/d') . '/' . $storedName,
                'file_extension' => pathinfo($filename, PATHINFO_EXTENSION),
                'mime_type' => $this->getMimeType($filename),
            ];
        });
    }

    public function document(): static
    {
        return $this->state(function (array $attributes) {
            $filename = fake()->word() . '.' . fake()->randomElement(['pdf', 'docx', 'xlsx', 'txt']);
            $storedName = Str::uuid() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            return [
                'original_name' => $filename,
                'stored_name' => $storedName,
                'storage_path' => 'uploads/' . date('Y/m/d') . '/' . $storedName,
                'file_extension' => pathinfo($filename, PATHINFO_EXTENSION),
                'mime_type' => $this->getMimeType($filename),
            ];
        });
    }
}