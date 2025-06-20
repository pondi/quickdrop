<?php

namespace Database\Factories;

use App\Models\DownloadLog;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use Illuminate\Database\Eloquent\Factories\Factory;

class DownloadLogFactory extends Factory
{
    protected $model = DownloadLog::class;

    public function definition(): array
    {
        return [
            'upload_request_id' => UploadRequest::factory(),
            'upload_object_id' => UploadObject::factory(),
            'user_id' => null,
            'download_type' => $this->faker->randomElement(['single', 'zip', 'preview']),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'referer' => $this->faker->url(),
            'bytes_downloaded' => $this->faker->numberBetween(1024, 10 * 1024 * 1024),
            'completed' => true,
            'started_at' => $this->faker->dateTimeBetween('-30 days'),
            'completed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'metadata' => null,
        ];
    }
}