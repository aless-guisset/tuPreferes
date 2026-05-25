<?php
namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FollowController extends Controller
{
    /**
     * Toggle follow/unfollow
     */
    public function toggle(User $user): JsonResponse
    {
        abort_if($user->id === Auth::id(), 422, 'Tu ne peux pas te suivre toi-même.');

        $existing = Follow::where('follower_id', Auth::id())
            ->where('following_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $following = false;
        } else {
            Follow::create([
                'follower_id'  => Auth::id(),
                'following_id' => $user->id,
            ]);
            $following = true;
        }

        return response()->json([
            'following'       => $following,
            'followers_count' => $user->followers()->count(),
        ]);
    }

    /**
     * Liste des abonnés d'un user
     */
    public function followers(User $user): Response
    {
        $followers = $user->followers()
            ->with('follower')
            ->latest()
            ->paginate(20)
            ->through(fn($f) => [
                'id'              => $f->follower->id,
                'name'            => $f->follower->name,
                'username'        => $f->follower->username,
                'avatar_url'      => $f->follower->avatar_url,
                'followers_count' => $f->follower->followers_count,
                'is_following'    => Auth::check() ? Auth::user()->isFollowing($f->follower->id) : false,
            ]);

        return Inertia::render('Profile/Followers', [
            'profileUser' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ],
            'followers' => $followers,
            'tab'       => 'followers',
        ]);
    }

    /**
     * Liste des abonnements d'un user
     */
    public function following(User $user): Response
    {
        $following = $user->following()
            ->with('following')
            ->latest()
            ->paginate(20)
            ->through(fn($f) => [
                'id'              => $f->following->id,
                'name'            => $f->following->name,
                'username'        => $f->following->username,
                'avatar_url'      => $f->following->avatar_url,
                'followers_count' => $f->following->followers_count,
                'is_following'    => Auth::check() ? Auth::user()->isFollowing($f->following->id) : false,
            ]);

        return Inertia::render('Profile/Followers', [
            'profileUser' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ]);
            'followers' => $following,
            'tab'       => 'following',
        ]);
    }

    /**
     * Page profil public d'un utilisateur
     */
    public function profile(User $user): Response
    {
        $currentUser = Auth::user();

        $questions = \App\Models\Question::where('user_id', $user->id)
            ->published()
            ->where('is_hidden', false)
            ->with(['options'])
            ->withCount(['votes','likes'])
            ->latest()
            ->get()
            ->map(fn($q) => [
                'id'          => $q->id,
                'title'       => $q->title,
                'category'    => $q->category,
                'options'     => $q->options->map(fn($o) => ['id'=>$o->id,'label'=>$o->label,'image'=>$o->image_url]),
                'total_votes' => $q->votes_count,
                'total_likes' => $q->likes_count,
                'created_at'  => $q->created_at->diffForHumans(),
            ]);

        return Inertia::render('Profile/Public', [
            'profileUser' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'username'        => $user->username,
                'bio'             => $user->bio,
                'avatar_url'      => $user->avatar_url,
                'followers_count' => $user->followers_count,
                'following_count' => $user->following_count,
                'is_following'    => $currentUser ? $currentUser->isFollowing($user->id) : false,
                'is_own_profile'  => $currentUser?->id === $user->id,
            ],
            'questions' => $questions,
            'stats' => [
                'questions_created' => $questions->count(),
                'total_votes_received' => $questions->sum('total_votes'),
            ],
        ]);
    }
}
