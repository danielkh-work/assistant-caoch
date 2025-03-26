<?php
namespace Database\Factories;

use App\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormationFactory extends Factory
{
    protected $model = Formation::class;

    public function definition()
    {
        return [
            'league_id' => $this->faker->randomDigitNotNull(),
            'formation' => $this->faker->randomElement(['4-3-3', '4-4-2', '3-5-2']),
            'base_64' => base64_encode('test-image-data'),
            'image' => 'test-image-path.jpg',
        ];
    }
}
