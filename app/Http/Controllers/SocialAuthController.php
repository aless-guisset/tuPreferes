<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirige vers le provider OAuth
     */
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'apple']), 404);
        return Socialite::driver($provider)
            ->redirectUrl($this->callbackUrl($provider))
            ->redirect();
    }

    /**
     * Callback après authentification OAuth
     */
    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'apple']), 404);

        try {
            $socialUser = Socialite::driver($provider)
                ->redirectUrl($this->callbackUrl($provider))
                ->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Connexion '.$provider.' échouée. Réessaie.');
        }

        // Chercher un user existant par provider ID
        $field = $provider.'_id';
        $user  = User::where($field, $socialUser->getId())->first();

        // Sinon chercher par email
        if (!$user && $socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
            if ($user) {
                $user->update([$field => $socialUser->getId()]);
            }
        }

        // Sinon créer un nouveau compte
        if (!$user) {
            $name     = $socialUser->getName() ?? $socialUser->getNickname() ?? 'User';
            $username = $this->generateUsername($name);

            $user = User::create([
                'name'           => $name,
                'username'       => $username,
                'email'          => $socialUser->getEmail() ?? $username.'@'.$provider.'.oauth',
                'password'       => null,
                $field           => $socialUser->getId(),
                'oauth_provider' => $provider,
                'oauth_token'    => $socialUser->token ?? null,
                'avatar'         => $socialUser->getAvatar() ?? null,
                'role_id'        => 1, // user par défaut
                'email_verified_at' => now(),
            ]);
        }

        // Vérifier si banni
        if ($user->banned) {
            return redirect()->route('login')
                ->with('error', 'Ton compte a été suspendu. Raison : '.($user->ban_reason ?? 'non précisée'));
        }

        Auth::login($user, true);

        return redirect()->intended(route('questions.index'))
            ->with('success', 'Bienvenue '.$user->name.' !');
    }

    /**
     * Construit l'URL de callback selon le domaine courant
     */
    private function callbackUrl(string $provider): string
    {
        return request()->getSchemeAndHttpHost() . '/auth/' . $provider . '/callback';
    }

    /**
     * Génère un username unique depuis le nom
     */
    private function generateUsername(string $name): string
    {
        $base     = Str::slug(Str::lower($name), '_');
        $base     = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'user';
        $username = $base;
        $i        = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.$i++;
        }

        return $username;
    }
}
