<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class QuestionGroupItem extends Model {
    protected $fillable = ['group_id','question_id','position'];
    public function group(): BelongsTo    { return $this->belongsTo(QuestionGroup::class,'group_id'); }
    public function question(): BelongsTo { return $this->belongsTo(Question::class); }
}
