<?php

namespace App\Http\Controllers;

use App\Contracts\SlotServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


class AvailabilityController extends BaseController
{

    public function __construct(
        private SlotServiceInterface $slotService
    ) {}

    public function availability(): JsonResponse
    {
        try {
            // Вызываем сервис и получаем данные (с кешированием)
            $slots = $this->slotService->getAvailableSlots();

            // Возвращаем успешный ответ
            return $this->response(
                ['slots' => $slots],
                'Available slots retrieved successfully',
                true,
                200
            );
        } catch (\Exception $exception) {
            // Обрабатываем любые исключения и возвращаем ошибку
            return $this->response(
                ['slots' => []],
                $exception->getMessage(),
                false,
                500
            );
        }
    }
}
