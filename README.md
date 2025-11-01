# Slot Booking API

## Описание

API для бронирования слотов с защитой от оверсела.

**Как работает бронирование:**
1. **Холд (held)** - создается временный холд на 5 минут, место НЕ занято
2. **Подтверждение (confirmed)** - холд подтверждается, место ЗАНЯТО (remaining уменьшается)
3. **Отмена (cancelled)** - холд отменяется, если был confirmed - место возвращается

## Разворачивание проекта

1. Скачать проект

```bash
git clone <repository-url>
cd ltl
```

2. Выполнить команду docker compose build --no-cache

```bash
docker compose build --no-cache
```

3. Запустить проект docker compose up -d

```bash
docker compose up -d
```

4. Зайти в проект docker exec -it backend-dedov bash

```bash
docker exec -it backend-dedov bash
```

5. Выполнить composer install

```bash
composer install
```

6. Выполнить миграции - php artisan migrate

```bash
php artisan migrate
```

7. Выполнить сиды - php artisan db:seed --class=SlotSeeder

```bash
php artisan db:seed --class=SlotSeeder
```

После выполнения всех шагов API будет доступно по адресу `http://localhost:85/api/`

## API Endpoints

### 1. Получение доступных слотов

```bash
curl -X GET http://localhost:85/api/slots/availability
```

**Ответ:**
```json
{
  "status": true,
  "message": "Available slots retrieved successfully",
  "data": {
    "slots": [
      {
        "slot_id": 1,
        "capacity": 10,
        "remaining": 10
      },
      {
        "slot_id": 2,
        "capacity": 5,
        "remaining": 5
      }
    ]
  }
}
```

### 2. Создание холда

```bash
# С генерацией UUID (Linux/Mac)
curl -X POST http://localhost:85/api/slots/1/hold \
  -H "Idempotency-Key: $(uuidgen)" \
  -H "Content-Type: application/json"

# С генерацией UUID (Windows PowerShell)
$guid = [guid]::NewGuid().ToString()
Invoke-RestMethod -Method Post `
  -Uri "http://localhost:85/api/slots/1/hold" `
  -Headers @{"Idempotency-Key"=$guid; "Content-Type"="application/json"}

# С фиксированным UUID
curl -X POST http://localhost:85/api/slots/1/hold \
  -H "Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000" \
  -H "Content-Type: application/json"
```

**Ответ при успехе (201 Created):**
```json
{
  "status": true,
  "message": "Hold created successfully",
  "data": {
    "hold": {
      "success": true,
      "idempotent": false,
      "hold_id": 1,
      "slot_id": 1,
      "status": "held",
      "expires_at": "2024-12-01 12:05:00",
      "message": "Hold created successfully"
    }
  }
}
```

**Конфликт - нет мест (409 Conflict):**
```json
{
  "status": false,
  "message": "No available capacity",
  "data": {
    "hold": null
  }
}
```

### 3. Подтверждение холда

```bash
curl -X POST http://localhost:85/api/holds/1/confirm \
  -H "Content-Type: application/json"
```

**Ответ при успехе (200 OK):**
```json
{
  "status": true,
  "message": "Hold confirmed successfully",
  "data": {
    "hold": {
      "success": true,
      "message": "Hold confirmed successfully",
      "hold_id": 1,
      "slot_id": 1
    }
  }
}
```

**Конфликт - нет мест (409 Conflict):**
```json
{
  "status": false,
  "message": "No available capacity (oversell protection)",
  "data": {
    "hold": null
  }
}
```

**Истек срок (410 Gone):**
```json
{
  "status": false,
  "message": "Hold expired",
  "data": {
    "hold": null
  }
}
```

### 4. Отмена холда

```bash
curl -X DELETE http://localhost:85/api/holds/1 \
  -H "Content-Type: application/json"
```

**Ответ при успехе (200 OK):**
```json
{
  "status": true,
  "message": "Hold cancelled successfully",
  "data": {
    "hold": {
      "success": true,
      "message": "Hold cancelled successfully",
      "hold_id": 1
    }
  }
}
```

## Примеры использования

### Генерация UUID для Idempotency-Key

```bash
# Linux/Mac
uuidgen

# Windows PowerShell
[guid]::NewGuid().ToString()

# Node.js
node -e "console.log(require('crypto').randomUUID())"

# Python
python -c "import uuid; print(uuid.uuid4())"

# Online
# https://www.uuidgenerator.net/
```

### Комплексный сценарий бронирования

```bash
# Шаг 1: Проверяем доступность
curl -X GET http://localhost:85/api/slots/availability

# Шаг 2: Создаем холд для слота 1
UUID=$(uuidgen)  # или используйте фиксированный UUID
curl -X POST http://localhost:85/api/slots/1/hold \
  -H "Idempotency-Key: $UUID" \
  -H "Content-Type: application/json"

# Шаг 3: Подтверждаем холд
curl -X POST http://localhost:85/api/holds/1/confirm \
  -H "Content-Type: application/json"

# Шаг 4: Проверяем доступность (остаток уменьшился)
curl -X GET http://localhost:85/api/slots/availability

# Шаг 5: Отменяем холд
curl -X DELETE http://localhost:85/api/holds/1 \
  -H "Content-Type: application/json"

# Шаг 6: Проверяем доступность (остаток вернулся)
curl -X GET http://localhost:85/api/slots/availability
```

## Структура базы данных

### Таблица `slots`

| Поле      | Тип    | Описание                |
|-----------|--------|-------------------------|
| id        | INT    | Primary Key             |
| name      | STRING | Название слота          |
| capacity  | INT    | Вместимость слота       |
| remaining | INT    | Остаток свободных мест  |
| timestamps| TIMESTAMP | created_at, updated_at |

### Таблица `holds`

| Поле            | Тип       | Описание                        |
|-----------------|-----------|---------------------------------|
| id              | INT       | Primary Key                     |
| slot_id         | INT       | Foreign Key -> slots.id         |
| status          | STRING    | held/confirmed/cancelled        |
| idempotency_key | STRING    | Уникальный ключ идемпотентности |
| expires_at      | TIMESTAMP | Время истечения холда           |
| timestamps      | TIMESTAMP | created_at, updated_at          |

## Важные замечания

- **Создание холда (held) НЕ уменьшает remaining** - место будет занято только после подтверждения
- **Подтверждение холда (confirmed) УМЕНЬШАЕТ remaining** - это единственный момент, когда место становится занятым
- **Отмена confirmed холда ВОЗВРАЩАЕТ remaining** - место освобождается обратно
- **Idempotency-Key обязателен** для создания холда и должен быть валидным UUID
- **Холд истекает через 5 минут** - после истечения его нельзя подтвердить
