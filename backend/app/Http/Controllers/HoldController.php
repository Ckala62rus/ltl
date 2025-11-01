<?php

namespace App\Http\Controllers;

use App\Contracts\SlotServiceInterface;
use App\Http\Requests\Slot\HoldConfirmRequest;
use App\Http\Requests\Slot\HoldStoreRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class HoldController extends BaseController
{

    public function __construct(
        private SlotServiceInterface $slotService
    ) {}

    /**
     * Создание холда для слота
     * @param int $id ID слота из URL route
     * @param HoldStoreRequest $request Валидированный запрос с Idempotency-Key
     * @return JsonResponse
     */
    public function createHold(int $id, HoldStoreRequest $request): JsonResponse
    {
        try {
            // Шаг 1: Получаем Idempotency-Key из заголовка
            $idempotencyKey = $request->getIdempotencyKey();

            // Шаг 2: Проверяем наличие ключа (дополнительная проверка)
            if (!$idempotencyKey) {
                return $this->response(
                    ['hold' => null],
                    'Idempotency-Key header is required',
                    false,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Шаг 3: Вызываем сервис для создания холда
            $result = $this->slotService->createHold($id, $idempotencyKey);

            // Шаг 4: Проверяем результат
            if (!$result['success']) {
                // Возвращаем ошибку с соответствующим статус-кодом
                return $this->response(
                    ['hold' => null],
                    $result['message'],
                    false,
                    $result['status_code'] ?? Response::HTTP_CONFLICT
                );
            }

            // Шаг 5: Возвращаем успешный ответ
            return $this->response(
                ['hold' => $result],
                'Hold created successfully',
                true,
                Response::HTTP_CREATED
            );
        } catch (\Exception $exception) {
            // Шаг 6: Обрабатываем исключения
            return $this->response(
                ['hold' => null],
                $exception->getMessage(),
                false,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Подтверждение холда
     * @param int $id ID холда из URL route
     * @param HoldConfirmRequest $request Валидированный запрос
     * @return JsonResponse
     */
    public function confirm(int $id, HoldConfirmRequest $request): JsonResponse
    {
        try {
            // Шаг 1: Вызываем сервис для подтверждения
            $result = $this->slotService->confirmHold($id);

            // Шаг 2: Проверяем результат
            if (!$result['success']) {
                return $this->response(
                    ['hold' => null],
                    $result['message'],
                    false,
                    $result['status_code'] ?? Response::HTTP_CONFLICT
                );
            }

            // Шаг 3: Возвращаем успех
            return $this->response(
                ['hold' => $result],
                'Hold confirmed successfully',
                true,
                Response::HTTP_OK
            );
        } catch (\Exception $exception) {
            // Шаг 4: Обрабатываем исключения с правильным кодом
            $statusCode = $exception->getCode() >= 400 && $exception->getCode() < 600
                ? $exception->getCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return $this->response(
                ['hold' => null],
                $exception->getMessage(),
                false,
                $statusCode
            );
        }
    }

    /**
     * Отмена холда
     * @param int $id ID холда из URL route
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            // Шаг 1: Вызываем сервис для отмены
            $result = $this->slotService->cancelHold($id);

            // Шаг 2: Возвращаем успех (всегда успешно)
            return $this->response(
                ['hold' => $result],
                'Hold cancelled successfully',
                true,
                Response::HTTP_OK
            );
        } catch (\Exception $exception) {
            // Шаг 3: Обрабатываем исключения
            $statusCode = $exception->getCode() >= 400 && $exception->getCode() < 600
                ? $exception->getCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return $this->response(
                ['hold' => null],
                $exception->getMessage(),
                false,
                $statusCode
            );
        }
    }
}
