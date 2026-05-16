<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class GroupProgress extends Model {
    protected $fillable = ['user_id','group_id','current_position','completed'];
    protected $casts    = ['completed'=>'boolean'];
    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function group(): BelongsTo { return $this->belongsTo(QuestionGroup::class,'group_id'); }
}
