<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'option_id' => [
                'required',
                'integer',
                'exists:question_options,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'option_id.required' => 'Vous devez choisir une option.',
            'option_id.exists'   => 'L\'option sélectionnée n\'existe pas.',
        ];
    }
}
