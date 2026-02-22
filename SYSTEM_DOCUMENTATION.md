# 📋 Documentación del Sistema — Backend API

> **Última actualización:** 2026-02-21  
> **Framework:** Laravel 11 + Filament Admin  
> **Autenticación API:** Laravel Sanctum  
> **Real-time:** Pusher (Broadcasting)

---

## 📁 Estructura General del Proyecto

```
backend-api/
├── app/
│   ├── Events/
│   │   └── MessageSent.php          # Evento de broadcasting para mensajes en tiempo real
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── UserResource.php     # CRUD de usuarios en Filament
│   │   │   ├── UserResource/Pages/  # Páginas del recurso User
│   │   │   ├── CoinTransactionResource.php  # [NUEVO] Recurso de transacciones de monedas
│   │   │   └── CoinTransactionResource/Pages/
│   │   ├── Pages/
│   │   │   └── MetricsDashboard.php # [NUEVO] Dashboard de métricas
│   │   └── Widgets/
│   │       ├── TopModelsWidget.php   # [NUEVO] Widget de modelos top
│   │       ├── StatsOverviewWidget.php # [NUEVO] Widget de estadísticas
│   │       └── RecentTransactionsWidget.php # [NUEVO] Widget de transacciones recientes
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php       # Registro, Login, Logout, Perfil
│   │   │   ├── ChatController.php       # Chat (CRUD mensajes, desbloqueo contenido)
│   │   │   ├── ContentController.php     # [NUEVO] Contenido (Posts, Reels, Stories, Highlights)
│   │   │   ├── FastContentController.php # Contenido rápido (fotos/videos de pago)
│   │   │   ├── FeedController.php       # Feed (Mock)
│   │   │   ├── NotificationController.php # Notificaciones
│   │   │   ├── SearchController.php     # Búsqueda de modelos
│   │   │   └── WalletController.php     # Wallet (balance, compra, desbloqueo chat)
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php              # Usuario (cliente, modelo, admin)
│   │   ├── Wallet.php            # Billetera virtual
│   │   ├── Transaction.php       # Historial de transacciones
│   │   ├── ChatUnlock.php        # Registro de desbloqueos de chat
│   │   ├── Message.php           # Mensajes de chat
│   │   ├── FastContent.php       # Contenido multimedia de pago
│   │   ├── SystemProfit.php      # Ganancias del sistema
│   │   ├── CoinTransaction.php   # [NUEVO] Registro detallado de gasto de monedas
│   │   ├── Post.php              # [NUEVO] Publicaciones y Reels
│   │   ├── Story.php             # [NUEVO] Historias (expiran en 24h)
│   │   └── Highlight.php         # [NUEVO] Historias destacadas
│   ├── Notifications/
│   │   └── NewMessageNotification.php # Push notification de mensajes
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── BroadcastServiceProvider.php
│       └── Filament/
│           └── AdminPanelProvider.php # Configuración panel Filament
├── database/
│   └── migrations/               # 21+ migraciones
├── routes/
│   ├── api.php                   # Rutas API (Sanctum)
│   ├── channels.php              # Broadcasting channels
│   └── web.php                   # Rutas web (Filament)
└── config/
```

---

## 🗄️ Base de Datos — Esquema Actual

### Tabla `users`

| Columna             | Tipo      | Descripción                           |
| ------------------- | --------- | ------------------------------------- |
| `id`                | bigint PK | ID autoincremental                    |
| `name`              | string    | Nombre del usuario                    |
| `email`             | string    | Email único                           |
| `email_verified_at` | timestamp | Verificación de email                 |
| `password`          | string    | Contraseña hasheada                   |
| `role`              | string    | `'cliente'`, `'modelo'`, `'admin'`    |
| `avatar`            | string    | URL de avatar                         |
| `bio`               | text      | Biografía                             |
| `bg_image`          | string    | Imagen de fondo del perfil            |
| `followers_count`   | integer   | Contador de seguidores                |
| `posts_count`       | integer   | Contador de publicaciones             |
| `rating`            | float     | Calificación                          |
| `rate_message`      | integer   | Costo por mensaje (default 1)         |
| `chat_price`        | integer   | Costo para desbloquear chat (modelos) |
| `is_online`         | boolean   | Estado en línea                       |
| `created_at`        | timestamp | Fecha de creación                     |
| `updated_at`        | timestamp | Fecha de actualización                |

**Roles disponibles:**

- `cliente` → Usuario normal que consume contenido y gasta monedas
- `modelo` → Creadora de contenido que recibe monedas
- `admin` → Administrador con acceso al panel Filament

### Tabla `wallets`

| Columna        | Tipo      | Descripción                 |
| -------------- | --------- | --------------------------- |
| `id`           | bigint PK | ID                          |
| `user_id`      | FK→users  | Propietario de la billetera |
| `balance`      | integer   | Saldo actual en monedas     |
| `total_earned` | integer   | Total histórico ganado      |
| `created_at`   | timestamp |                             |
| `updated_at`   | timestamp |                             |

**Relación:** Un usuario tiene una wallet (1:1)

### Tabla `transactions`

