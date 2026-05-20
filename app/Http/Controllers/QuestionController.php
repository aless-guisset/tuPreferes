<?php
namespace App\Http\Controllers;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\VoteRequest;
use App\Models\Like;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\QuestionHistory;
use App\Models\QuestionOption;
use App\Models\Share;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    private function formatQuestion(Question $q): array
    {
        $user  = Auth::user();
        $total = $q->votes()->count();
        $opts  = $q->options->map(function (QuestionOption $o) use ($total) {
            $c = $o->votes()->count();
            return ['id'=>$o->id,'label'=>$o->label,'image'=>$o->image_url,'audio'=>$o->audio_url,'order'=>$o->order,'vote_count'=>$c,'percentage'=>$total>0?round($c/$total*100):0];
        });
        $userVote = $user ? $q->votes()->where('user_id',$user->id)->value('question_option_id') : null;
        $hasLiked = $user ? $q->likes()->where('user_id',$user->id)->exists() : false;
        $author = null;
        if ($q->user && !$q->is_anonymous) {
            $author = ['id'=>$q->user->id,'name'=>$q->user->name,'username'=>$q->user->username,'avatar_url'=>$q->user->avatar_url];
        }
        return [
            'id'=>$q->id,'title'=>$q->title,'category'=>$q->category,
            'options'=>$opts,'total_votes'=>$total,
            'total_likes'=>$q->likes()->count(),'total_shares'=>$q->shares()->count(),
            'user_vote'=>$userVote,'has_liked'=>$hasLiked,'author'=>$author,
            'created_at'=>$q->created_at->diffForHumans(),
            'item_type'=>'question',
        ];
    }

    private function formatGroup(QuestionGroup $g): array
    {
        $user = Auth::user();
        $progress = $user ? \App\Models\GroupProgress::where('user_id',$user->id)->where('group_id',$g->id)->first() : null;
        $author = null;
        if ($g->user && !$g->is_anonymous) {
            $author = ['id'=>$g->user->id,'name'=>$g->user->name,'username'=>$g->user->username,'avatar_url'=>$g->user->avatar_url];
        }
        // Stats éliminatoire
        $winnerStats = $g->type === 'elimination' ? $g->winner_stats : [];

        return [
            'id'=>$g->id,'title'=>$g->title,'description'=>$g->description,
            'type'=>$g->type,'category'=>$g->category ?? 'divers',
            'total_questions'=>$g->questions_count ?? $g->questions()->count(),
            'total_items'=>$g->eliminationItems()->count(),
            'current_position'=>$progress?->current_position ?? 0,
            'completed'=>$progress?->completed ?? false,
            'author'=>$author,'created_at'=>$g->created_at->diffForHumans(),
            'item_type'=>'group',
            'winner_stats'=>$winnerStats,
        ];
    }

    private function trackHistory(Question $q): void
    {
        if (!Auth::check()) return;
        QuestionHistory::updateOrCreate(['user_id'=>Auth::id(),'question_id'=>$q->id],['viewed_at'=>now()]);
    }

    public function index(SearchRequest $request): Response
    {
        $search   = $request->validated('q');
        $category = $request->validated('category');
        $sort     = $request->validated('sort', 'recent');
        $type     = $request->input('type'); // simple, group, elimination

        // ── Questions simples ─────────────────────────────────────────────
        $qQuery = Question::published()
            ->where('question_type', 'simple')
            ->orWhereNull('question_type')
            ->with(['user','options'])
            ->withCount(['votes','likes']);

        if ($search)   $qQuery->search($search);
        if ($category) $qQuery->byCategory($category);

        // ── Groupes ───────────────────────────────────────────────────────
        $gQuery = QuestionGroup::where('is_published', true)
            ->with(['user'])
            ->withCount('questions');

        if ($category) $gQuery->where('category', $category);
        if ($search)   $gQuery->where('title', 'like', "%{$search}%");
        if ($type === 'group')       $gQuery->where('type', 'group');
        if ($type === 'elimination') $gQuery->where('type', 'elimination');

        // Récupérer et fusionner
        $questions = $qQuery->latest()->get()->map(fn($q) => $this->formatQuestion($q));
        $groups    = $gQuery->latest()->get()->map(fn($g) => $this->formatGroup($g));

        // Filtrer par type si demandé
        if ($type === 'simple') {
            $allItems = $questions;
        } elseif ($type === 'group' || $type === 'elimination') {
            $allItems = $groups->filter(fn($g) => $g['type'] === $type)->values();
        } else {
            $allItems = $questions->concat($groups);
        }

        // Trier
        $allItems = match($sort) {
            'popular' => $allItems->sortByDesc('total_likes'),
            'votes'   => $allItems->sortByDesc('total_votes'),
            default   => $allItems->sortByDesc('created_at'),
        };
        $allItems = $allItems->values();

        // Paginer manuellement
        $perPage = 10;
        $page    = (int) request()->input('page', 1);
        $total   = $allItems->count();
        $items   = $allItems->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = [
            'data'         => $items,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
            'total'        => $total,
        ];

        // Suggestions
        $suggestions = [];
        if (Auth::check()) {
            $answeredIds = Vote::where('user_id', Auth::id())->pluck('question_id');
            $suggestions = Question::published()
                ->where('question_type', 'simple')
                ->whereNotIn('id', $answeredIds)
                ->with(['user','options'])
                ->inRandomOrder()->limit(5)->get()
                ->map(fn($q) => $this->formatQuestion($q));
        }

        return Inertia::render('Questions/Index', [
            'questions'   => $paginated,
            'suggestions' => $suggestions,
            'filters'     => array_merge($request->validated(), ['type' => $type]),
            'categories'  => ['amour','aventure','nourriture','technologie','voyage','sport','musique','cinéma','divers'],
        ]);
    }

    public function show(Question $question): Response
    {
        abort_unless($question->is_published, 404);
        $this->trackHistory($question);
        $question->load(['user','options']);
        return Inertia::render('Questions/Show', ['question' => $this->formatQuestion($question)]);
    }

    public function create(): Response
    {
        return Inertia::render('Questions/Create');
    }

    public function store(\App\Http\Requests\StoreQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $q = Question::create(['user_id'=>Auth::id(),'title'=>$data['title']??null,'category'=>$data['category'],'is_anonymous'=>$data['is_anonymous']??false,'is_published'=>true,'question_type'=>'simple']);
        foreach ($data['options'] as $i=>$opt) {
            QuestionOption::create(['question_id'=>$q->id,'label'=>$opt['label'],'image'=>isset($opt['image'])&&$opt['image'] instanceof \Illuminate\Http\UploadedFile?$opt['image']->store('questions/images','public'):null,'audio'=>isset($opt['audio'])&&$opt['audio'] instanceof \Illuminate\Http\UploadedFile?$opt['audio']->store('questions/audio','public'):null,'order'=>$i]);
        }
        return redirect()->route('questions.show',$q)->with('success','Question créée !');
    }

    public function destroy(Question $question): RedirectResponse
    {
        abort_unless($question->user_id === Auth::id(), 403);
        foreach ($question->options as $o) {
            if ($o->image) Storage::disk('public')->delete($o->image);
            if ($o->audio) Storage::disk('public')->delete($o->audio);
        }
        $question->delete();
        return redirect()->route('questions.index')->with('success','Question supprimée.');
    }

    public function vote(VoteRequest $request, Question $question): JsonResponse
    {
        $optionId = $request->validated('option_id');
        abort_unless($question->options->pluck('id')->contains($optionId), 422);

        $existingVote = Vote::where('user_id',Auth::id())->where('question_id',$question->id)->first();

        if ($existingVote) {
            // Annuler l'ancien vote du compteur
            $existingVote->update(['question_option_id' => $optionId]);
        } else {
            // Premier vote
            Vote::create(['user_id'=>Auth::id(),'question_id'=>$question->id,'question_option_id'=>$optionId]);
        }

        // Enregistrer dans l'historique
        \App\Models\VoteHistory::where('user_id',Auth::id())->where('question_id',$question->id)->update(['is_current'=>false]);
        \App\Models\VoteHistory::create(['user_id'=>Auth::id(),'question_id'=>$question->id,'question_option_id'=>$optionId,'is_current'=>true]);

        $question->load('options');
        $total = $question->votes()->count();
        $opts  = $question->options->map(function($o) use($total) {
            $c = $o->votes()->count();
            return ['id'=>$o->id,'label'=>$o->label,'image'=>$o->image_url,'audio'=>$o->audio_url,'order'=>$o->order,'vote_count'=>$c,'percentage'=>$total>0?round($c/$total*100):0];
        });
        return response()->json(['message'=>'Vote enregistré !','question'=>array_merge($this->formatQuestion($question),['options'=>$opts,'total_votes'=>$total])]);
    }

    public function toggleLike(Question $question): JsonResponse
    {
        $like = Like::where('user_id',Auth::id())->where('question_id',$question->id)->first();
        $like ? $like->delete() : Like::create(['user_id'=>Auth::id(),'question_id'=>$question->id]);
        return response()->json(['liked'=>!$like,'total_likes'=>$question->likes()->count()]);
    }

    public function share(Question $question): JsonResponse
    {
        Share::create(['user_id'=>Auth::id(),'question_id'=>$question->id,'platform'=>request()->input('platform','link')]);
        return response()->json(['total_shares'=>$question->shares()->count(),'share_url'=>route('questions.show',$question)]);
    }
}
