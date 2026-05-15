<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'label',
        'image',
        'audio',
        'order',
    ];

    // ─── Relations ────────────────────────────────────────────

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'question_option_id');
    }

    // ─── Accesseurs ───────────────────────────────────────────

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio ? asset('storage/' . $this->audio) : null;
    }
}