| Columna           | Tipo       | Descripción                           |
| ----------------- | ---------- | ------------------------------------- |
| `id`              | bigint PK  | ID                                    |
| `wallet_id`       | FK→wallets | Wallet afectada                       |
| `type`            | string     | Tipo de transacción                   |
| `amount`          | integer    | Positivo=ingreso, Negativo=gasto      |
| `description`     | string     | Descripción del movimiento            |
| `related_user_id` | bigint     | Usuario relacionado (opcional)        |
| `reference_id`    | string     | ID de referencia externa (ej: Stripe) |
| `created_at`      | timestamp  |                                       |
| `updated_at`      | timestamp  |                                       |

**Tipos:** `deposit`, `withdrawal`, `purchase`, `message`, `call`, `tip`, `subscription`, `refund`, `chat_unlock`, `unlock_content`

### Tabla `messages`

| Columna           | Tipo             | Descripción                      |
| ----------------- | ---------------- | -------------------------------- |
| `id`              | bigint PK        | ID                               |
| `sender_id`       | FK→users         | Quien envía                      |
| `receiver_id`     | FK→users         | Quien recibe                     |
| `content`         | text             | Contenido del mensaje            |
| `read_at`         | timestamp        | Fecha de lectura                 |
| `fast_content_id` | FK→fast_contents | Contenido multimedia adjunto     |
| `is_paid`         | boolean          | Si el contenido fue desbloqueado |
| `created_at`      | timestamp        |                                  |
| `updated_at`      | timestamp        |                                  |

### Tabla `chat_unlocks`

| Columna      | Tipo      | Descripción                    |
| ------------ | --------- | ------------------------------ |
| `id`         | bigint PK | ID                             |
| `user_id`    | FK→users  | Cliente que desbloquea         |
| `model_id`   | FK→users  | Modelo cuyo chat se desbloquea |
| `amount`     | integer   | Monedas gastadas               |
| `created_at` | timestamp |                                |
| `updated_at` | timestamp |                                |

**Constraint:** `UNIQUE(user_id, model_id)` — un usuario solo desbloquea una vez

### Tabla `fast_contents`

| Columna       | Tipo      | Descripción           |
| ------------- | --------- | --------------------- |
| `id`          | bigint PK | ID                    |
| `user_id`     | FK→users  | Modelo propietaria    |
| `type`        | string    | `'image'` o `'video'` |
| `url`         | string    | URL del archivo       |
| `price`       | integer   | Precio en monedas     |
| `description` | text      | Descripción           |
| `created_at`  | timestamp |                       |
| `updated_at`  | timestamp |                       |

### Tabla `system_profits`

| Columna      | Tipo      | Descripción                   |
| ------------ | --------- | ----------------------------- |
| `id`         | bigint PK | ID                            |
| `user_id`    | FK→users  | Cliente que gastó             |
| `model_id`   | FK→users  | Modelo en la que gastó        |
| `amount`     | integer   | Cantidad de monedas           |
| `source`     | string    | Origen: `'chat_unlock'`, etc. |
| `created_at` | timestamp |                               |
| `updated_at` | timestamp |                               |

---

## 🔗 Modelos y Relaciones

### User

- `wallet()` → HasOne(Wallet) — Cada usuario tiene una billetera
- `transactions()` → HasManyThrough(Transaction, Wallet) — Historial de transacciones
- `isAdmin()` → Verifica si el rol es `'admin'`
- `isModel()` → Verifica si el rol es `'modelo'`
- `canAccessPanel()` → Solo `admin` y `usuarios` pueden acceder a Filament

### Wallet

- `user()` → BelongsTo(User)
- `transactions()` → HasMany(Transaction)
- `deposit($amount, $type, $description)` → Agrega saldo y crea transacción positiva
- `withdraw($amount, $type, $description)` → Resta saldo y crea transacción negativa (valida saldo)

### Transaction

- `wallet()` → BelongsTo(Wallet)
- `relatedUser()` → BelongsTo(User, 'related_user_id')
- Scopes: `income()` (amount > 0), `expense()` (amount < 0)

### ChatUnlock

- `user()` → BelongsTo(User, 'user_id') — El cliente
- `model()` → BelongsTo(User, 'model_id') — La modelo

### Message

- `sender()` → BelongsTo(User, 'sender_id')
- `receiver()` → BelongsTo(User, 'receiver_id')
- `fastContent()` → BelongsTo(FastContent)

### FastContent

- `user()` → BelongsTo(User) — La modelo propietaria

### SystemProfit

- `user()` → BelongsTo(User, 'user_id') — Quien gastó
- `model()` → BelongsTo(User, 'model_id') — En quien gastó

---

## 🛣️ Endpoints API

Todas las rutas API requieren autenticación Sanctum excepto las marcadas como públicas.

### Auth (Público)

| Método | Ruta            | Controlador             | Descripción            |
| ------ | --------------- | ----------------------- | ---------------------- |
| GET    | `/api/ping`     | Closure                 | Health check           |
| POST   | `/api/register` | AuthController@register | Registro nuevo usuario |
| POST   | `/api/login`    | AuthController@login    | Inicio de sesión       |

### Auth (Protegido)

| Método | Ruta                  | Controlador                  | Descripción        |
| ------ | --------------------- | ---------------------------- | ------------------ |
| POST   | `/api/logout`         | AuthController@logout        | Cerrar sesión      |
| GET    | `/api/user`           | AuthController@user          | Perfil del usuario |
| POST   | `/api/profile/update` | AuthController@updateProfile | Actualizar perfil  |

