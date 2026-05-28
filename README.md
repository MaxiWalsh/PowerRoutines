# PowerRoutines

App móvil-first de gestión de entrenamiento para entrenadores y alumnos.
Los entrenadores crean rutinas y las asignan; los alumnos las siguen, registran sesiones y ven su progreso.

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 11 · PHP 8.3 · Sanctum · Spatie Permissions |
| Frontend | React 18 · React Router v6 · TanStack Query v5 · Tailwind CSS v3 · PWA |
| Base de datos | PostgreSQL (Supabase) |
| Imágenes | Cloudinary |
| Pagos | MercadoPago |
| IA | Groq (Llama 4 Scout Vision) — genera rutinas desde foto |
| Deploy | Render (backend) · Vercel (frontend) |

---

## Repos

| Repo | Carpeta local | URL |
|------|--------------|-----|
| Backend | `routine-app/` | https://github.com/MaxiWalsh/PowerRoutines |
| Frontend | `routine-app-front/` | https://github.com/MaxiWalsh/PowerRoutines-front |

---

## URLs de producción

| Servicio | URL |
|----------|-----|
| Frontend | https://power-routines-front.vercel.app |
| Backend API | https://powerroutines-api.onrender.com |
| Base de datos | Supabase proyecto `powerroutines` (ID: `zytymgrzlkqehusxnktd`) |

---

## Arquitectura

```
Browser / PWA
     │
     ▼
React SPA (Vercel)
     │  Bearer token (Sanctum)
     ▼
Laravel API (Render)
     ├── PostgreSQL (Supabase)
     ├── Cloudinary (avatares, logos, fotos ejercicios)
     ├── MercadoPago (pagos marketplace)
     └── Groq API (IA: foto → rutina JSON)
```

### Autenticación

Laravel Sanctum con tokens Bearer. El token se guarda en `localStorage` en el frontend y se envía en cada request como `Authorization: Bearer <token>`.

Roles (Spatie): `trainer` y `student`.

### Modelo de datos clave

```
User (trainer | student)
 ├── Gym (trainer crea, students se unen por código)
 ├── Profile (agrupa students con rutinas asignadas)
 └── Routine
      ├── Block (día: parent_id null)
      │    └── Block (sección: parent_id = día)
      │         └── block_exercise (pivot: sets, reps, rest_sec, order, notes)
      │              └── Exercise (catálogo global + personal)
      ├── RoutineAssignment (a student, gym o profile)
      └── RoutinePurchase (marketplace)

ExerciseLog (registra una serie ejecutada en sesión)
```

---

## Features

### Auth y usuarios
- Registro y login con roles `trainer` / `student`
- Avatar upload (Cloudinary)
- Campos de personalización: disciplina, objetivo, nivel, condiciones físicas
- Wizard de onboarding post-registro (3 pasos)

### Gimnasio
- El trainer crea el gym y obtiene un código de invitación
- Los students se unen con el código
- El trainer puede ver y gestionar sus alumnos

### Rutinas
- CRUD completo de rutinas
- Estructura: Rutina → Días → Bloques → Ejercicios (con sets/reps/rest/notas)
- Asignación a student individual, gym entero o profile
- **Generación desde foto**: `POST /api/routines/from-photo` envía una imagen al modelo Llama 4 Scout (Groq) que hace OCR y devuelve la rutina estructurada en JSON

### Sesiones de entrenamiento
- El student arranca una sesión para una rutina asignada
- Registra cada serie ejecutada (peso, reps reales, notas)
- Historial agrupado por rutina y sesión

### Progreso (premium)
- Stats de entrenamiento, racha y gráfico SVG de progreso por ejercicio
- Bloqueado por `PremiumGate` para plan free

### Marketplace
- Trainers publican rutinas (gratis o de pago)
- Students compran: gratis = compra directa, de pago = redirect a MercadoPago sandbox → webhook
- Rutinas recomendadas filtradas por disciplina + nivel + contraindicaciones

### Perfiles de entrenamiento
- El trainer crea profiles (ej: "Avanzados lunes/miércoles")
- Asigna students y rutinas al profile

---

## Rutas API principales

### Públicas
```
GET  /api/ping
POST /api/users/register
POST /api/users/login
POST /api/webhooks/mercadopago
```

### Protegidas (Bearer token requerido)

**Usuarios**
```
GET    /api/users/me
PUT    /api/users/me
POST   /api/users/me/avatar
POST   /api/users/me/upgrade / downgrade
POST   /api/users/logout
```

**Gimnasio**
```
POST   /api/gyms
GET    /api/gyms/{gym}
PUT    /api/gyms/{gym}
POST   /api/gyms/{gym}/logo
POST   /api/gyms/join
GET    /api/gyms/{gym}/students
DELETE /api/gyms/{gym}/leave
DELETE /api/gyms/{gym}/students/{studentId}
```

