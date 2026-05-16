<?php
namespace App\Http\Controllers;
use App\Models\{EliminationItem,EliminationSession,GroupProgress,Question,QuestionGroup,QuestionGroupItem,QuestionOption,Vote};
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB,Storage};
use Inertia\{Inertia,Response};

class QuestionGroupController extends Controller
{
    private function fmt(Question $q): array {
        $u = Auth::user();
        $total = $q->votes()->count();
        $opts = $q->options->map(function($o) use($total) {
            $c = $o->votes()->count();
            return ['id'=>$o->id,'label'=>$o->label,'image'=>$o->image_url,'audio'=>$o->audio_url,'order'=>$o->order,'vote_count'=>$c,'percentage'=>$total>0?round($c/$total*100):0];
        });
        return ['id'=>$q->id,'title'=>$q->title,'category'=>$q->category,'options'=>$opts,'total_votes'=>$total,'user_vote'=>$u?$q->votes()->where('user_id',$u->id)->value('question_option_id'):null];
    }

    private function fmtGroup(QuestionGroup $g): array {
        $u = Auth::user();
        $prog = $u ? GroupProgress::where('user_id',$u->id)->where('group_id',$g->id)->first() : null;
        $questions = $g->questions->map(fn($q) => $this->fmt($q))->values();
        return [
            'id'=>$g->id,'title'=>$g->title,'description'=>$g->description,'type'=>$g->type,
            'order'=>$g->order,'total_questions'=>$questions->count(),'questions'=>$questions,
            'current_position'=>$prog?->current_position??0,'completed'=>$prog?->completed??false,
            'author'=>$g->user&&!$g->is_anonymous?['id'=>$g->user->id,'name'=>$g->user->name,'username'=>$g->user->username,'avatar_url'=>$g->user->avatar_url]:null,
            'created_at'=>$g->created_at->diffForHumans(),
        ];
    }

    public function index(): Response {
        $groups = QuestionGroup::where('is_published',true)->with(['user','questions.options'])->withCount('questions')->latest()->paginate(10)->through(fn($g) => $this->fmtGroup($g));
        return Inertia::render('Groups/Index', ['groups'=>$groups]);
    }

    public function show(QuestionGroup $group): Response {
        abort_unless($group->is_published, 404);
        $group->load(['user','questions.options']);
        if ($group->type === 'elimination') return $this->showElimination($group);
        return Inertia::render('Groups/Show', ['group'=>$this->fmtGroup($group)]);
    }

    private function showElimination(QuestionGroup $group): Response {
        $u = Auth::user();
        $session = $duel = $stats = null;
        if ($u) {
            $session = EliminationSession::where('user_id',$u->id)->where('group_id',$group->id)->first();
            if ($session && !$session->completed) $duel = $session->current_duel;
            if ($session?->completed) $stats = $group->winner_stats;
        }
        $items = $group->eliminationItems->map(fn($i) => ['id'=>$i->id,'label'=>$i->label,'image'=>$i->image_url]);
        $fmtDuel = $duel ? ['a'=>['id'=>$duel['a']->id,'label'=>$duel['a']->label,'image'=>$duel['a']->image_url],'b'=>['id'=>$duel['b']->id,'label'=>$duel['b']->label,'image'=>$duel['b']->image_url]] : null;
        $fmtSession = $session ? ['id'=>$session->id,'remaining_count'=>count($session->remaining_items??[]),'completed'=>$session->completed,'winner'=>$session->completed?['label'=>$session->winner?->label,'image'=>$session->winner?->image_url]:null] : null;
        return Inertia::render('Groups/Elimination', ['group'=>$this->fmtGroup($group),'items'=>$items,'session'=>$fmtSession,'duel'=>$fmtDuel,'stats'=>$stats]);
    }

    public function create(): Response { return Inertia::render('Groups/Create'); }

    public function store(Request $request): RedirectResponse {
        return match($request->input('type','simple')) {
            'group'       => $this->storeGroup($request),
            'elimination' => $this->storeElimination($request),
            default       => $this->storeSimple($request),
        };
    }

    private function storeSimple(Request $request): RedirectResponse {
        $data = $request->validate(['title'=>['nullable','string','max:255'],'category'=>['required','string','in:amour,aventure,nourriture,technologie,voyage,sport,musique,cinéma,divers'],'is_anonymous'=>['boolean'],'options'=>['required','array','size:2'],'options.*.label'=>['required','string','max:255'],'options.*.image'=>['nullable','image','max:5120'],'options.*.audio'=>['nullable','mimes:mp3,wav,ogg,m4a','max:10240']]);
        $q = Question::create(['user_id'=>Auth::id(),'title'=>$data['title']??null,'category'=>$data['category'],'is_anonymous'=>$data['is_anonymous']??false,'is_published'=>true,'question_type'=>'simple']);
        foreach ($data['options'] as $i=>$opt) {
            QuestionOption::create(['question_id'=>$q->id,'label'=>$opt['label'],'image'=>isset($opt['image'])&&$opt['image'] instanceof \Illuminate\Http\UploadedFile?$opt['image']->store('questions/images','public'):null,'audio'=>isset($opt['audio'])&&$opt['audio'] instanceof \Illuminate\Http\UploadedFile?$opt['audio']->store('questions/audio','public'):null,'order'=>$i]);
        }
        return redirect()->route('questions.show',$q)->with('success','Question créée !');
    }