### Wallet

| Método | Ruta                      | Controlador                   | Descripción                 |
| ------ | ------------------------- | ----------------------------- | --------------------------- |
| GET    | `/api/wallet`             | WalletController@index        | Obtener balance actual      |
| GET    | `/api/wallet/history`     | WalletController@transactions | Historial de transacciones  |
| POST   | `/api/wallet/purchase`    | WalletController@purchase     | Comprar monedas (mock)      |
| POST   | `/api/wallet/unlock-chat` | WalletController@unlockChat   | Desbloquear chat con modelo |

### Chat

| Método | Ruta                        | Controlador                  | Descripción                        |
| ------ | --------------------------- | ---------------------------- | ---------------------------------- |
| GET    | `/api/chat`                 | ChatController@index         | Lista de conversaciones            |
| GET    | `/api/chat/{userId}`        | ChatController@getMessages   | Mensajes de una conversación       |
| POST   | `/api/chat`                 | ChatController@sendMessage   | Enviar mensaje                     |
| DELETE | `/api/chat/{userId}`        | ChatController@destroy       | Eliminar conversación y rebloquear |
| POST   | `/api/messages/{id}/unlock` | ChatController@unlockMessage | Desbloquear contenido de pago      |

### Contenido Rápido

| Método | Ruta                     | Controlador                   | Descripción                   |
| ------ | ------------------------ | ----------------------------- | ----------------------------- |
| GET    | `/api/fast-content`      | FastContentController@index   | Listar contenidos del usuario |
| POST   | `/api/fast-content`      | FastContentController@store   | Subir contenido multimedia    |
| DELETE | `/api/fast-content/{id}` | FastContentController@destroy | Eliminar contenido            |

### Búsqueda

| Método | Ruta          | Controlador             | Descripción                               |
| ------ | ------------- | ----------------------- | ----------------------------------------- |
| GET    | `/api/models` | SearchController@models | Buscar modelos (con estado de desbloqueo) |

### Feed

| Método | Ruta        | Controlador          | Descripción       |
| ------ | ----------- | -------------------- | ----------------- |
| GET    | `/api/feed` | FeedController@index | Feed (datos mock) |

### Notificaciones

| Método | Ruta                               | Controlador                          | Descripción              |
| ------ | ---------------------------------- | ------------------------------------ | ------------------------ |
| GET    | `/api/notifications`               | NotificationController@index         | Listar notificaciones    |
| GET    | `/api/notifications/unread-count`  | NotificationController@unread_count  | Contar no leídas         |
| POST   | `/api/notifications/{id}/read`     | NotificationController@markAsRead    | Marcar como leída        |
| POST   | `/api/notifications/mark-all-read` | NotificationController@markAllAsRead | Marcar todas como leídas |

### Otros

| Método | Ruta                     | Descripción                              |
| ------ | ------------------------ | ---------------------------------------- |
| GET    | `/api/users/{id}`        | Obtener datos básicos de un usuario      |
| POST   | `/api/broadcasting/auth` | Autenticación para Pusher (broadcasting) |

---

## 💰 Flujo de Gasto de Monedas (Sistema Actual)

### 1. Desbloqueo de Chat (`POST /api/wallet/unlock-chat`)

1. Cliente envía `model_id`
2. Se verifica que el target sea un modelo
3. Se verifica que no esté ya desbloqueado (`chat_unlocks`)
4. Se obtiene el `chat_price` del modelo
5. Se cobra al cliente: `wallet->withdraw(price, 'chat_unlock')`
6. Se registra ganancia del sistema: `SystemProfit::create()`
7. Se crea registro de desbloqueo: `ChatUnlock::create()`

### 2. Desbloqueo de Contenido (`POST /api/messages/{id}/unlock`)

1. Usuario solicita desbloquear un mensaje con `fast_content_id`
2. Se verifica que el contenido existe y no está pagado
3. Se cobra el precio del contenido: `wallet->withdraw(price, 'unlock_content')`
4. Se marca el mensaje como `is_paid = true`

### 3. Compra de Monedas (`POST /api/wallet/purchase`)

1. Simulación (Mock) de compra
2. Se acredita al wallet: `wallet->deposit(amount, 'purchase')`

---

## 📡 Sistema de Broadcasting (Real-time)

- **Evento:** `MessageSent` → Se emite al canal privado `chat.{receiver_id}`
- **Notificación:** `NewMessageNotification` → Se guarda en BD y emite por broadcast
- **Canal privado:** `App.Models.User.{id}` — autenticado vía Sanctum

---

## 🛡️ Panel de Administración (Filament)

- **URL:** `/admin`
- **Autenticación:** Solo usuarios con rol `admin` o `usuarios` (`canAccessPanel()`)
- **Recursos existentes:**
    - `UserResource` → CRUD completo de usuarios con filtros por rol

---

## ✅ Migraciones (Orden cronológico)

