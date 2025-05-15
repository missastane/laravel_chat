<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'=>fake()->userName(),
            'over_view'=>fake()->paragraph(),
            'image'=>fake()->filePath(),
            'user_id'=>User::all()->random()->id,
            'created_at'=>Carbon::now(),
            'started_at'=>Carbon::now(),
            'updated_at'=>Carbon::now(),
        ];
    }
}
