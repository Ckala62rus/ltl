<?php

namespace App\Services;

use App\Contracts\HoldRepositoryInterface;
use App\Contracts\SlotRepositoryInterface;
use App\Contracts\SlotServiceInterface;
use App\Models\Hold;
use App\Models\Slot;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlotService implements SlotServiceInterface
{
    private const CACHE_KEY_AVAILABILITY = 'slots:availability';
    private const CACHE_TTL_SECONDS = 10;

    /**
     * @param SlotRepositoryInterface $slotRepository
     * @param HoldRepositoryInterface $holdRepository
     */
    public function __construct(
        private SlotRepositoryInterface $slotRepository,
        private HoldRepositoryInterface $holdRepository
    ) {
    }

    /**
     * Получение доступных слотов с кешированием
     * @return Collection
     */
    public function getAvailableSlots(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_AVAILABILITY,
            self::CACHE_TTL_SECONDS,
            function () {
                $query = $this->slotRepository->getQuery();
                
                return $query->get()->map(function (Slot $slot) {
                    return [
                        'slot_id' => $slot->id,
                        'capacity' => $slot->capacity,
                        'remaining' => $slot->remaining,
                    ];
                })->values();
            }
        );
    }

    /**
     * Создание временного холда на слот с поддержкой идемпотентности
     * @param int $slotId ID слота для бронирования
     * @param string $idempotencyKey Уникальный ключ идемпотентности (UUID)
     * @return array Результат операции с данными холда или ошибкой
     * @throws Exception Если слот не найден или произошла ошибка при создании
     */
    public function createHold(int $slotId, string $idempotencyKey): array
    {
        // Шаг 1: Проверка идемпотентности
        $holdQuery = $this->holdRepository->getQuery();
        $existingHold = $this->holdRepository->getHoldByIdempotencyKey($holdQuery, $idempotencyKey);
        
        // Шаг 2: Если холд уже существует, возвращаем его
        if ($existingHold) {
            return [
                'success' => true,
                'idempotent' => true,
                'hold_id' => $existingHold->id,
                'slot_id' => $existingHold->slot_id,
                'status' => $existingHold->status,
                'message' => 'Hold already exists',
            ];
        }

        // Шаг 3: Получаем слот с блокировкой FOR UPDATE в транзакции
        // Это предотвращает race condition - два клиента не смогут одновременно создать холд
        $slot = DB::transaction(function () use ($slotId) {
            $slotQuery = $this->slotRepository->getQuery();
            return $this->slotRepository->getSlotByIdWithLock($slotQuery, $slotId);
        });

        // Шаг 4: Проверяем существование слота
        if (!$slot) {
            throw new Exception("Slot with id:{$slotId} not found", 404);
        }

        // Шаг 5: Проверяем доступность мест
        if ($slot->remaining <= 0) {
            return [
                'success' => false,
                'message' => 'No available capacity',
                'status_code' => 409,
            ];
        }

        // Шаг 6: Создаем новый холд в новой транзакции
        DB::beginTransaction();
        try {
            $holdQuery = $this->holdRepository->getQuery();
            $hold = $this->holdRepository->createHold($holdQuery, [
                'slot_id' => $slotId,
                'status' => Hold::STATUS_HELD, // Временный холд
                'idempotency_key' => $idempotencyKey,
                'expires_at' => Carbon::now()->addMinutes(5), // Живет 5 минут
            ]);

            DB::commit();

            // Шаг 7: Инвалидируем кеш доступности слотов
            $this->invalidateCache();

            // Шаг 8: Возвращаем успешный результат
            return [
                'success' => true,
                'idempotent' => false,
                'hold_id' => $hold->id,
                'slot_id' => $hold->slot_id,
                'status' => $hold->status,
                'expires_at' => $hold->expires_at->toDateTimeString(),
                'message' => 'Hold created successfully',
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create hold', [
                'slot_id' => $slotId,
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Подтверждение холда с атомарным уменьшением количества мест
     * @param int $holdId ID холда для подтверждения
     * @return array Результат операции с данными или ошибкой
     * @throws Exception Если холд не найден или произошла ошибка
     */
    public function confirmHold(int $holdId): array
    {
        // Шаг 1: Получаем холд
        $holdQuery = $this->holdRepository->getQuery();
        $hold = $this->holdRepository->getHoldById($holdQuery, $holdId);

        // Шаг 2: Проверяем существование
        if (!$hold) {
            throw new Exception("Hold with id:{$holdId} not found", 404);
        }

        // Шаг 3: Проверяем статус - уже подтвержден
        if ($hold->status === Hold::STATUS_CONFIRMED) {
            return [
                'success' => true,
                'message' => 'Hold already confirmed',
                'hold_id' => $hold->id,
                'slot_id' => $hold->slot_id,
            ];
        }

        // Шаг 4: Проверяем статус - отменен
        if ($hold->status === Hold::STATUS_CANCELLED) {
            return [
                'success' => false,
                'message' => 'Hold already cancelled',
                'status_code' => 409,
            ];
        }

        // Шаг 5: Проверяем, что холд не истек
        if ($hold->isExpired()) {
            return [
                'success' => false,
                'message' => 'Hold expired',
                'status_code' => 410,
            ];
        }

        // Шаг 6: Атомарное уменьшение remaining в транзакции
        DB::beginTransaction();
        try {
            // Шаг 7: Атомарно уменьшаем remaining с проверкой remaining > 0
            $slotQuery = $this->slotRepository->getQuery();
            $decremented = $this->slotRepository->decrementRemaining($slotQuery, $hold->slot_id);

            // Шаг 8: Проверяем, что уменьшение прошло успешно
            if (!$decremented) {
                DB::rollBack();
                
                // Шаг 9: Получаем слот для диагностики
                $slotQuery = $this->slotRepository->getQuery();
                $slot = $this->slotRepository->getSlotById($slotQuery, $hold->slot_id);
                
                // Если действительно нет мест, возвращаем ошибку оверсела
                if ($slot && $slot->remaining <= 0) {
                    return [
                        'success' => false,
                        'message' => 'No available capacity (oversell protection)',
                        'status_code' => 409,
                    ];
                }

                // Иначе неизвестная ошибка
                return [
                    'success' => false,
                    'message' => 'Failed to confirm hold',
                    'status_code' => 409,
                ];
            }

            // Шаг 10: Обновляем статус холда на confirmed
            $holdQuery = $this->holdRepository->getQuery();
            $this->holdRepository->updateHold($holdQuery, $holdId, [
                'status' => Hold::STATUS_CONFIRMED,
            ]);

            DB::commit();

            // Шаг 11: Инвалидируем кеш
            $this->invalidateCache();

            // Шаг 12: Возвращаем успех
            return [
                'success' => true,
                'message' => 'Hold confirmed successfully',
                'hold_id' => $hold->id,
                'slot_id' => $hold->slot_id,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to confirm hold', [
                'hold_id' => $holdId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Отмена холда с возвратом места в слот (если холд был подтвержден)
     * @param int $holdId ID холда для отмены
     * @return array Результат операции с данными или ошибкой
     * @throws Exception Если холд не найден или произошла ошибка
     */
    public function cancelHold(int $holdId): array
    {
        // Шаг 1: Получаем холд
        $holdQuery = $this->holdRepository->getQuery();
        $hold = $this->holdRepository->getHoldById($holdQuery, $holdId);

        // Шаг 2: Проверяем существование
        if (!$hold) {
            throw new Exception("Hold with id:{$holdId} not found", 404);
        }

        // Шаг 3: Проверяем, что холд не был уже отменен
        if ($hold->status === Hold::STATUS_CANCELLED) {
            return [
                'success' => true,
                'message' => 'Hold already cancelled',
                'hold_id' => $hold->id,
            ];
        }

        // Шаг 4: Если холд был подтвержден, возвращаем место
        if ($hold->status === Hold::STATUS_CONFIRMED) {
            DB::beginTransaction();
            try {
                // Увеличиваем remaining на 1
                $slotQuery = $this->slotRepository->getQuery();
                $this->slotRepository->incrementRemaining($slotQuery, $hold->slot_id);
                
                // Обновляем статус
                $holdQuery = $this->holdRepository->getQuery();
                $this->holdRepository->updateHold($holdQuery, $holdId, [
                    'status' => Hold::STATUS_CANCELLED,
                ]);

                DB::commit();

                // Инвалидируем кеш
                $this->invalidateCache();

                return [
                    'success' => true,
                    'message' => 'Confirmed hold cancelled, slot returned',
                    'hold_id' => $hold->id,
                ];
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Failed to cancel confirmed hold', [
                    'hold_id' => $holdId,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        // Шаг 5: Если холд был только held, просто отменяем
        DB::beginTransaction();
        try {
            $holdQuery = $this->holdRepository->getQuery();
            $this->holdRepository->updateHold($holdQuery, $holdId, [
                'status' => Hold::STATUS_CANCELLED,
            ]);

            DB::commit();

            $this->invalidateCache();

            return [
                'success' => true,
                'message' => 'Hold cancelled successfully',
                'hold_id' => $hold->id,
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel hold', [
                'hold_id' => $holdId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Инвалидация кеша доступных слотов
     * @return void
     */
    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY_AVAILABILITY);
    }
}