1. `create_users_table` — Usuarios, password_reset_tokens, sessions
2. `create_cache_table` — Cache de Laravel
3. `create_jobs_table` — Queue jobs
4. `create_wallets_table` — Billeteras
5. `create_transactions_table` — Transacciones (originalmente enum, luego string)
6. `create_personal_access_tokens_table` — Tokens de Sanctum
7. `create_messages_table` — Mensajes de chat
8. `create_notifications_table` — Notificaciones de Laravel
9. `create_chat_unlocks_table` — Desbloqueos de chat
10. `add_chat_price_to_users_table` — Columna chat_price
11. `set_default_chat_price_for_existing_models` — Migración de datos
12. `change_transactions_type_to_string` — Cambio de enum a string en transactions
13. `create_system_profits_table` — Ganancias del sistema
14. `create_fast_contents_table` — Contenido rápido
15. `add_fast_content_columns_to_messages_table` — fast_content_id e is_paid en messages

---

---

# 🆕 NUEVAS FUNCIONALIDADES AGREGADAS

## 1. 📊 Tabla `coin_transactions` — Registro de Gasto de Monedas

### Descripción

Cada vez que un usuario gasta monedas (desbloqueo de chat o desbloqueo de contenido), se crea automáticamente un registro en la tabla `coin_transactions` que almacena:

- **Quién gastó** (`user_id`) — El cliente que realiza el gasto
- **En quién gastó** (`model_id`) — La modelo en la que se gastaron las monedas
- **Cuántas monedas** (`amount`) — Cantidad de monedas gastadas
- **Tipo de gasto** (`type`) — `'chat_unlock'` o `'content_unlock'`
- **Referencia** (`reference_id`) — ID del ChatUnlock o del Message desbloqueado

### Migración

```
database/migrations/2026_02_21_173000_create_coin_transactions_table.php
```

### Modelo

```
app/Models/CoinTransaction.php
```

**Relaciones:**

- `user()` → BelongsTo(User) — El cliente que gastó
- `modelUser()` → BelongsTo(User, 'model_id') — La modelo beneficiaria

**Scopes:**

- `chatUnlocks()` → Solo desbloqueos de chat
- `contentUnlocks()` → Solo desbloqueos de contenido

### ¿Dónde se registra?

- En `WalletController@unlockChat` — Al desbloquear un chat
- En `ChatController@unlockMessage` — Al desbloquear contenido de pago

---

## 2. 📈 Módulo de Métricas en Filament (Solo Admins)

### Página de Métricas

**URL:** `/admin/metrics`  
**Archivo:** `app/Filament/Pages/MetricsDashboard.php`

Solo accesible por usuarios con rol `admin`. Muestra:

### Widgets incluidos:

#### a) `StatsOverviewWidget`

**Archivo:** `app/Filament/Widgets/StatsOverviewWidget.php`

Tarjetas con estadísticas clave:

- 📊 Total de usuarios
- 👩 Total de modelos
- 💰 Total de monedas gastadas
- 🔓 Total de chats desbloqueados

#### b) `TopModelsWidget`

**Archivo:** `app/Filament/Widgets/TopModelsWidget.php`

Tabla que muestra las **modelos que más monedas les han gastado**, ordenadas por total de monedas recibidas. Columnas:

- Nombre de la modelo
- Email
- Total de monedas gastadas en ella
- Cantidad de desbloqueos de chat
- Cantidad de contenidos desbloqueados

#### c) `RecentTransactionsWidget`

**Archivo:** `app/Filament/Widgets/RecentTransactionsWidget.php`

Tabla de las últimas 10 transacciones de monedas con:

- Usuario que gastó
- Modelo beneficiaria
- Tipo de transacción
- Cantidad
- Fecha

---

## 3. 👥 Recurso de Usuarios Mejorado con Filtros por Rol

### Recurso `UserResource` — Actualizado

**Archivo:** `app/Filament/Resources/UserResource.php`

Se agregaron **3 tabs/filtros** en la tabla de usuarios:

1. **Todos** — Muestra todos los usuarios
2. **Clientes** — Solo usuarios con rol `'cliente'`
3. **Modelos** — Solo usuarios con rol `'modelo'`

Columnas mejoradas:

- Avatar (imagen circular)
- Nombre (buscable)
- Email (buscable)
- Rol (con badge de color)
- Balance de wallet
- Fecha de registro

---

## 4. 📋 Recurso de Transacciones de Monedas en Filament

### `CoinTransactionResource`

**Archivo:** `app/Filament/Resources/CoinTransactionResource.php`

Tabla completa de todas las transacciones de monedas con:

- Filtros por tipo (chat_unlock, content_unlock)
- Búsqueda por nombre de usuario y modelo
- Ordenamiento por fecha y cantidad
- Solo lectura (sin crear/editar)
- Accesible solo por admins

---

## 5. 🔐 Restricción de Acceso Filament

Se actualizó `User::canAccessPanel()` para que **solo admins** puedan acceder al panel:

```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->role === 'admin';
}
```

---

## 6. 🎫 Sistema de Tickets de Soporte

### Descripción General

Sistema completo de soporte al usuario mediante tickets. Los usuarios (clientes y modelos) crean tickets desde la **app móvil** a través de la API, y los administradores los gestionan desde el **panel de Filament**. Funciona como un chat de soporte donde el usuario describe su problema, y el admin puede responder, cambiar el estado, asignar prioridad y cerrar el ticket.

