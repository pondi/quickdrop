<?php

namespace Database\Factories;

use App\Models\FileTypeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileTypeSettingFactory extends Factory
{
    protected $model = FileTypeSetting::class;

    public function definition(): array
    {
        $fileTypes = [
            ['extension' => 'pdf', 'mime_type' => 'application/pdf', 'category' => 'document'],
            ['extension' => 'jpg', 'mime_type' => 'image/jpeg', 'category' => 'image'],
            ['extension' => 'png', 'mime_type' => 'image/png', 'category' => 'image'],
            ['extension' => 'docx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'category' => 'document'],
            ['extension' => 'xlsx', 'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'category' => 'document'],
            ['extension' => 'txt', 'mime_type' => 'text/plain', 'category' => 'document'],
            ['extension' => 'zip', 'mime_type' => 'application/zip', 'category' => 'archive'],
            ['extension' => 'mp4', 'mime_type' => 'video/mp4', 'category' => 'video'],
            ['extension' => 'mp3', 'mime_type' => 'audio/mpeg', 'category' => 'audio'],
        ];
        
        $fileType = fake()->randomElement($fileTypes);
        
        return [
            'extension' => $fileType['extension'],
            'mime_type' => $fileType['mime_type'],
            'display_name' => ucfirst($fileType['extension']) . ' files',
            'max_size' => fake()->numberBetween(1, 100), // MB
            'is_allowed' => fake()->boolean(80), // 80% chance of being allowed
            'category' => $fileType['category'],
            'icon_class' => 'fa-file-' . $fileType['extension'],
            'priority' => fake()->numberBetween(1, 100),
            'metadata' => null,
        ];
    }

    public function allowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_allowed' => true,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_allowed' => false,
        ]);
    }

    public function forCategory(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}