**Rutinas**
```
GET|POST                /api/routines
GET|PUT|DELETE          /api/routines/{routine}
POST                    /api/routines/from-photo
POST                    /api/routines/{routine}/assign/student/{studentId}
POST                    /api/routines/{routine}/assign/gym/{gymId}
POST                    /api/routines/{routine}/assign/profile/{profileId}
DELETE                  /api/routines/{routine}/assignments/{assignment}
```

**Bloques y ejercicios**
```
POST|PUT|DELETE  /api/routines/{routine}/blocks/{block?}
POST|PUT|DELETE  /api/routines/{routine}/blocks/{block}/exercises/{exerciseId?}
```

**Logs**
```
POST  /api/logs
GET   /api/me/logs
GET   /api/me/logs/exercise/{exerciseId}
GET   /api/me/logs/exercise/{exerciseId}/stats
```

**Marketplace**
```
GET   /api/marketplace
GET   /api/marketplace/recommended
POST  /api/marketplace/{routine}/checkout
GET   /api/marketplace/{routine}/purchase-status
POST  /api/routines/{routine}/publish
GET   /api/trainer/marketplace/stats
```

---

## Variables de entorno

### Backend (`routine-app/.env`)

| Variable | Descripción | Dónde obtenerla |
|----------|-------------|-----------------|
| `APP_KEY` | Clave de cifrado de Laravel | `php artisan key:generate` |
| `APP_ENV` | `local` o `production` | — |
| `DB_HOST` | Host de Supabase | supabase.com → proyecto → Settings → Database |
| `DB_PASSWORD` | Password de Supabase | ídem |
| `CLOUDINARY_CLOUD_NAME` | Nombre del cloud | cloudinary.com → Settings → API Keys |
| `CLOUDINARY_API_KEY` | API key de Cloudinary | ídem |
| `CLOUDINARY_API_SECRET` | Secret de Cloudinary | ídem |
| `MP_ACCESS_TOKEN` | Token de MercadoPago | mercadopago.com.ar/developers → tu app |
| `MP_PUBLIC_KEY` | Public key de MercadoPago | ídem |
| `GROQ_API_KEY` | API key de Groq (IA) | console.groq.com/keys |
| `FRONTEND_URL` | URL del frontend (CORS) | URL de Vercel o `http://localhost:5173` local |

### Frontend (`routine-app-front/.env`)

| Variable | Descripción |
|----------|-------------|
| `VITE_API_URL` | URL base del backend (ej: `https://powerroutines-api.onrender.com`). Si no se define, usa proxy local a `http://localhost:8000` |

---

## Consideraciones de despliegue

- **Anti-sleep**: UptimeRobot pingea `GET /api/ping` cada 5 min para que Render no duerma el servidor
- **Render**: el backend se despliega desde `routine-app/`. Config en `render.yaml`
- **Vercel**: el frontend se despliega desde `routine-app-front/`. Config en `vercel.json`
- **GD**: la extensión GD de PHP es necesaria para redimensionar imágenes antes de enviarlas a la IA. En Render no está disponible — hay fallback a `file_get_contents()`

---

## Estructura del backend

```
app/
├── Http/
│   ├── Controllers/     AIRoutineController, AuthController, BlockController,
│   │                    ExerciseController, ExerciseLogController, GymController,
│   │                    MarketplaceController, PaymentController, ProfileController,
│   │                    RoutineController
│   ├── Requests/        Validaciones por entidad (Auth, Block, Exercise, Gym, Log, Profile, Routine, User)
│   └── Resources/       BlockResource, ExerciseResource, GymResource, ProfileResource,
│                        RoutineAssignmentResource, RoutineResource, UserResource
├── Models/              User, Gym, Profile, Routine, RoutineAssignment, RoutinePurchase,
│                        Exercise, ExerciseLog, Block
├── Policies/            GymPolicy, RoutinePolicy
└── Services/            AuthService, ExerciseLogService, GymService,
                         ImageUploadService, RoutineService
```

## Estructura del frontend

```
src/
├── App.jsx
├── lib/
│   ├── api.js           cliente axios con baseURL dinámica
│   ├── auth.js          helpers de autenticación
│   └── cn.js            utilidad de clases Tailwind
├── components/
│   ├── Layout.jsx
│   ├── AvatarUpload.jsx
│   └── PremiumGate.jsx
└── pages/
    ├── LandingPage, LoginPage, RegisterPage, Onboarding, JoinPage
    ├── student/         Routines, RoutineDetail, RoutineEdit, RoutineSession,
    │                    Logs, Progress, Marketplace, Profile
    └── trainer/         Routines, RoutineEdit, Students, StudentDetail,
                         Marketplace, Profile
```