> **IMPORTANTE:** Este sistema NO usa ningún plugin externo. Fue construido desde cero porque el plugin `sgcomptech/filament-ticketing` solo soporta Filament v2 y nuestro proyecto usa Filament v3. Todo el código está alojado internamente en el proyecto.

### Arquitectura del Sistema

```
┌──────────────────────┐         ┌──────────────────────────────┐
│   APP MÓVIL (Front)  │         │   PANEL FILAMENT (Admin)     │
│                      │         │                              │
│  - Crear ticket      │  API    │  - Ver todos los tickets     │
│  - Ver mis tickets   │◄──────► │  - Responder como soporte    │
│  - Enviar mensaje    │  REST   │  - Cambiar estado/prioridad  │
│  - Cerrar ticket     │         │  - Asignar admin al ticket   │
│                      │         │  - Filtrar por estado         │
└──────────────────────┘         └──────────────────────────────┘
```

### Base de Datos

#### Tabla `support_tickets`

| Columna       | Tipo      | Descripción                                                       |
| ------------- | --------- | ----------------------------------------------------------------- |
| `id`          | bigint PK | ID autoincremental                                                |
| `user_id`     | FK→users  | El usuario (cliente o modelo) que abrió el ticket                 |
| `subject`     | string    | Asunto/título del ticket                                          |
| `description` | text      | Descripción completa del problema                                 |
| `category`    | string    | Categoría: `general`, `billing`, `technical`, `account`, `report` |
| `priority`    | string    | Prioridad: `low`, `normal`, `high`, `critical`                    |
| `status`      | string    | Estado: `open`, `in_progress`, `resolved`, `closed`               |
| `assigned_to` | FK→users  | ID del admin asignado a resolver el ticket (nullable)             |
| `resolved_at` | timestamp | Fecha en que se marcó como resuelto                               |
| `closed_at`   | timestamp | Fecha en que se cerró definitivamente                             |
| `created_at`  | timestamp | Fecha de creación                                                 |
| `updated_at`  | timestamp | Última actividad (se actualiza con cada mensaje)                  |

**Valores de `category`:**

- `general` → General (consulta común)
- `billing` → Facturación / Monedas (problemas con pagos, saldo, monedas)
- `technical` → Problema Técnico (bugs, errores, app no funciona)
- `account` → Mi Cuenta (problemas de perfil, contraseña, etc)
- `report` → Reportar Usuario (denunciar a otro usuario o modelo)

**Valores de `status`:**

- `open` → Abierto (recién creado, esperando atención)
- `in_progress` → En Progreso (un admin ya respondió y lo está atendiendo)
- `resolved` → Resuelto (el admin considera que ya se resolvió)
- `closed` → Cerrado (cerrado definitivamente, no admite más mensajes)

**Valores de `priority`:**

- `low` → Baja
- `normal` → Normal (valor por defecto)
- `high` → Alta
- `critical` → Crítica (máxima urgencia)

#### Tabla `ticket_messages`

| Columna          | Tipo               | Descripción                                                 |
| ---------------- | ------------------ | ----------------------------------------------------------- |
| `id`             | bigint PK          | ID autoincremental                                          |
| `ticket_id`      | FK→support_tickets | El ticket al que pertenece este mensaje                     |
| `user_id`        | FK→users           | Quien escribió el mensaje (usuario O admin)                 |
| `message`        | text               | Contenido del mensaje                                       |
| `is_admin_reply` | boolean            | `true` = el mensaje lo escribió un admin, `false` = usuario |
| `attachment_url` | string (nullable)  | URL de adjunto opcional (captura de pantalla, etc)          |
| `read_at`        | timestamp          | Fecha en que se leyó el mensaje (nullable)                  |
| `created_at`     | timestamp          | Fecha de creación del mensaje                               |
| `updated_at`     | timestamp          |                                                             |

### Modelos

#### `SupportTicket` (`app/Models/SupportTicket.php`)

**Relaciones:**

- `user()` → BelongsTo(User) — El usuario que creó el ticket
- `assignedAdmin()` → BelongsTo(User, 'assigned_to') — El admin de soporte asignado
- `messages()` → HasMany(TicketMessage) — Todos los mensajes del ticket (ordenados cronológicamente)

**Scopes:**

- `open()` → Solo tickets abiertos
- `inProgress()` → Solo tickets en progreso
- `resolved()` → Solo tickets resueltos
- `closed()` → Solo tickets cerrados

**Helpers:**

- `isOpen()` → Retorna `true` si el ticket está abierto
- `isClosed()` → Retorna `true` si el ticket está cerrado
- `markAsResolved()` → Cambia estado a `resolved` y registra `resolved_at`
- `markAsClosed()` → Cambia estado a `closed` y registra `closed_at`

**Constantes estáticas (para usar en formularios):**

- `SupportTicket::statuses()` → Array de estados con sus labels en español
- `SupportTicket::priorities()` → Array de prioridades con sus labels en español
- `SupportTicket::categories()` → Array de categorías con sus labels en español

#### `TicketMessage` (`app/Models/TicketMessage.php`)

**Relaciones:**

- `ticket()` → BelongsTo(SupportTicket) — El ticket al que pertenece
- `user()` → BelongsTo(User) — Quien escribió el mensaje

### Endpoints API (Para la App Móvil)

Todas las rutas requieren autenticación con Sanctum (`auth:sanctum`).

