<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubRegion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClubRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:100', 'unique:clubs,name'],
            'crest'  => ['nullable', 'string', 'max:255'],
            'region' => ['required', Rule::enum(ClubRegion::class)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'   => 'nome',
            'crest'  => 'escudo',
            'region' => 'região',
        ];
    }
}
