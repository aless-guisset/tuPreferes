<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name','username','email','password','avatar','bio','role_id','banned','ban_reason'];
    protected $hidden   = ['password','remember_token'];

    protected $casts = ['banned' => 'boolean'];

    protected function casts(): array
    {
        return ['email_verified_at'=>'datetime','password'=>'hashed'];
    }

    public function role(): BelongsTo      { return $this->belongsTo(Role::class); }
    public function questions(): HasMany   { return $this->hasMany(Question::class); }
    public function votes(): HasMany       { return $this->hasMany(Vote::class); }
    public function likes(): HasMany       { return $this->hasMany(Like::class); }
    public function shares(): HasMany      { return $this->hasMany(Share::class); }
    public function voteHistory(): HasMany { return $this->hasMany(VoteHistory::class)->orderByDesc('created_at'); }
    public function history(): HasMany     { return $this->hasMany(QuestionHistory::class)->orderByDesc('viewed_at'); }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role?->name === 'user';
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : 'https://api.dicebear.com/7.x/fun-emoji/svg?seed='.urlencode($this->username ?? 'user');
    }
}