| Método | Ruta                              | Controlador                        | Descripción                                                |
| ------ | --------------------------------- | ---------------------------------- | ---------------------------------------------------------- |
| GET    | `/api/support/categories`         | SupportTicketController@categories | Obtener lista de categorías disponibles para el formulario |
| GET    | `/api/support/tickets`            | SupportTicketController@index      | Listar MIS tickets (paginado, ordenado por más reciente)   |
| POST   | `/api/support/tickets`            | SupportTicketController@store      | Crear un nuevo ticket de soporte                           |
| GET    | `/api/support/tickets/{id}`       | SupportTicketController@show       | Ver un ticket con todos sus mensajes                       |
| POST   | `/api/support/tickets/{id}/reply` | SupportTicketController@reply      | Enviar un mensaje dentro de un ticket existente            |
| POST   | `/api/support/tickets/{id}/close` | SupportTicketController@close      | El usuario cierra su propio ticket                         |

#### Detalle de cada endpoint:

**`POST /api/support/tickets` — Crear ticket:**

```json
{
    "subject": "No puedo ver mi saldo de monedas",
    "description": "Desde ayer mi saldo aparece en 0 pero yo compré 500 monedas...",
    "category": "billing", // opcional, default: "general"
    "priority": "high" // opcional, default: "normal"
}
```

Respuesta: Crea el ticket + un primer mensaje automático con la descripción.

**`POST /api/support/tickets/{id}/reply` — Responder en ticket:**

```json
{
    "message": "Gracias, pero el problema sigue..."
}
```

Nota: Si el ticket estaba marcado como `resolved`, al responder el usuario se **reabre automáticamente** (cambia a `open`).

**`GET /api/support/tickets/{id}` — Ver ticket con mensajes:**
Retorna el ticket completo con todos sus mensajes, info del usuario y del admin asignado. Marca como leídos los mensajes del admin.

### Panel de Filament (Para el Admin)

#### Recurso: `SupportTicketResource`

**Archivo:** `app/Filament/Resources/SupportTicketResource.php`  
**URL:** `/admin/support-tickets`  
**Grupo de navegación:** "Soporte"  
**Badge en navegación:** Muestra el número de tickets abiertos + en progreso (rojo si hay abiertos)

**Listado de tickets (`/admin/support-tickets`):**

- Tabs de filtrado: **Abiertos** | **En Progreso** | **Resueltos** | **Cerrados** | **Todos**
- Cada tab muestra un badge con la cantidad de tickets en ese estado
- Columnas: #, Usuario, Rol del usuario, Asunto, Categoría, Prioridad, Estado, Nº mensajes, Admin asignado, Fecha creación, Última actividad
- Filtros adicionales por estado, prioridad y categoría
- Los badges de estado y prioridad usan colores semánticos (rojo=abierto/crítico, amarillo=en progreso/alta, verde=resuelto, gris=cerrado/baja)

**Vista detallada de ticket (`/admin/support-tickets/{id}`):**

- Información completa del ticket (usuario, email, rol, asunto, descripción)
- Estado actual con badges de colores
- **Sección de conversación:** Muestra TODOS los mensajes del ticket en orden cronológico, diferenciando visualmente los mensajes del usuario (gris) y las respuestas del admin (azul/primary)
- **Botón "Responder":** Abre un modal donde el admin escribe su respuesta. Al responder:
    - Si el ticket estaba `open`, automáticamente cambia a `in_progress`
    - Se asigna automáticamente el admin que respondió como `assigned_to`
- **Botón "Marcar Resuelto":** Cambia el estado a `resolved` (requiere confirmación)
- **Botón "Cerrar Ticket":** Cambia el estado a `closed` definitivamente (requiere confirmación)

**Edición de ticket (`/admin/support-tickets/{id}/edit`):**

- Permite cambiar: Estado, Prioridad, Admin asignado
- NO permite editar los datos del usuario ni la descripción original
- Tiene los mismos botones de acción rápida (Marcar Resuelto, Cerrar)
- Incluye el **RelationManager de Mensajes** donde el admin puede ver la conversación y responder directamente

#### RelationManager: `MessagesRelationManager`

**Archivo:** `app/Filament/Resources/SupportTicketResource/RelationManagers/MessagesRelationManager.php`

Tabla con todos los mensajes del ticket, mostrando:

- Nombre del remitente + indicador (🛡️ Soporte / 👤 Usuario)
- Contenido del mensaje
- Fecha/hora
- Botón "Responder como Soporte" en la cabecera de la tabla

### Flujo Completo del Sistema de Tickets

```
1. USUARIO (App Móvil)
   └─ POST /api/support/tickets
      ├─ Crea SupportTicket (status: "open")
      └─ Crea primer TicketMessage (con la descripción)

2. ADMIN (Panel Filament /admin/support-tickets)
   └─ Ve el ticket nuevo en la pestaña "Abiertos"
      └─ Click en "Ver" → Ve los detalles + la conversación
         └─ Click en "Responder"
            ├─ Crea TicketMessage (is_admin_reply: true)
            ├─ Cambia status a "in_progress"
            └─ Se asigna como admin del ticket

3. USUARIO (App Móvil)
   └─ GET /api/support/tickets/{id}
      └─ Ve la respuesta del admin marcada como "leída"
         └─ POST /api/support/tickets/{id}/reply
            └─ Envía mensaje de respuesta

4. ADMIN resuelve el problema
   └─ Click en "Marcar Resuelto"
      └─ Status cambia a "resolved", resolved_at = now()

5. USUARIO satisfecho
   └─ POST /api/support/tickets/{id}/close
      └─ Status cambia a "closed", closed_at = now()

   O si NO está satisfecho:
   └─ POST /api/support/tickets/{id}/reply
      └─ El ticket se REABRE automáticamente (status → "open")
```

