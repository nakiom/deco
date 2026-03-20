# DECO — Proyecto Laravel 13 + Filament 5

## 📌 Contexto

Proyecto web basado en:

- Laravel 13
- Filament 5
- Entorno local: Arch Linux
- Ruta: `/srv/http/deco`
- Dominio local: `http://deco.local`

Este proyecto está diseñado para ser desarrollado asistido por IA (Cursor), por lo que se definen reglas estrictas de trabajo, commits y estructura.

---

## ⚙️ Setup inicial

```bash
cd /srv/http/deco

composer create-project laravel/laravel . "^13.0"
composer require filament/filament:"^5.0"
php artisan filament:install --panels
```

### Permisos de `storage` y `bootstrap/cache`

Si el proyecto vive bajo `/srv/http` y Apache/nginx ha tocado los archivos, `storage/` suele pertenecer al usuario del servidor web (en Arch suele ser **`http`**). **`php artisan serve`** corre como tu usuario (p. ej. `nacho`), que entonces **no puede escribir** en `storage/logs/laravel.log` ni en caché/vistas — aparece *Permission denied* y a veces errores en cascada (p. ej. al abrir `/admin/orders/…/edit`).

Desde la raíz del proyecto, ejecutá **una vez** (ajustá el grupo si tu servidor usa otro, p. ej. `www-data`):

```bash
cd /srv/http/deco
sudo chown -R "$USER:http" storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
```

Opcional: `sudo usermod -aG http "$USER"` y nueva sesión, para compartir grupo con el servidor web.

**Solo para no escribir logs a disco** (no arregla subidas ni caché): en `.env` podés poner `LOG_CHANNEL=stderr` mientras corregís permisos.

## Flujo de trabajo

```bash
# crear feature
git checkout develop
git checkout -b feature/nombre

# trabajar...

git add .
git commit -m "feat: descripcion clara"

# volver a develop
git checkout develop
git merge feature/nombre

# limpiar
git branch -d feature/nombre
```

---

## 🚀 Acceso al panel admin

`http://deco.local/admin`

### Mozo: pedidos en el celular

**Operación → Pedidos** (`/admin/waiter-orders`): pestañas **Mis pedidos** (solo los que tenés asignados como mozo) y **Abiertos** (todos los pedidos abiertos del local). Muestra estado del pedido, ítem por ítem (cocina/bar, pendiente/listo), aviso si la cocina terminó, y botón **Ver pedido** al detalle en Filament. Pensado para pantallas chicas.

### Usuarios de demostración (por rol)

Tras `php artisan db:seed` (o `migrate:fresh --seed`), la contraseña común de prueba es **`password`**.

| Rol          | Email              | Notas breves                          |
|-------------|--------------------|----------------------------------------|
| **dev**     | `dev@deco.local`¹  | Acceso total; email configurable en `.env` (`DECO_DEV_EMAIL`). |
| **owner**   | `owner@deco.local` | Dueño del local asociado a Deco Bar.   |
| **manager** | `manager@deco.local` | Gerente operativo.                  |
| **kitchen** | `kitchen@deco.local` | Cola cocina, etc.                    |
| **bar**     | `bar@deco.local`   | Cola barra.                            |
| **waiter**  | `waiter@deco.local` | Mesas y pedidos.                      |
| **client**  | `client@deco.local` | Sin permisos de panel (rol “cliente”). |

¹ Creado por `DecoBarSeeder`; el resto por `DemoUsersSeeder`.

Para volver a generar solo usuarios demo: `php artisan db:seed --class=DemoUsersSeeder`

---

## Carta digital pública

- **URL por local:** `http://deco.local/carta/{slug}` — el `slug` se define en cada restaurante (puede ser por ejemplo `decoCentro` o `parque-norte`).

### Subidas de imágenes (logo, carta) — CORS / 403

