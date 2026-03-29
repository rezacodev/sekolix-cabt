<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1])
            ->default(app()->isLocal() ? 'admin@cabt.local' : null);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new \Illuminate\Support\HtmlString(
                \Filament\Support\Facades\FilamentView::renderHook(
                    'panels::auth.login.form.password.after',
                )
            ) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->default(app()->isLocal() ? 'admin123' : null);
    }

    /**
     * Jika yang login adalah peserta (level 1), arahkan ke halaman login peserta.
     */
    protected function throwFailureValidationException(): never
    {
        // Cek apakah user memang ada tapi level-nya peserta
        $email = $this->data['email'] ?? '';
        $user  = User::where('email', $email)->first();

        if ($user && $user->level === User::LEVEL_PESERTA) {
            throw ValidationException::withMessages([
                'data.email' => 'Akun peserta tidak dapat login di sini. Buka /login untuk login sebagai peserta.',
            ]);
        }

        parent::throwFailureValidationException();
    }
}