### Archivos del Sistema de Tickets

| Archivo                                                                                     | Tipo             | Descripción                                 |
| ------------------------------------------------------------------------------------------- | ---------------- | ------------------------------------------- |
| `database/migrations/2026_02_21_202700_create_support_tickets_table.php`                    | Migración        | Tablas support_tickets + ticket_messages    |
| `app/Models/SupportTicket.php`                                                              | Modelo           | Modelo del ticket con constantes y helpers  |
| `app/Models/TicketMessage.php`                                                              | Modelo           | Modelo del mensaje de ticket                |
| `app/Http/Controllers/Api/SupportTicketController.php`                                      | Controlador API  | Endpoints para la app móvil                 |
| `app/Filament/Resources/SupportTicketResource.php`                                          | Recurso Filament | Configuración del recurso en el panel admin |
| `app/Filament/Resources/SupportTicketResource/Pages/ListSupportTickets.php`                 | Página           | Listado con tabs por estado                 |
| `app/Filament/Resources/SupportTicketResource/Pages/EditSupportTicket.php`                  | Página           | Edición con acciones rápidas                |
| `app/Filament/Resources/SupportTicketResource/Pages/ViewSupportTicket.php`                  | Página           | Vista con conversación y botón de respuesta |
| `app/Filament/Resources/SupportTicketResource/RelationManagers/MessagesRelationManager.php` | RelationManager  | Chat de respuestas del ticket               |

---

## 📸 Sistema de Contenido (Posts, Reels, Stories, Highlights)

> **Fecha de implementación:** 2026-02-21

### Concepto General

El sistema de contenido permite a los usuarios subir publicaciones (posts), reels (videos cortos), historias (contenido temporal de 24h) e historias destacadas (highlights permanentes). **TODO** el contenido tiene un flag `is_exclusive` que determina si es público (visible para todos) o exclusivo (de pago, solo visible para suscriptores o mediante desbloqueo con monedas).

### Tablas Nuevas

#### Tabla `posts`

| Columna          | Tipo      | Descripción                                         |
| ---------------- | --------- | --------------------------------------------------- |
| `id`             | bigint PK | ID autoincremental                                  |
| `user_id`        | FK→users  | Usuario que subió el contenido                      |
| `type`           | enum      | `'post'`, `'reel'`, `'live'` — Tipo de publicación  |
| `media_url`      | string    | URL del archivo subido (en `storage/posts/`)        |
| `media_type`     | string    | `'image'` o `'video'` — Detectado automáticamente   |
| `caption`        | text      | Descripción/caption de la publicación               |
| `is_exclusive`   | boolean   | `false` = pública, `true` = contenido exclusivo     |
| `coin_cost`      | integer   | Costo en monedas para desbloquear (si es exclusiva) |
| `likes_count`    | integer   | Contador de likes (default 0)                       |
| `comments_count` | integer   | Contador de comentarios (default 0)                 |
| `created_at`     | timestamp |                                                     |
| `updated_at`     | timestamp |                                                     |

**IMPORTANTE sobre `type`:**

- `post` → Publicación normal. Acepta imágenes Y videos.
- `reel` → Video corto. **SOLO acepta videos** (el backend valida esto y rechaza imágenes).
- `live` → Reservado para futuro uso (video en vivo). NO implementado aún.

#### Tabla `stories`

| Columna        | Tipo      | Descripción                                      |
| -------------- | --------- | ------------------------------------------------ |
| `id`           | bigint PK | ID autoincremental                               |
| `user_id`      | FK→users  | Usuario que subió la historia                    |
| `media_url`    | string    | URL del archivo subido (en `storage/stories/`)   |
| `media_type`   | enum      | `'image'` o `'video'`                            |
| `is_exclusive` | boolean   | `false` = pública, `true` = exclusiva            |
| `expires_at`   | timestamp | Fecha/hora de expiración (24h después de subida) |
| `created_at`   | timestamp |                                                  |
| `updated_at`   | timestamp |                                                  |

**IMPORTANTE:** Las historias expiran automáticamente 24 horas después de crearse. La query de `myStories` filtra por `expires_at > now()`.

#### Tabla `highlights`

| Columna        | Tipo      | Descripción                                            |
| -------------- | --------- | ------------------------------------------------------ |
| `id`           | bigint PK | ID autoincremental                                     |
| `user_id`      | FK→users  | Usuario dueño del highlight                            |
| `title`        | string    | Nombre de la historia destacada (máx 50 chars)         |
| `cover_url`    | string    | URL de la imagen de portada (en `storage/highlights/`) |
| `is_exclusive` | boolean   | `false` = pública, `true` = exclusiva                  |
| `created_at`   | timestamp |                                                        |
| `updated_at`   | timestamp |                                                        |

