<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 📚 FEATURE TEST: CommentController
 *
 * Feature testler:
 * - HTTP request gönderir (gerçek entegrasyon)
 * - Veritabanı kullanır (RefreshDatabase trait)
 * - Tests\TestCase extend eder (Laravel TestCase)
 */
class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_add_comment_to_own_movie(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('movies.comments.store', $movie), [
            'body' => 'Harika bir filmdi!',
            'has_spoiler' => false,
        ]);

        $response->assertRedirect(route('movies.show', $movie));
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Harika bir filmdi!',
            'has_spoiler' => false,
        ]);
    }

    #[Test]
    public function user_cannot_add_comment_to_others_movie(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->post(route('movies.comments.store', $movie), [
            'body' => 'Test yorum',
        ]);

        $response->assertForbidden();
    }

    #[Test]
    public function comment_body_is_required(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('movies.comments.store', $movie), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }

    #[Test]
    public function comment_body_max_length_is_500(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('movies.comments.store', $movie), [
            'body' => str_repeat('a', 501),
        ]);

        $response->assertSessionHasErrors('body');
    }

    #[Test]
    public function user_can_update_own_comment(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Orijinal yorum',
            'has_spoiler' => false,
        ]);

        $response = $this->actingAs($user)->put(
            route('movies.comments.update', [$movie, $comment]),
            ['body' => 'Güncellenmiş yorum', 'has_spoiler' => true]
        );

        $response->assertRedirect(route('movies.show', $movie));
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Güncellenmiş yorum',
            'has_spoiler' => true,
        ]);
    }

    #[Test]
    public function user_cannot_update_others_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $owner->id]);
        $comment = Comment::create([
            'user_id' => $owner->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Owner yorumu',
        ]);

        $response = $this->actingAs($other)->put(
            route('movies.comments.update', [$movie, $comment]),
            ['body' => 'Hack girişimi']
        );

        $response->assertForbidden();
    }

    #[Test]
    public function user_can_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Silinecek yorum',
        ]);

        $response = $this->actingAs($user)->delete(
            route('movies.comments.destroy', [$movie, $comment])
        );

        $response->assertRedirect(route('movies.show', $movie));
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function user_cannot_delete_others_comment(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $owner->id]);
        $comment = Comment::create([
            'user_id' => $owner->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Owner yorumu',
        ]);

        $response = $this->actingAs($other)->delete(
            route('movies.comments.destroy', [$movie, $comment])
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function movie_show_displays_comments(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Test yorumu burada',
        ]);

        $response = $this->actingAs($user)->get(route('movies.show', $movie));

        $response->assertOk();
        $response->assertSee('Test yorumu burada');
        $response->assertSee('Yorumlar');
    }

    #[Test]
    public function spoiler_comment_is_hidden_by_default(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'Spoiler içerik',
            'has_spoiler' => true,
        ]);

        $response = $this->actingAs($user)->get(route('movies.show', $movie));

        $response->assertOk();
        $response->assertSee('Spoiler İçerik');
        $response->assertSee('Göster'); // Reveal button
    }
}
