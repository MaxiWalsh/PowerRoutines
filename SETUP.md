# Guía de instalación — PowerRoutines

Cómo levantar el proyecto completo en una PC nueva.

---

## Prerrequisitos

Instalá estas herramientas antes de empezar:

| Herramienta | Versión mínima | Cómo instalar |
|-------------|---------------|---------------|
| PHP | 8.3 | [php.net](https://www.php.net/downloads) · en Windows recomendado via [XAMPP](https://www.apachefriends.org/) |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org/download/) |
| Node.js | 18+ | [nodejs.org](https://nodejs.org/) |
| npm | incluido con Node | — |
| Git | cualquiera | [git-scm.com](https://git-scm.com/) |

> **No necesitás** instalar PostgreSQL localmente — la base de datos vive en Supabase en la nube.

---

## 1. Clonar los repos

```bash
git clone https://github.com/MaxiWalsh/PowerRoutines.git
git clone https://github.com/MaxiWalsh/PowerRoutines-front.git
```

Deberías tener esta estructura:
```
PowerRoutines/
├── routine-app/          ← backend
└── routine-app-front/    ← frontend (si lo clonaste dentro)
```

---

## 2. Backend (Laravel)

### 2.1 Instalar dependencias

```bash
cd routine-app
composer install
```

### 2.2 Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Abrí `.env` y completá los valores requeridos (ver tabla abajo).

### 2.3 Correr las migraciones

```bash
php artisan migrate
```

Esto crea todas las tablas en la base de datos de Supabase.

### 2.4 Crear los roles

```bash
php artisan db:seed --class=RoleSeeder
```

> Si no existe el seeder o falla, podés crearlo manualmente desde tinker:
> ```bash
> php artisan tinker --execute="
>   \Spatie\Permission\Models\Role::firstOrCreate(['name'=>'trainer','guard_name'=>'web']);
>   \Spatie\Permission\Models\Role::firstOrCreate(['name'=>'student','guard_name'=>'web']);
> "
> ```

### 2.5 Levantar el servidor

```bash
php artisan serve
```

El backend queda en `http://localhost:8000`.

### Verificación

```bash
curl http://localhost:8000/api/ping
# Esperado: 200 OK
```

---

## 3. Frontend (React)

### 3.1 Instalar dependencias

```bash
cd routine-app-front
npm install
```

### 3.2 Configurar el entorno

```bash
cp .env.example .env
```

Para desarrollo local no hace falta cambiar nada — el proxy de Vite apunta automáticamente a `http://localhost:8000`.

Si querés apuntar a producción:
```
VITE_API_URL=https://powerroutines-api.onrender.com
```

### 3.3 Levantar el servidor de desarrollo

```bash
npm run dev
```

El frontend queda en `http://localhost:5173`.

---

## 4. Variables de entorno del backend

Completá estas variables en `routine-app/.env`:

### Obligatorias para que funcione

| Variable | Dónde obtenerla |
|----------|-----------------|
| `APP_KEY` | Se genera con `php artisan key:generate` |
| `DB_HOST` | Supabase → tu proyecto → Settings → Database → Host |
| `DB_PASSWORD` | Supabase → tu proyecto → Settings → Database → Password |

### Para subir imágenes (avatares, logos)

| Variable | Dónde obtenerla |
|----------|-----------------|
| `CLOUDINARY_CLOUD_NAME` | [cloudinary.com](https://cloudinary.com) → Settings → API Keys |
| `CLOUDINARY_API_KEY` | ídem |
| `CLOUDINARY_API_SECRET` | ídem |

### Para la IA (generar rutinas desde foto)

| Variable | Dónde obtenerla |
|----------|-----------------|
| `GROQ_API_KEY` | [console.groq.com/keys](https://console.groq.com/keys) — gratis, sin tarjeta |

### Para pagos (marketplace)

| Variable | Dónde obtenerla |
|----------|-----------------|
| `MP_ACCESS_TOKEN` | [mercadopago.com.ar/developers](https://www.mercadopago.com.ar/developers/panel/app) → tu app → Credenciales de prueba |
| `MP_PUBLIC_KEY` | ídem |

### CORS

| Variable | Valor para desarrollo |
|----------|----------------------|
| `FRONTEND_URL` | `http://localhost:5173` |

---

## 5. Crear un usuario de prueba

```bash
cd routine-app
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name'     => 'Trainer Test',
    'email'    => 'trainer@test.com',
    'password' => bcrypt('password'),
]);
$user->assignRole('trainer');

echo \App\Models\User::first()->createToken('dev')->plainTextToken;
```

Copiá el token para usar en Postman o en el curl de prueba.

---

## 6. Checklist final

- [ ] `php artisan serve` corre sin errores
- [ ] `curl http://localhost:8000/api/ping` devuelve 200
- [ ] `npm run dev` abre `http://localhost:5173` sin errores de consola
- [ ] Login funciona y devuelve token
- [ ] Las imágenes se suben correctamente (requiere Cloudinary configurado)
- [ ] La generación de rutina desde foto funciona (requiere `GROQ_API_KEY`)

---

## Colección Postman

En la raíz del monorepo hay dos archivos:
- `RoutineApp.postman_collection.json` — todos los endpoints
- `RoutineApp.postman_environment.json` — variables de entorno (URL base, token)

Importalos en Postman para probar la API sin frontend.

---

## Problemas frecuentes

| Problema | Causa | Solución |
|----------|-------|----------|
| `php artisan` no funciona | PHP no está en el PATH | Agregá la carpeta de PHP al PATH del sistema |
| Error de SSL en requests locales | Certificados faltantes en Windows | El código ya tiene `withoutVerifying()` en entorno local |
| `CORS error` en el frontend | `FRONTEND_URL` mal configurado | Verificá que `FRONTEND_URL=http://localhost:5173` en el `.env` del backend |
| Migraciones fallan | Credenciales de DB incorrectas | Revisá `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| `APP_KEY` vacío | Olvidaste generar la key | `php artisan key:generate` |
| Config cacheada con valores viejos | Cambios en `.env` no se reflejan | `php artisan config:clear` |
