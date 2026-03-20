<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/login.form.email.label'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->rules([
                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                    $normalized = $this->normalizeLoginEmail((string) $value);
                    if ($normalized === '' || ! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                        $fail(__('validation.email'));
                    }
                },
            ]);
    }

    /**
     * Si no hay @, se asume el dominio configurado (p. ej. `owner` → `owner@deco.local`).
     */
    protected function normalizeLoginEmail(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (! str_contains($trimmed, '@')) {
            return $trimmed.'@'.config('deco.login_email_domain', 'deco.local');
        }

        return $trimmed;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $data['email'] = $this->normalizeLoginEmail((string) ($data['email'] ?? ''));

        return parent::getCredentialsFromFormData($data);
    }
}