#### Tabla `highlight_story` (Pivot)

| Columna        | Tipo          | Descripción                       |
| -------------- | ------------- | --------------------------------- |
| `id`           | bigint PK     | ID autoincremental                |
| `highlight_id` | FK→highlights | Referencia al highlight           |
| `story_id`     | FK→stories    | Referencia a la historia guardada |
| `created_at`   | timestamp     |                                   |
| `updated_at`   | timestamp     |                                   |

**Relación Many-to-Many:** Una historia puede pertenecer a múltiples highlights, y un highlight puede contener múltiples historias.

### Modelos y Relaciones

#### Post

- `user()` → BelongsTo(User)
- Fillable: `user_id`, `type`, `media_url`, `media_type`, `caption`, `is_exclusive`, `coin_cost`, `likes_count`, `comments_count`

#### Story

- `user()` → BelongsTo(User)
- `highlights()` → BelongsToMany(Highlight) — Pivot `highlight_story`
- Cast: `expires_at` → datetime
- Fillable: `user_id`, `media_url`, `media_type`, `is_exclusive`, `expires_at`

#### Highlight

- `user()` → BelongsTo(User)
- `stories()` → BelongsToMany(Story) — Pivot `highlight_story`
- Fillable: `user_id`, `title`, `cover_url`, `is_exclusive`

#### User (relaciones nuevas)

- `posts()` → HasMany(Post)
- `stories()` → HasMany(Story)
- `highlights()` → HasMany(Highlight)

### Controlador: `ContentController.php`

**Archivo:** `app/Http/Controllers/Api/ContentController.php`

#### Endpoints

| Método | Ruta                               | Función          | Descripción                                     |
| ------ | ---------------------------------- | ---------------- | ----------------------------------------------- |
| POST   | `/api/content/post`                | `storePost`      | Crear publicación o reel (con archivo)          |
| POST   | `/api/content/story`               | `storeStory`     | Crear historia (con archivo, expira en 24h)     |
| POST   | `/api/content/highlight`           | `storeHighlight` | Crear historia destacada (con título y portada) |
| GET    | `/api/content/my-posts`            | `myPosts`        | Obtener mis posts/reels (filtrado por query)    |
| GET    | `/api/content/my-stories`          | `myStories`      | Obtener mis historias activas (no expiradas)    |
| GET    | `/api/content/my-highlights`       | `myHighlights`   | Obtener mis highlights con sus stories          |
| GET    | `/api/content/user/{userId}/posts` | `userPosts`      | Obtener posts de otro usuario                   |
| DELETE | `/api/content/post/{id}`           | `destroyPost`    | Eliminar post propio                            |
| DELETE | `/api/content/story/{id}`          | `destroyStory`   | Eliminar historia propia                        |

#### Query Parameters para `myPosts` y `userPosts`

- `type` → `post` o `reel` (default: `post`)
- `exclusive` → `0` o `1` (si se omite, trae todos)

**Ejemplos de uso:**

```
GET /api/content/my-posts?type=post&exclusive=0   → Mis publicaciones públicas
GET /api/content/my-posts?type=reel&exclusive=0   → Mis reels públicos
GET /api/content/my-posts?type=post&exclusive=1   → Mis publicaciones exclusivas
GET /api/content/my-posts?type=reel&exclusive=1   → Mis reels exclusivos
```

#### Validaciones del Backend

- **Reels SOLO aceptan video.** Si envías una imagen como reel, el backend responde con error 422: `"Los reels deben ser videos."`
- **Archivos permitidos:** jpeg, png, jpg, gif, mp4, mov, webm
- **Tamaño máximo de archivo:** 50MB (51200 KB)
- **Al crear un post:** Si `is_exclusive = true`, el `coin_cost` se establece automáticamente en 5 (o el valor enviado). Si `is_exclusive = false`, `coin_cost` siempre es 0.
- **Al crear un post/reel:** Se incrementa automáticamente `users.posts_count`. Al eliminar, se decrementa.
- **Al crear una historia:** Se calcula `expires_at = now() + 24 hours` automáticamente.

#### Almacenamiento de Archivos

- Los archivos se guardan en `storage/app/public/` bajo subcarpetas:
    - Posts/Reels → `storage/app/public/posts/`
    - Stories → `storage/app/public/stories/`
    - Highlights (portada) → `storage/app/public/highlights/`
- Se requiere `php artisan storage:link` para que sean accesibles públicamente.
- Las URLs se generan con `asset('storage/' . $path)`.

### Migraciones

| Archivo                                                    | Descripción                                           |
| ---------------------------------------------------------- | ----------------------------------------------------- |
| `2026_02_21_230504_create_posts_table.php`                 | Tabla posts con type, media, caption, exclusive, cost |
| `2026_02_21_230504_create_stories_table.php`               | Tabla stories con media, exclusive, expires_at        |
| `2026_02_21_230505_create_highlights_table.php`            | Tabla highlights con title, cover, exclusive          |
| `2026_02_21_230600_create_highlight_story_table.php`       | Tabla pivot highlight↔story (many-to-many)            |
| `2026_02_21_232529_add_is_exclusive_to_content_tables.php` | Agrega `is_exclusive` a posts, stories, highlights    |
