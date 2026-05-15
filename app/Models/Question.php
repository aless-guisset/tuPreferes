<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'is_published',
        'is_anonymous',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_anonymous' => 'boolean',
    ];

    // ─── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(QuestionHistory::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhereHas('options', function ($q2) use ($term) {
                  $q2->where('label', 'like', "%{$term}%");
              });
        });
    }

    // ─── Accesseurs ───────────────────────────────────────────

    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    public function getTotalLikesAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Retourne les options avec leur pourcentage de votes
     */
    public function getOptionsWithStatsAttribute(): \Illuminate\Support\Collection
    {
        $total = $this->total_votes;

        return $this->options->map(function (QuestionOption $option) use ($total) {
            $count = $option->votes()->count();
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;

            return [
                'id'         => $option->id,
                'label'      => $option->label,
                'image'      => $option->image_url,
                'audio'      => $option->audio_url,
                'order'      => $option->order,
                'vote_count' => $count,
                'percentage' => $percentage,
            ];
        });
    }
}
