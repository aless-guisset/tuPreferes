<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'            => ['nullable', 'string', 'max:255'],
            'category'         => ['required', 'string', 'in:amour,aventure,nourriture,technologie,voyage,sport,musique,cinéma,divers'],
            'is_anonymous'     => ['boolean'],
            'options'          => ['required', 'array', 'size:2'],
            'options.*.label'  => ['required', 'string', 'max:255'],
            'options.*.image'  => ['nullable', 'image', 'max:5120'], // 5 Mo max
            'options.*.audio'  => ['nullable', 'mimes:mp3,wav,ogg,m4a', 'max:10240'], // 10 Mo max
        ];
    }

    public function messages(): array
    {
        return [
            'options.size'          => 'Vous devez fournir exactement 2 options.',
            'options.*.label.required' => 'Chaque option doit avoir un texte.',
            'options.*.image.image' => 'Le fichier doit être une image valide.',
            'options.*.image.max'   => 'L\'image ne doit pas dépasser 5 Mo.',
            'options.*.audio.mimes' => 'Le fichier audio doit être au format MP3, WAV, OGG ou M4A.',
            'options.*.audio.max'   => 'L\'audio ne doit pas dépasser 10 Mo.',
            'category.in'           => 'La catégorie sélectionnée est invalide.',
        ];
    }
}
