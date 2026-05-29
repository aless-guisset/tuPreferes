<?php
namespace App\Http\Controllers;
use App\Models\{Question,QuestionGroup,Report,User,Vote,Like};
use Illuminate\Http\{JsonResponse,Request};
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
                'id'              => $u->id,
                'name'            => $u->name,
                'username'        => $u->username,
                'email'           => $this->maskEmail($u->email),
                'role'            => $u->role?->name,
                'banned'          => $u->banned,
                'ban_reason'      => $u->ban_reason,
                'avatar_url'      => $u->avatar_url,
                'questions_count' => $u->questions_count,
                'votes_count'     => $u->votes_count,
                'created_at'      => $u->created_at->format('d/m/Y'),
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
                'author'        => $q->user ? ['name'=>$q->user->name] : null,
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
                'author'        => $g->user ? ['name'=>$g->user->name] : null,
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
                'id'               => $r->id,
                'reason'           => $r->reason,
                'comment'          => $r->comment,
                'status'           => $r->status,
                'reportable_type'  => class_basename($r->reportable_type),
                'reportable_id'    => $r->reportable_id,
                'reportable_title' => $r->reportable?->title ?? 'Supprimé',
                'reporter'         => ['name'=>$r->user->name, 'email'=>$this->maskEmail($r->user->email)],
                'created_at'       => $r->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Reports', ['reports' => $reports]);
    }

    // ── Analytics ─────────────────────────────────────────────────────────────
    public function analytics(): Response
    {
        $registrations = User::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')->orderBy('date')->get();

        $votes = Vote::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')->orderBy('date')->get();

        $questions = Question::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')->orderBy('date')->get();

        return Inertia::render('Admin/Analytics', [
            'stats'         => $this->getStats(),
            'registrations' => $registrations,
            'votes'         => $votes,
            'questions'     => $questions,
        ]);
    }

    // ── Actions ───────────────────────────────────────────────────────────────
    public function updateRole(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === Auth::id(), 403, 'Impossible de changer son propre rôle.');
        $request->validate(['role' => ['required','in:user,admin']]);
        $role = DB::table('roles')->where('name', $request->role)->first();
        abort_unless($role, 422, 'Rôle invalide.');
        $user->update(['role_id' => $role->id]);
        return response()->json(['message' => 'Rôle mis à jour.']);
    }

    public function banUser(Request $request, User $user): JsonResponse
    {
        abort_if($user->id === Auth::id(), 403);
        $request->validate(['reason' => ['nullable','string','max:255']]);
        $nowBanning = !$user->banned;
        DB::table('users')->where('id', $user->id)->update([
            'banned'     => $nowBanning,
            'banned_at'  => $nowBanning ? now() : null,
            'ban_reason' => $nowBanning ? $request->reason : null,
            'updated_at' => now(),
        ]);
        return response()->json(['banned' => $nowBanning]);
    }

    public function deleteUser(User $user): JsonResponse
    {
        abort_if($user->id === Auth::id(), 403);
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    public function hidePost(Request $request, int $id): JsonResponse
    {
        $type = $request->input('type', 'question');
        if ($type === 'question') {
            $post = Question::findOrFail($id);
            $post->update(['is_hidden' => !$post->is_hidden]);
        } else {
            $post = QuestionGroup::findOrFail($id);
            $post->update(['is_hidden' => !$post->is_hidden]);
        }
        return response()->json(['is_hidden' => $post->fresh()->is_hidden]);
    }

    public function deletePost(Request $request, int $id): JsonResponse
    {
        $type = $request->input('type', 'question');
        if ($type === 'question') {
            Question::findOrFail($id)->delete();
        } else {
            QuestionGroup::findOrFail($id)->delete();
        }
        return response()->json(['message' => 'Post supprimé.']);
    }

    public function resolveReport(Request $request, Report $report): JsonResponse
    {
        $request->validate(['action' => ['required','in:resolve,reject']]);
        $report->update([
            'status'      => $request->action === 'resolve' ? 'resolved' : 'rejected',
            'resolved_by' => Auth::id(),
            'resolved_at' => now(),
        ]);
        return response()->json(['message' => 'Signalement traité.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 1);
        $masked  = $visible . str_repeat('*', max(3, strlen($local) - 1));
        return $masked . '@' . $domain;
    }

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
