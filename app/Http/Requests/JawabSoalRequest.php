<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JawabSoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'attempt_id'  => ['required', 'integer', 'exists:exam_attempts,id'],
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'jawaban'     => ['nullable'],
            'is_ragu'     => ['boolean'],
        ];
    }
}
