<?php

namespace App\Policies;

use App\Models\Movie;
use App\Models\User;

/**
 * Film kayıtları üzerindeki yetkilendirme kuralları.
 *
 * Laravel, Policy method isimlerini controller action'larıyla
 * otomatik eşleştirir:
 *   - view()    → show()
 *   - update()  → update()
 *   - delete()  → destroy()
 */
class MoviePolicy
{
    /**
     * Filmi görüntüleyebilir mi?
     */
    public function view(User $user, Movie $movie): bool
    {
        return $user->id === $movie->user_id;
    }

    /**
     * Filmi güncelleyebilir mi?
     */
    public function update(User $user, Movie $movie): bool
    {
        return $user->id === $movie->user_id;
    }

    /**
     * Filmi silebilir mi?
     */
    public function delete(User $user, Movie $movie): bool
    {
        return $user->id === $movie->user_id;
    }
}
