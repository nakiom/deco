# Sistema de Gestión de Bares/Restaurantes – Especificación Técnica (Cursor Ready)

## 1. Estructura del Proyecto (Laravel 13)

app/
 ├── Models/
 ├── Http/
 │   ├── Controllers/
 │   ├── Requests/
 ├── Services/
 ├── Actions/
 ├── Enums/
database/
 ├── migrations/
 ├── seeders/
 ├── factories/

## 2. Roles del Sistema

ENUM roles:
- DEV
- OWNER
- MANAGER
- KITCHEN
- WAITER
- CLIENT

## 3. Modelo de Datos

### users
- id
- name
- email
- password
- role (enum)
- created_at

### restaurants
- id
- name
- owner_id

### tables
- id
- restaurant_id
- number
- qr_code

### categories
- id
- name
- restaurant_id

### products
- id
- name
- category_id
- price
- stock
- is_active

### orders
- id
- table_id
- status (pending, preparing, served, closed)
- total

### order_items
- id
- order_id
- product_id
- quantity
- price

## 4. Estados

OrderStatus:
- PENDING
- IN_PROGRESS
- READY
- DELIVERED
- CLOSED

## 5. Seeders

- Crear usuario DEV
- Crear restaurant demo
- Crear categorías base
- Crear productos ejemplo

## 6. Módulos

- Auth + Roles
- Gestión de carta
- Gestión de mesas
- Pedidos en tiempo real
- Cocina/Bar dashboard

## 7. UX/UI Guidelines

- SPA feel (Livewire/Filament)
- Transiciones suaves
- Dark mode por defecto
- Mobile-first
- Botones grandes para mozos
- UI tipo POS

## 8. Drag & Drop Layout

- Grid editable
- Mesas movibles
- Persistencia JSON

## 9. QR System (base)

- QR → /menu/{table_id}
- Cliente crea pedido

## 10. Backlog Fases

Fase 1:
- Auth + roles
- CRUD productos
- CRUD mesas

Fase 2:
- Pedidos básicos
- Cocina dashboard

Fase 3:
- QR + cliente
- UX mejoras

Fase 4:
- Analytics
- Optimización

## 11. Reglas Clave

- No lógica en controllers
- Usar Services/Actions
- Validaciones con FormRequest
- Tests mínimos en features críticas