1. **Enlace de storage:** debe existir `public/storage` → `storage/app/public`. Si falta: `php artisan storage:link`.
2. **`APP_URL` en `.env`:** debe ser exactamente el origen con el que abrís el sitio (mismo host y puerto), p. ej. `http://deco.local`. Si el panel está en `deco.local` pero `APP_URL` es `http://localhost`, las URLs de archivos apuntan a `localhost` y el navegador bloquea la petición (CORS) al previsualizar o recortar en Filament.
3. Tras cambiar `.env`: `php artisan config:clear`.

### Carta pública y Vite

La vista `/carta/{slug}` **no usa** `@vite` para no exigir `public/build/manifest.json`. Usa Tailwind vía CDN en esa página. Si en el futuro preferís un CSS empaquetado, ejecutá `npm run build` y podés volver a enlazar `resources/css/app.css` solo en esa vista.

### Vaciar pedidos (simulacro)

```bash
php artisan deco:clear-orders --force
```

Borra todas las órdenes e ítems, restaura stock en productos con control, marca las mesas como libres y elimina notificaciones de “pedido listo en cocina”. Opciones: `--no-stock`, `--no-reset-tables`, `--keep-notifications`. Alias: `deco:simulacro-reset`.

### Nuevo pedido (táctil)

**Pedidos → Nuevo pedido** (`/admin/orders/create`) es una pantalla pensada para celular: botones de **mesa**, **toda la carta** con anclas por categoría, **+ / −** por producto, **notas** por ítem y texto de **stock**. La barra inferior resume ítems y total y **Confirma** el pedido (descuenta stock si aplica). Desde **Mesas** cada fila tiene acción **Pedido** que abre el mismo alta con `?table_id=…`. Hace falta permiso `orders.create` (el rol **owner** lo incluye tras volver a correr `RolePermissionSeeder` o asignar el permiso a mano).
- **Panel:** menú lateral **Carta → Carta digital** (accesos rápidos), o **Restaurantes → Editar** → sección **Carta digital** (logo, cabecera, fondo, colores, columnas, cintas).
- **Platos:** en **Productos**, apartado **Carta digital** (ofertas, etiquetas dietéticas, comentario en carta).
- **QR por mesa (seguro):** URL `GET /menu/{uuid}/{secreto}` — el **UUID** identifica la mesa en público y el **secreto** (64 hex) solo coincide con un `hash_hmac` guardado en BD; al **regenerar** el QR en **Mesas → Editar** se invalida el anterior. Los enlaces antiguos de un solo segmento `/menu/{token}` siguen resolviendo por hash mientras el secreto no se haya rotado. **Estado acceso QR** en la mesa: activa / inactiva / suspendida (bloquea carta y futuros pedidos). **Contraseña opcional** del local: **Restaurantes → Editar → Acceso carta por QR**. Los pedidos desde el panel táctil quedan con origen **Personal / POS**; el campo **Origen** en la grilla de pedidos distingue **QR mesa** cuando exista flujo público. La sesión `deco_menu_qr` guarda mesa+local tras validar el QR (para futuros POST de pedido).
- **Llamar al mozo:** en la carta por QR hay un botón fijo que envía un aviso a **Operación → Pedidos** (Salón): columna de llamados, contador en la barra superior y **Atendido** para limpiar el llamado (requiere `tables.update`).

### Pantalla Salón (`/admin/waiter-orders`)

Pensada para uso intensivo: **estadísticas** (mis pedidos, salón, prioridad cocina, para retirar, llamados), **búsqueda por mesa**, **filtros** (todos / prioridad / para retirar), pestañas **Mis pedidos** / **Todo el salón**, **asignación** de pedidos, **marcar entregados** los ítems listos, accesos en cabecera a **Nuevo pedido**, Mapa operativo, Cocina y Barra. **Actualización automática** cada ~8 s. En escritorio, **sidebar** con llamados de mesa; en móvil, franja compacta de llamados.
