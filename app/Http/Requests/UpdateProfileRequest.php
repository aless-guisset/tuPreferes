<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore(auth()->id()),
            ],
            'bio'    => ['nullable', 'string', 'max:160'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'     => 'Ce nom d\'utilisateur est déjà pris.',
            'username.alpha_dash' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'avatar.image'        => 'L\'avatar doit être une image.',
            'avatar.max'          => 'L\'avatar ne doit pas dépasser 2 Mo.',
        ];
    }
}
