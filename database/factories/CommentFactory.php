<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'commentable_type' => Movie::class,
            'commentable_id' => Movie::factory(),
            'body' => $this->faker->paragraph(),
            'has_spoiler' => false,
        ];
    }
}
