<?php
namespace App\Http\Controllers;
use App\Models\{Question,QuestionGroup,Report,User,Vote,Like};
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Support\Facades\{Auth,DB};
use Inertia\{Inertia,Response};

class AdminController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────────
    public function index(): Response
    {
        return Inertia::render('Admin/Index', [
            'stats' => $this->getStats(),
        ]);
    }

    // ── Users ─────────────────────────────────────────────────────────────────
    public function users(Request $request): Response
    {
        $users = User::with('role')
            ->when($request->search, fn($q) => $q->where('name','like',"%{$request->search}%")->orWhere('email','like',"%{$request->search}%"))
            ->withCount(['questions','votes'])
            ->latest()
            ->paginate(20)
            ->through(fn($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'username'   => $u->username,
                'email'      => $u->email,
                'role'       => $u->role?->name,
                'banned'     => $u->banned,
                'ban_reason' => $u->ban_reason,
                'avatar_url' => $u->avatar_url,
                'questions_count' => $u->questions_count,
                'votes_count'     => $u->votes_count,
                'created_at' => $u->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('Admin/Users', ['users' => $users, 'filters' => $request->only('search')]);
    }

    // ── Posts ─────────────────────────────────────────────────────────────────
    public function posts(Request $request): Response
    {
        $questions = Question::with(['user','options'])
            ->when($request->search, fn($q) => $q->where('title','like',"%{$request->search}%"))
            ->withCount(['votes','reports'])
            ->latest()
            ->paginate(15)
            ->through(fn($q) => [
                'id'            => $q->id,
                'title'         => $q->title,
                'category'      => $q->category,
                'is_hidden'     => $q->is_hidden,
                'is_anonymous'  => $q->is_anonymous,
                'votes_count'   => $q->votes_count,
                'reports_count' => $q->reports_count,
                'author'        => $q->user ? ['name'=>$q->user->name,'email'=>$q->user->email] : null,
                'created_at'    => $q->created_at->format('d/m/Y'),
                'type'          => 'question',
            ]);

        $groups = QuestionGroup::with('user')
            ->when($request->search, fn($q) => $q->where('title','like',"%{$request->search}%"))
            ->withCount('reports')
            ->latest()
            ->paginate(15)
            ->through(fn($g) => [
                'id'            => $g->id,
                'title'         => $g->title,
                'category'      => $g->category,
                'is_hidden'     => $g->is_hidden,
                'is_anonymous'  => $g->is_anonymous,
                'votes_count'   => 0,
                'reports_count' => $g->reports_count,
                'author'        => $g->user ? ['name'=>$g->user->name,'email'=>$g->user->email] : null,
                'created_at'    => $g->created_at->format('d/m/Y'),
                'type'          => $g->type,
            ]);

        return Inertia::render('Admin/Posts', [
            'questions' => $questions,
            'groups'    => $groups,
            'filters'   => $request->only('search'),
        ]);
    }

    // ── Reports ───────────────────────────────────────────────────────────────
    public function reports(): Response
    {
        $reports = Report::with(['user','reportable','resolvedBy'])
            ->pending()
            ->latest()
            ->paginate(20)
            ->through(fn($r) => [
                'id'             => $r->id,
                'reason'         => $r->reason,
                'comment'        => $r->comment,
                'status'         => $r->status,
                'reportable_type'=> class_basename($r->reportable_type),
                'reportable_id'  => $r->reportable_id,
                'reportable_title' => $r->reportable?->title ?? 'Supprimé',
                'reporter'       => ['name'=>$r->user->name,'email'=>$r->user->email],
                'created_at'     => $r->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Reports', ['reports' => $reports]);
    }

    // ── Analytics ─────────────────────────────────────────────────────────────
    public function analytics(): Response
    {
        // Inscriptions par jour (30 derniers jours)
        $registrations = User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Votes par jour
        $votes = Vote::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Questions par jour
        $questions = Question::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Admin/Analytics', [
            'stats'         => $this->getStats(),
            'registrations' => $registrations,
            'votes'         => $votes,
            'questions'     => $questions,
        ]);
    }

    // ── Actions ───────────────────────────────────────────────────────────────
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403, 'Impossible de changer son propre rôle.');
        $request->validate(['role' => ['required','in:user,admin']]);
        $role = DB::table('roles')->where('name', $request->role)->first();
        $user->update(['role_id' => $role->id]);
        return back()->with('success', 'Rôle mis à jour.');
    }

    public function banUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403);
        $request->validate(['reason' => ['nullable','string','max:255']]);
        $user->update([
            'banned'     => !$user->banned,
            'banned_at'  => !$user->banned ? now() : null,
            'ban_reason' => !$user->banned ? $request->reason : null,
        ]);
        return back()->with('success', $user->banned ? 'Utilisateur banni.' : 'Bannissement levé.');
    }

    public function deleteUser(User $user): RedirectResponse
    {
        abort_if($user->id === Auth::id(), 403);
        $user->delete();
        return back()->with('success', 'Utilisateur supprimé.');
    }

    public function hidePost(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type', 'question');
        if ($type === 'question') {
            $post = Question::findOrFail($id);
            $post->update(['is_hidden' => !$post->is_hidden, 'is_anonymous' => true]);
        } else {
            $post = QuestionGroup::findOrFail($id);
            $post->update(['is_hidden' => !$post->is_hidden, 'is_anonymous' => true]);
        }
        return back()->with('success', 'Post mis à jour.');
    }

    public function deletePost(Request $request, int $id): RedirectResponse
    {
        $type = $request->input('type', 'question');
        if ($type === 'question') {
            Question::findOrFail($id)->delete();
        } else {
            QuestionGroup::findOrFail($id)->delete();
        }
        return back()->with('success', 'Post supprimé.');
    }

    public function resolveReport(Request $request, Report $report): RedirectResponse
    {
        $request->validate(['action' => ['required','in:resolve,reject']]);
        $report->update([
            'status'      => $request->action === 'resolve' ? 'resolved' : 'rejected',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);
        return back()->with('success', 'Signalement traité.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function getStats(): array
    {
        return [
            'total_users'     => User::count(),
            'total_questions' => Question::count(),
            'total_groups'    => QuestionGroup::count(),
            'total_votes'     => Vote::count(),
            'total_likes'     => Like::count(),
            'total_reports'   => Report::pending()->count(),
            'banned_users'    => User::where('banned', true)->count(),
            'hidden_posts'    => Question::where('is_hidden', true)->count() + QuestionGroup::where('is_hidden', true)->count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'new_votes_today' => Vote::whereDate('created_at', today())->count(),
        ];
    }
}
