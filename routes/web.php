<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

// ─── Questions (publiques) ────────────────────────────────────────────────────

Route::get('/', [QuestionController::class, 'index'])->name('questions.index');
Route::get('/question/{question}', [QuestionController::class, 'show'])->name('questions.show');

// ─── Authentification ─────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login']);
    Route::get('/inscription', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register']);
});

Route::post('/deconnexion', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ─── Questions (authentifiées) ────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    // Création
    Route::get('/creer', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::delete('/question/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');

    // Interactions (retournent du JSON)
    Route::post('/question/{question}/voter', [QuestionController::class, 'vote'])->name('questions.vote');
    Route::post('/question/{question}/like', [QuestionController::class, 'toggleLike'])->name('questions.like');
    Route::post('/question/{question}/partager', [QuestionController::class, 'share'])->name('questions.share');

    // Profil
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profil/modifier', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Groupes ──────────────────────────────────────────────────────────────────
use App\Http\Controllers\QuestionGroupController;

Route::get('/groupes', [QuestionGroupController::class, 'index'])->name('groups.index');
Route::get('/groupe/{group}', [QuestionGroupController::class, 'show'])->name('groups.show');

Route::middleware('auth')->group(function () {
    Route::get('/creer-groupe', [QuestionGroupController::class, 'create'])->name('groups.create');
    Route::post('/groupes', [QuestionGroupController::class, 'store'])->name('groups.store');
    Route::post('/groupe/{group}/question/{question}/voter', [QuestionGroupController::class, 'voteInGroup'])->name('groups.vote');
    Route::post('/groupe/{group}/elimination/start',  [QuestionGroupController::class, 'startElimination'])->name('groups.elimination.start');
    Route::post('/groupe/{group}/elimination/choose', [QuestionGroupController::class, 'chooseElimination'])->name('groups.elimination.choose');
});

// ─── Admin ────────────────────────────────────────────────────────────────────
use App\Http\Controllers\AdminController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',           [AdminController::class, 'index'])->name('index');
    Route::get('/users',      [AdminController::class, 'users'])->name('users');
    Route::get('/posts',      [AdminController::class, 'posts'])->name('posts');
    Route::get('/reports',    [AdminController::class, 'reports'])->name('reports');
    Route::get('/analytics',  [AdminController::class, 'analytics'])->name('analytics');

    // Actions
    Route::patch('/users/{user}/role',    [AdminController::class, 'updateRole'])->name('users.role');
    Route::delete('/users/{user}',        [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::patch('/users/{user}/ban',     [AdminController::class, 'banUser'])->name('users.ban');
    Route::patch('/posts/{id}/hide',      [AdminController::class, 'hidePost'])->name('posts.hide');
    Route::delete('/posts/{id}',          [AdminController::class, 'deletePost'])->name('posts.delete');
    Route::patch('/reports/{report}/resolve', [AdminController::class, 'resolveReport'])->name('reports.resolve');
});

// ─── Signalements ─────────────────────────────────────────────────────────────
Route::post('/question/{question}/signaler', function(\Illuminate\Http\Request $request, \App\Models\Question $question) {
    $request->validate(['reason'=>['required','string'],'comment'=>['nullable','string','max:500']]);
    \App\Models\Report::create([
        'user_id'          => auth()->id(),
        'reportable_type'  => \App\Models\Question::class,
        'reportable_id'    => $question->id,
        'reason'           => $request->reason,
        'comment'          => $request->comment,
    ]);
    return response()->json(['message' => 'Signalement envoyé.']);
})->name('questions.report')->middleware('auth');

Route::post('/groupe/{group}/signaler', function(\Illuminate\Http\Request $request, \App\Models\QuestionGroup $group) {
    $request->validate(['reason'=>['required','string'],'comment'=>['nullable','string','max:500']]);
    \App\Models\Report::create([
        'user_id'          => auth()->id(),
        'reportable_type'  => \App\Models\QuestionGroup::class,
        'reportable_id'    => $group->id,
        'reason'           => $request->reason,
        'comment'          => $request->comment,
    ]);
    return response()->json(['message' => 'Signalement envoyé.']);
})->name('groups.report')->middleware('auth');

// ─── OAuth Social ─────────────────────────────────────────────────────────────
use App\Http\Controllers\SocialAuthController;

Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect',  [SocialAuthController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback',  [SocialAuthController::class, 'callback'])->name('social.callback');
});
