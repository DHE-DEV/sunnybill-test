<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $filename = Str::random(10) . '.pdf';

        return [
            'name' => $this->faker->words(3, true),
            'original_name' => $filename,
            'path' => 'documents/' . $filename,
            'disk' => 'documents',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(10000, 500000),
            'category' => $this->faker->randomElement(['contract', 'invoice', 'other']),
            'description' => $this->faker->optional()->sentence,
            'documentable_type' => null, // Should be set when creating
            'documentable_id' => null,   // Should be set when creating
            'uploaded_by' => User::factory(),
            'is_favorite' => $this->faker->boolean(10),
        ];
    }
}
