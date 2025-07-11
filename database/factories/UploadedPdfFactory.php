<?php

namespace Database\Factories;

use App\Models\UploadedPdf;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UploadedPdfFactory extends Factory
{
    protected $model = UploadedPdf::class;

    public function definition(): array
    {
        $filename = Str::random(10) . '.pdf';

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'file_path' => 'pdfs/' . $filename,
            'original_filename' => $filename,
            'file_size' => $this->faker->numberBetween(10000, 500000),
            'mime_type' => 'application/pdf',
            'uploaded_by' => User::factory(),
            'analysis_status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'analysis_data' => null,
            'analysis_completed_at' => null,
        ];
    }
}
