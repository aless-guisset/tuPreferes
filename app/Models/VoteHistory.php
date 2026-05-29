<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteHistory extends Model
{
    protected $table    = 'vote_history';
    protected $fillable = ['user_id','question_id','question_option_id','is_current'];
    protected $casts    = ['is_current' => 'boolean'];

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function question(): BelongsTo { return $this->belongsTo(Question::class); }
    public function option(): BelongsTo   { return $this->belongsTo(QuestionOption::class,'question_option_id'); }
}
