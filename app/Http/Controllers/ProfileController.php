<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Question;
use App\Models\QuestionHistory;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Page profil de l'utilisateur connecté.
     */
    public function show(): Response
    {
        $user = Auth::user();

        // Questions créées par l'utilisateur
        $myQuestions = Question::where('user_id', $user->id)
            ->published()
            ->with(['options'])
            ->withCount(['votes', 'likes'])
            ->latest()
            ->get()
            ->map(fn ($q) => [
                'id'          => $q->id,
                'title'       => $q->title,
                'category'    => $q->category,
                'options'     => $q->options->map(fn ($o) => [
                    'id'    => $o->id,
                    'label' => $o->label,
                    'image' => $o->image_url,
                ]),
                'total_votes' => $q->votes_count,
                'total_likes' => $q->likes_count,
                'created_at'  => $q->created_at->diffForHumans(),
            ]);

        // Historique (questions vues)
        $history = QuestionHistory::where('user_id', $user->id)
            ->with(['question.options'])
            ->orderByDesc('viewed_at')
            ->limit(20)
            ->get()
            ->map(fn ($h) => [
                'id'         => $h->question->id,
                'title'      => $h->question->title,
                'category'   => $h->question->category,
                'options'    => $h->question->options->map(fn ($o) => [
                    'id'    => $o->id,
                    'label' => $o->label,
                ]),
                'viewed_at'  => $h->viewed_at->diffForHumans(),
            ]);

        // Votes de l'utilisateur
        $myVotes = Vote::where('user_id', $user->id)
            ->with(['question.options', 'option'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($v) => [
                'question_id'    => $v->question_id,
                'question_title' => $v->question->title,
                'chosen_option'  => $v->option->label,
                'category'       => $v->question->category,
                'voted_at'       => $v->created_at->diffForHumans(),
            ]);

        return Inertia::render('Profile/Show', [
            'profileUser' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username,
                'bio'        => $user->bio,
                'avatar_url' => $user->avatar_url,
            ],
            'myQuestions' => $myQuestions,
            'history'     => $history,
            'myVotes'     => $myVotes,
            'stats'       => [
                'questions_created' => $myQuestions->count(),
                'questions_answered'=> $myVotes->count(),
                'total_votes_received' => $myQuestions->sum('total_votes'),
            ],
        ]);
    }

    /**
     * Formulaire d'édition du profil.
     */
    public function edit(): Response
    {
        $user = Auth::user();

        return Inertia::render('Profile/Edit', [
            'profileUser' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'username'   => $user->username,
                'email'      => $user->email,
                'bio'        => $user->bio,
                'avatar_url' => $user->avatar_url,
            ],
        ]);
    }

    /**
     * Met à jour le profil.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            // Supprime l'ancien avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);

        return redirect()->route('profile.show')
            ->with('success', 'Profil mis à jour avec succès !');
    }

    /**
     * Supprime le compte de l'utilisateur.
     */
    public function destroy(): RedirectResponse
    {
        $user = Auth::user();

        Auth::logout();

        // Supprime l'avatar si présent
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('questions.index')
            ->with('success', 'Votre compte a été supprimé.');
    }
}
