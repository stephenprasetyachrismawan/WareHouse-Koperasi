<?php

namespace Database\Factories;

use App\Enums\ReturnEvidencePurpose;
use App\Models\ReturnEvidence;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnEvidenceFactory extends Factory
{
    protected $model = ReturnEvidence::class;

    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory(),
            'warehouse_id' => Warehouse::factory(),
            'purpose' => ReturnEvidencePurpose::ReturnSubmission,
            'uploaded_by' => User::factory(),
            'path' => 'return-evidence/'.$this->faker->uuid().'.jpg',
            'mime' => 'image/jpeg',
        ];
    }

    public function verification(): self
    {
        return $this->state(fn (array $attributes) => [
            'purpose' => ReturnEvidencePurpose::ReturnVerification,
        ]);
    }
}
