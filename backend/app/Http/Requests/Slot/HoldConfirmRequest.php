<?php

namespace App\Http\Requests\Slot;

use Illuminate\Foundation\Http\FormRequest;

class HoldConfirmRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // Можно добавить дополнительные правила валидации при необходимости
        ];
    }
}

