<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EliminationSession extends Model {
    protected $fillable = ['user_id','group_id','remaining_items','eliminated_items','winner_item_id','completed'];
    protected $casts    = ['remaining_items'=>'array','eliminated_items'=>'array','completed'=>'boolean'];
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
    public function group(): BelongsTo  { return $this->belongsTo(QuestionGroup::class,'group_id'); }
    public function winner(): BelongsTo { return $this->belongsTo(EliminationItem::class,'winner_item_id'); }

    public function getCurrentDuelAttribute(): array {
        $r = $this->remaining_items ?? [];
        if (count($r) < 2) return [];
        $items = EliminationItem::whereIn('id', array_slice($r,0,2))->get()->keyBy('id');
        return ['a'=>$items[$r[0]]??null, 'b'=>$items[$r[1]]??null];
    }

    public function advance(int $winnerId): void {
        $r = $this->remaining_items;
        $e = $this->eliminated_items ?? [];

        // Retirer les 2 combattants
        $a = array_shift($r); // index 0
        $b = array_shift($r); // index 1

        // Le perdant est éliminé
        $e[] = ($winnerId === $a) ? $b : $a;

        // Le gagnant reste EN TÊTE pour affronter le suivant immédiatement
        array_unshift($r, $winnerId);

        $completed = count($r) === 1;
        $this->update([
            'remaining_items'  => $r,
            'eliminated_items' => $e,
            'winner_item_id'   => $completed ? $winnerId : null,
            'completed'        => $completed,
        ]);
    }
}
