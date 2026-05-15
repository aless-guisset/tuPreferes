<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\VoteRequest;
use App\Models\Like;
use App\Models\Question;
use App\Models\QuestionHistory;
use App\Models\QuestionOption;
use App\Models\Share;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Formate une question pour le front, avec stats et état utilisateur.
     */
    private function formatQuestion(Question $question): array
    {
        $user      = Auth::user();
        $totalVotes = $question->votes()->count();

        $options = $question->options->map(function (QuestionOption $option) use ($totalVotes) {
            $count      = $option->votes()->count();
            $percentage = $totalVotes > 0 ? round(($count / $totalVotes) * 100) : 0;

            return [
                'id'         => $option->id,
                'label'      => $option->label,
                'image'      => $option->image_url,
                'audio'      => $option->audio_url,
                'order'      => $option->order,
                'vote_count' => $count,
                'percentage' => $percentage,
            ];
        });

        $userVote = $user
            ? $question->votes()->where('user_id', $user->id)->value('question_option_id')
            : null;

        $hasLiked = $user
            ? $question->likes()->where('user_id', $user->id)->exists()
            : false;

        $author = null;
        if ($question->user && ! $question->is_anonymous) {
            $author = [
                'id'         => $question->user->id,
                'name'       => $question->user->name,
                'username'   => $question->user->username,
                'avatar_url' => $question->user->avatar_url,
            ];
        }

        return [
            'id'          => $question->id,
            'title'       => $question->title,
            'category'    => $question->category,
            'options'     => $options,
            'total_votes' => $totalVotes,
            'total_likes' => $question->likes()->count(),
            'total_shares'=> $question->shares()->count(),
            'user_vote'   => $userVote,
            'has_liked'   => $hasLiked,
            'author'      => $author,
            'created_at'  => $question->created_at->diffForHumans(),
        ];
    }

    /**
     * Enregistre la question dans l'historique de l'utilisateur connecté.
     */
    private function trackHistory(Question $question): void
    {
        if (! Auth::check()) {
            return;
        }

        QuestionHistory::updateOrCreate(
            ['user_id' => Auth::id(), 'question_id' => $question->id],
            ['viewed_at' => now()]
        );
    }

    // ─── Actions publiques ────────────────────────────────────────────────────

    /**
     * Page d'accueil — flux de questions.
     */
    public function index(SearchRequest $request): Response
    {
        $query = Question::published()
            ->with(['user', 'options'])
            ->withCount(['votes', 'likes']);

        if ($search = $request->validated('q')) {
            $query->search($search);
        }

        if ($category = $request->validated('category')) {
            $query->byCategory($category);
        }

        $sort = $request->validated('sort', 'recent');
        match ($sort) {
            'popular' => $query->orderByDesc('likes_count'),
            'votes'   => $query->orderByDesc('votes_count'),
            default   => $query->latest(),
        };

        $questions = $query->paginate(10)->through(
            fn ($q) => $this->formatQuestion($q)
        );

        // Suggestions pour les utilisateurs connectés
        $suggestions = [];
        if (Auth::check()) {
            $answeredIds = Vote::where('user_id', Auth::id())->pluck('question_id');
            $suggestions = Question::published()
                ->whereNotIn('id', $answeredIds)
                ->with(['user', 'options'])
                ->inRandomOrder()
                ->limit(5)
                ->get()
                ->map(fn ($q) => $this->formatQuestion($q));
        }

        return Inertia::render('Questions/Index', [
            'questions'  => $questions,
            'suggestions'=> $suggestions,
            'filters'    => $request->validated(),
            'categories' => Question::distinct()->pluck('category'),
        ]);
    }

    /**
     * Affiche une question en détail.
     */
    public function show(Question $question): Response
    {
        abort_unless($question->is_published, 404);

        $this->trackHistory($question);

        $question->load(['user', 'options']);

        return Inertia::render('Questions/Show', [
            'question' => $this->formatQuestion($question),
        ]);
    }

    /**
     * Formulaire de création.
     */
    public function create(): Response
    {
        return Inertia::render('Questions/Create');
    }

    /**
     * Enregistre une nouvelle question.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $question = Question::create([
            'user_id'      => Auth::id(),
            'title'        => $data['title'] ?? null,
            'category'     => $data['category'],
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'is_published' => true,
        ]);

        foreach ($data['options'] as $index => $optionData) {
            $imagePath = null;
            $audioPath = null;

            if (isset($optionData['image']) && $optionData['image'] instanceof \Illuminate\Http\UploadedFile) {
                $imagePath = $optionData['image']->store('questions/images', 'public');
            }

            if (isset($optionData['audio']) && $optionData['audio'] instanceof \Illuminate\Http\UploadedFile) {
                $audioPath = $optionData['audio']->store('questions/audio', 'public');
            }

            QuestionOption::create([
                'question_id' => $question->id,
                'label'       => $optionData['label'],
                'image'       => $imagePath,
                'audio'       => $audioPath,
                'order'       => $index,
            ]);
        }

        return redirect()->route('questions.show', $question)
            ->with('success', 'Ta question a été créée avec succès !');
    }

    /**
     * Supprime une question (auteur ou admin seulement).
     */
    public function destroy(Question $question): RedirectResponse
    {
        abort_unless($question->user_id === Auth::id(), 403);

        // Supprime les fichiers associés
        foreach ($question->options as $option) {
            if ($option->image) {
                Storage::disk('public')->delete($option->image);
            }
            if ($option->audio) {
                Storage::disk('public')->delete($option->audio);
            }
        }

        $question->delete();

        return redirect()->route('questions.index')
            ->with('success', 'Question supprimée.');
    }

    // ─── Actions d'interaction ────────────────────────────────────────────────

    /**
     * Vote pour une option.
     */
    public function vote(VoteRequest $request, Question $question): \Illuminate\Http\JsonResponse
    {
        $optionId = $request->validated('option_id');

        // Vérifie que l'option appartient bien à cette question
        abort_unless(
            $question->options->pluck('id')->contains($optionId),
            422,
            'Cette option n\'appartient pas à cette question.'
        );

        // Empêche le double vote
        $existing = Vote::where('user_id', Auth::id())
            ->where('question_id', $question->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Vous avez déjà répondu à cette question.'], 422);
        }

        Vote::create([
            'user_id'            => Auth::id(),
            'question_id'        => $question->id,
            'question_option_id' => $optionId,
        ]);

        // Recharge les stats
        $question->load('options');
        $formatted = $this->formatQuestion($question);

        return response()->json([
            'message'  => 'Vote enregistré !',
            'question' => $formatted,
        ]);
    }

    /**
     * Toggle like sur une question.
     */
    public function toggleLike(Question $question): \Illuminate\Http\JsonResponse
    {
        $like = Like::where('user_id', Auth::id())
            ->where('question_id', $question->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            Like::create([
                'user_id'     => Auth::id(),
                'question_id' => $question->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'liked'       => $liked,
            'total_likes' => $question->likes()->count(),
        ]);
    }

    /**
     * Enregistre un partage.
     */
    public function share(Question $question): \Illuminate\Http\JsonResponse
    {
        $platform = request()->input('platform', 'link');

        Share::create([
            'user_id'     => Auth::id(),
            'question_id' => $question->id,
            'platform'    => $platform,
        ]);

        return response()->json([
            'message'      => 'Partage enregistré.',
            'total_shares' => $question->shares()->count(),
            'share_url'    => route('questions.show', $question),
        ]);
    }
}
