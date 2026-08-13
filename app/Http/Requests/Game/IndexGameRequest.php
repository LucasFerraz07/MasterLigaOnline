<?php

namespace App\Http\Requests\Game;

use App\Enums\MatchStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexGameRequest extends FormRequest
{
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
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'league_id' => ['nullable', 'uuid', 'exists:leagues,id'],
            'season_id' => ['nullable', 'uuid', 'exists:seasons,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'half' => ['nullable', 'integer', Rule::in([1, 2])],
            'round' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::enum(MatchStatus::class)],
        ];
    }
}
