<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovieFactory extends Factory
{
    protected $model = Movie::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tmdb_id' => collect([$this->faker->unique()->numberBetween(100, 99999), null])->random(),
            'title' => $this->faker->sentence(3),
            'director' => $this->faker->name(),
            'genres' => [$this->faker->randomElement(['Aksiyon', 'Komedi', 'Dram', 'Korku', 'Bilim Kurgu', 'Belgesel'])],
            'poster_path' => null,
            'rating' => $this->faker->randomFloat(1, 1, 10),
            'personal_rating' => $this->faker->randomFloat(1, 1, 10),
            'runtime' => $this->faker->numberBetween(80, 180),
            'overview' => $this->faker->paragraph(),
            'release_date' => $this->faker->date(),
            'is_watched' => true,
            'watched_at' => now(),
        ];
    }
}
