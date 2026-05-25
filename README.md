# AnunciosFácil

Aplicación web de publicación de anuncios gratuitos, desarrollada con **PHP 8.2 + MySQL 8.0 + Materialize CSS**.  
Proyecto académico para la asignatura **Seguridad de Aplicaciones (SEA)**.

---

## Requisitos previos

| Herramienta | Versión mínima |
|-------------|----------------|
| Docker      | 24.x           |
| Docker Compose | 2.x         |

---

## Puesta en marcha

### 1. Clonar / descargar el proyecto

```bash
cd anuncios-app
```

### 2. Crear el archivo de variables de entorno

```bash
cp .env.example .env
```

Editar `.env` con tus propias contraseñas (nunca subir este archivo al repositorio):

```dotenv
DB_NAME=anuncios_db
DB_USER=anuncios_user
DB_PASS=tu_clave_segura_aqui
DB_ROOT_PASS=tu_clave_root_aqui
```

### 3. Construir y levantar los contenedores

```bash
docker compose up --build -d
```

La primera vez tarda ~2-3 minutos mientras descarga las imágenes base y ejecuta la migración SQL.

### 4. Verificar que los servicios estén activos

```bash
docker compose ps
```

Todos los servicios deben estar en estado `running`.

### 5. Acceder a la aplicación

| URL | Descripción |
|-----|-------------|
| http://localhost:8080 | Aplicación principal |
| http://localhost:8081 | phpMyAdmin (gestión de BD) |

---

## Estructura del proyecto

```
anuncios-app/
├── .env.example            ← Plantilla de variables de entorno
├── .gitignore
├── docker-compose.yml      ← Orquestación de servicios
├── README.md
├── docker/
│   └── php/
│       └── Dockerfile      ← PHP 8.2 + Apache + PDO MySQL
├── sql/
│   └── migration.sql       ← Esquema de BD + datos iniciales
└── src/                    ← Raíz del servidor web (document root)
    ├── .htaccess           ← Seguridad Apache: sin listado de directorios
    ├── config.php          ← Configuración global y headers de seguridad
    ├── db.php              ← Conexión PDO (singleton)
    ├── functions.php       ← Funciones de seguridad y utilidades
    ├── index.php           ← Home: listado de anuncios
    ├── register.php        ← Registro de usuarios
    ├── login.php           ← Inicio de sesión
    ├── logout.php          ← Cierre de sesión
    ├── create-ad.php       ← Publicar nuevo anuncio
    ├── ad.php              ← Vista de detalle de anuncio
    ├── uploads/            ← Imágenes subidas por usuarios
    └── templates/
        ├── header.php      ← Navegación + cabecera HTML
        └── footer.php      ← Pie de página + scripts JS
```

---

## Detener y limpiar los contenedores

```bash
# Detener sin borrar datos
docker compose down

# Detener y eliminar la base de datos (volumen)
docker compose down -v
```

---

## Controles de seguridad implementados

### 1. Campos obligatorios y tipos de entrada HTML5

Todos los formularios usan atributos HTML5 (`required`, `type="email"`, `type="password"`, `minlength`, `maxlength`) como primera capa de validación en el cliente.

```html
<input type="email" name="email" maxlength="255" required autocomplete="email">
<input type="password" name="password" minlength="8" maxlength="128" required>
```

### 2. Validación server-side (PHP)

Cada campo es revalidado en el servidor independientemente de la validación del cliente, aplicando:

- Verificación de valores vacíos/nulos (`validateString`, `validateEmail`, `validateInt`)
- Restricciones de longitud mínima y máxima
- Patrones con expresiones regulares (username: solo `[a-zA-Z0-9_]`)
- Validación de email con `FILTER_VALIDATE_EMAIL`
- Validación de enteros con `FILTER_VALIDATE_INT` y rangos mínimos

```php
// functions.php
function validateString(string $value, string $label, int $min = 1, int $max = 255): string {
    $value = trim($value);
    if (strlen($value) < $min) throw new InvalidArgumentException("...");
    if (strlen($value) > $max) throw new InvalidArgumentException("...");
    return $value;
}
```

### 3. Almacenamiento en base de datos (MySQL + PDO)

Toda la información del formulario se almacena en MySQL usando **PDO** con prepared statements. La conexión se configura con:

- `ERRMODE_EXCEPTION`: errores como excepciones, no mensajes visibles
- `EMULATE_PREPARES = false`: prepared statements reales (no emulados)
- Charset `utf8mb4` en el DSN para prevenir ataques de encoding

### 4. Prevención de SQL Injection — Consultas preparadas

**Ninguna consulta concatena strings con datos del usuario.** Todos los valores se pasan como parámetros:

```php
// VULNERABLE ✗
$query = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";

// CORRECTO ✓ (db.php + todos los archivos PHP)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
```

### 5. Prevención de XSS — Escape de salida

Toda variable mostrada en HTML pasa por `h()`, que aplica `htmlspecialchars` con charset UTF-8:

```php
// functions.php
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Uso en plantillas
echo h($ad['title']);
echo h($ad['description']);
```

### 6. Hashing de contraseñas (bcrypt)

Las contraseñas se almacenan con `password_hash()` usando el algoritmo BCRYPT (costo 12). **Nunca se guarda la contraseña en texto plano ni en Base64.**

```php
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
// Verificación:
$valid = password_verify($password, $user['password_hash']);
```

### 7. Protección CSRF

Cada formulario POST incluye un token CSRF aleatorio (32 bytes) generado con `random_bytes()` y verificado con `hash_equals()` (comparación en tiempo constante para evitar timing attacks):

```php
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Token de seguridad inválido.');
    }
}
```

### 8. Sesión segura

- `session_regenerate_id(true)` al iniciar sesión (previene *session fixation*)
- Cookie de sesión: `HttpOnly` + `SameSite=Lax` (previene acceso desde JS y CSRF básico)
- `use_strict_mode = 1`: rechaza IDs de sesión no generados por el servidor
- Destrucción completa en logout: vaciar `$_SESSION`, borrar cookie, `session_destroy()`

### 9. Validación de subida de imágenes

- Verificación del tipo MIME **real** con `finfo` (no el nombre del archivo)
- Whitelist de tipos permitidos: `image/jpeg`, `image/png`, `image/webp`
- Límite de tamaño: 2 MB
- Nombre de archivo **aleatorio** (`random_bytes(16)`) sin datos del usuario

### 10. Manejo seguro de errores y logging

- `display_errors = Off` en producción: errores nunca visibles al usuario
- Errores registrados en `src/logs/app.log` con contexto
- El usuario recibe mensajes genéricos con referencia (request_id)
- Protección por `.htaccess`: el directorio `logs/` no es accesible desde el navegador

### 11. Headers de seguridad HTTP

Enviados en cada respuesta desde `config.php`:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
```

### 12. Privacidad del email del usuario

Durante el registro, el usuario elige si su correo será público en sus anuncios. La lógica se valida en el servidor y se almacena en `users.email_public`. El email solo se muestra en el frontend cuando `email_public = 1`.

---

## Tecnologías

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2 |
| Servidor web | Apache 2.4 (mod_rewrite) |
| Base de datos | MySQL 8.0 |
| ORM / Acceso a datos | PDO nativo |
| Frontend | HTML5 + Materialize CSS 1.0 |
| Contenedores | Docker + Docker Compose |
| Gestión de BD (dev) | phpMyAdmin |

---

## Asignatura

**Seguridad de Aplicaciones (SEA)** — Universidad  de Manizales
Semestre 5 · 2026
