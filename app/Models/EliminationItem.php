<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EliminationItem extends Model {
    protected $fillable = ['group_id','label','image','position'];
    public function group(): BelongsTo { return $this->belongsTo(QuestionGroup::class,'group_id'); }
    public function getImageUrlAttribute(): ?string { return $this->image ? asset('storage/'.$this->image) : null; }
}
