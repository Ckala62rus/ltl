<?php

namespace App\Http\Requests\Slot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;


class HoldStoreRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Шаг 1: Получаем Idempotency-Key
            $idempotencyKey = $this->getIdempotencyKey();

            // Шаг 2: Проверяем наличие
            if (empty($idempotencyKey)) {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key header is required');
                return;
            }

            // Шаг 3: Проверяем формат UUID
            if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idempotencyKey)) {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key must be a valid UUID');
            }
        });
    }


    public function getIdempotencyKey()
    {
        return $this->header('Idempotency-Key') ?? $this->input('idempotency_key');
    }
}

