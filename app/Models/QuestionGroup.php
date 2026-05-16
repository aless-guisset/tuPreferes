<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany,BelongsToMany};

class QuestionGroup extends Model {
    use HasFactory;
    protected $fillable = ['user_id','title','description','type','order','is_published','is_anonymous'];
    protected $casts    = ['is_published'=>'boolean','is_anonymous'=>'boolean'];

    public function user(): BelongsTo          { return $this->belongsTo(User::class); }
    public function questions(): BelongsToMany { return $this->belongsToMany(Question::class,'question_group_items','group_id','question_id')->withPivot('position')->orderBy('question_group_items.position'); }
    public function groupItems(): HasMany      { return $this->hasMany(QuestionGroupItem::class,'group_id')->orderBy('position'); }
    public function eliminationItems(): HasMany{ return $this->hasMany(EliminationItem::class,'group_id')->orderBy('position'); }
    public function eliminationSessions(): HasMany { return $this->hasMany(EliminationSession::class,'group_id'); }
    public function groupProgress(): HasMany   { return $this->hasMany(GroupProgress::class,'group_id'); }

    public function getWinnerStatsAttribute(): array {
        $sessions = $this->eliminationSessions()->where('completed',true)->get();
        $total = $sessions->count();
        if ($total === 0) return [];
        $items = $this->eliminationItems->keyBy('id');
        return $sessions->groupBy('winner_item_id')->map(function($g,$itemId) use($total,$items) {
            return ['item'=>$items[$itemId]??null,'count'=>$g->count(),'percentage'=>round($g->count()/$total*100)];
        })->sortByDesc('count')->values()->toArray();
    }
}