    private function storeGroup(Request $request): RedirectResponse {
        $data = $request->validate(['title'=>['required','string','max:255'],'description'=>['nullable','string','max:500'],'category'=>['required','string'],'is_anonymous'=>['boolean'],'questions'=>['required','array','min:2','max:20'],'questions.*.title'=>['nullable','string','max:255'],'questions.*.options'=>['required','array','size:2'],'questions.*.options.*.label'=>['required','string','max:255'],'questions.*.options.*.image'=>['nullable','image','max:5120']]);
        DB::transaction(function() use($data) {
            $group = QuestionGroup::create(['user_id'=>Auth::id(),'title'=>$data['title'],'description'=>$data['description']??null,'type'=>'group','is_anonymous'=>$data['is_anonymous']??false,'is_published'=>true]);
            foreach ($data['questions'] as $pos=>$qd) {
                $q = Question::create(['user_id'=>Auth::id(),'title'=>$qd['title']??null,'category'=>$data['category'],'is_published'=>true,'question_type'=>'group','group_id'=>$group->id]);
                QuestionGroupItem::create(['group_id'=>$group->id,'question_id'=>$q->id,'position'=>$pos]);
                foreach ($qd['options'] as $i=>$opt) {
                    QuestionOption::create(['question_id'=>$q->id,'label'=>$opt['label'],'image'=>isset($opt['image'])&&$opt['image'] instanceof \Illuminate\Http\UploadedFile?$opt['image']->store('questions/images','public'):null,'order'=>$i]);
                }
            }
        });
        return redirect()->route('groups.index')->with('success','Groupe créé !');
    }

    private function storeElimination(Request $request): RedirectResponse {
        $data = $request->validate(['title'=>['required','string','max:255'],'description'=>['nullable','string','max:500'],'order'=>['required','in:sequential,random'],'is_anonymous'=>['boolean'],'items'=>['required','array','min:3','max:64'],'items.*.label'=>['required','string','max:100'],'items.*.image'=>['nullable','image','max:5120']]);
        DB::transaction(function() use($data) {
            $group = QuestionGroup::create(['user_id'=>Auth::id(),'title'=>$data['title'],'description'=>$data['description']??null,'type'=>'elimination','order'=>$data['order'],'is_anonymous'=>$data['is_anonymous']??false,'is_published'=>true]);
            foreach ($data['items'] as $pos=>$item) {
                EliminationItem::create(['group_id'=>$group->id,'label'=>$item['label'],'image'=>isset($item['image'])&&$item['image'] instanceof \Illuminate\Http\UploadedFile?$item['image']->store('elimination/images','public'):null,'position'=>$pos]);
            }
        });
        return redirect()->route('groups.index')->with('success','Tournoi créé !');
    }

    public function voteInGroup(Request $request, QuestionGroup $group, Question $question): JsonResponse {
        $optId = $request->validate(['option_id'=>['required','integer','exists:question_options,id']])['option_id'];
        abort_unless($question->options->pluck('id')->contains($optId), 422);
        if (Vote::where('user_id',Auth::id())->where('question_id',$question->id)->exists())
            return response()->json(['message'=>'Déjà voté.'], 422);
        Vote::create(['user_id'=>Auth::id(),'question_id'=>$question->id,'question_option_id'=>$optId]);
        $items = $group->groupItems;
        $current = $items->search(fn($i) => $i->question_id === $question->id);
        $next = $items->get($current + 1);
        $completed = $next === null;
        GroupProgress::updateOrCreate(['user_id'=>Auth::id(),'group_id'=>$group->id],['current_position'=>$current+1,'completed'=>$completed]);
        $question->load('options');
        return response()->json(['question'=>$this->fmt($question),'next_question_id'=>$next?->question_id,'completed'=>$completed]);
    }

    public function startElimination(QuestionGroup $group): JsonResponse {
        abort_unless($group->type === 'elimination', 422);
        $items = $group->eliminationItems;
        abort_if($items->count() < 2, 422);
        $ids = $items->pluck('id')->toArray();
        if ($group->order === 'random') shuffle($ids);
        $session = EliminationSession::updateOrCreate(
            ['user_id'=>Auth::id(),'group_id'=>$group->id],
            ['remaining_items'=>$ids,'eliminated_items'=>[],'winner_item_id'=>null,'completed'=>false]
        );
        $duel = $session->current_duel;
        return response()->json(['session_id'=>$session->id,'remaining_count'=>count($ids),'duel'=>$duel?['a'=>['id'=>$duel['a']->id,'label'=>$duel['a']->label,'image'=>$duel['a']->image_url],'b'=>['id'=>$duel['b']->id,'label'=>$duel['b']->label,'image'=>$duel['b']->image_url]]:null]);
    }

    public function chooseElimination(Request $request, QuestionGroup $group): JsonResponse {
        $winnerId = $request->validate(['winner_id'=>['required','integer','exists:elimination_items,id']])['winner_id'];
        $session = EliminationSession::where('user_id',Auth::id())->where('group_id',$group->id)->where('completed',false)->firstOrFail();
        $session->advance($winnerId);
        $session->refresh();
        if ($session->completed) {
            return response()->json(['completed'=>true,'winner'=>['label'=>$session->winner?->label,'image'=>$session->winner?->image_url],'stats'=>$group->winner_stats,'remaining_count'=>1]);
        }
        $duel = $session->current_duel;
        return response()->json(['completed'=>false,'remaining_count'=>count($session->remaining_items),'duel'=>$duel?['a'=>['id'=>$duel['a']->id,'label'=>$duel['a']->label,'image'=>$duel['a']->image_url],'b'=>['id'=>$duel['b']->id,'label'=>$duel['b']->label,'image'=>$duel['b']->image_url]]:null]);
    }
}
