# DECO* — Guía para Cursor
## Sistema de gestión para bares y restaurantes

> Este documento define el alcance funcional, la visión de producto, los roles, la UX/UI esperada y una propuesta de arquitectura para que Cursor implemente una primera base sólida del sistema.

---

## 1. Visión del producto

Construir una plataforma web moderna para gestión de bares y restaurantes, con foco fuerte en:

- **UX/UI premium** para el personal del local.
- **Experiencia moderna y fluida** para el cliente final.
- **Modularidad real** para crecer por etapas sin romper la base.
- **Operación en tiempo real** para pedidos, stock y estado de mesas.
- **Preparación para QR por mesa** y carta digital con pedidos desde el cliente.

La plataforma debe sentirse actual, rápida, visual, intuitiva y altamente usable en desktop y tablet. La prioridad no es solo “que funcione”, sino que **se vea y se use muy bien**.

---

## 2. Stack y lineamientos técnicos

### Base tecnológica obligatoria
- **Laravel 13** como framework principal.
- **Filament** para dashboards/paneles administrativos y operativos.
- Base de datos relacional: **MySQL/MariaDB**.
- Frontend con fuerte énfasis visual usando:
  - Tailwind CSS
  - Alpine.js o Livewire donde aporte fluidez
  - Animaciones y microinteracciones cuidadas

### Lineamientos de implementación
- Código limpio, modular y escalable.
- Separación clara entre dominio, panel administrativo y experiencia cliente.
- Diseño preparado para crecimiento multi-módulo.
- Estructura lista para incorporar luego:
  - QR por mesa
  - carta digital pública
  - pedidos desde cliente
  - pagos y métricas

### Documentación base a seguir
Tomar como referencia la documentación oficial de Laravel 13 y Filament indicada para el proyecto. fileciteturn0file0

---

## 3. Objetivo general del MVP

Implementar una primera versión funcional que permita:

1. Gestionar usuarios y roles.
2. Administrar productos de carta por categorías.
3. Controlar stock de platos, tragos e insumos base.
4. Registrar consumo y descontar stock.
5. Diseñar el plano del local con interfaz drag and drop.
6. Crear y administrar mesas numeradas.
7. Gestionar pedidos internos del salón.
8. Enviar pedidos a cocina/bar según corresponda.
9. Tener una base visual muy fuerte, moderna y animada.
10. Dejar preparado el sistema para futura integración de QR por mesa y pedido desde cliente.
11. Desde una vista y además un API REST poder consultar disponibilidad de mesas y eventuales flujos para reservas

---

## 4. Tipos de usuario y permisos

Implementar **RBAC** (role-based access control) desde el inicio.

### 4.1. Dev (superadmin)
**Soy yo.**
Debe tener acceso total a todo el sistema:
- configuración global
- locales
- usuarios
- permisos
- carta
- stock
- layout del local
- mesas
- pedidos
- métricas
- logs
- parámetros internos

### 4.2. Dueño
Acceso alto, orientado a negocio:
- ver ventas
- ver métricas
- administrar carta
- administrar stock
- administrar personal
- ver pedidos
- ver estado del local
- ver reportes

### 4.3. Gerente de local
Acceso operativo avanzado:
- gestionar mesas
- gestionar pedidos
- supervisar stock
- ver cocina/bar
- administrar mozos
- editar carta según permisos definidos
- abrir/cerrar turnos

### 4.4. Jefe de cocina / cocina
Acceso específico:
- recibir pedidos de cocina
- cambiar estados de preparación
- marcar listos
- ver observaciones del pedido
- ver cola de trabajo priorizada

### 4.5. Jefe de barra / barra
Similar al de cocina, pero filtrado a tragos/bebidas:
- recibir pedidos de barra
- cambiar estados
- marcar listos
- ver tiempos y prioridad

### 4.6. Mozo
Acceso operativo de salón:
- ver mapa de mesas
- abrir mesa
- cargar pedido
- agregar notas
- consultar estado del pedido
- cerrar mesa según permisos
- vincular clientes cuando aplique

### 4.7. Cliente visitante
Sin registro obligatorio:
- acceder a carta digital
- ver productos y categorías
- eventualmente escanear QR de mesa
- futura posibilidad de pedir desde su dispositivo

### 4.8. Cliente registrado
Extensión del visitante:
- perfil básico
- historial futuro
- preferencias futuras
- beneficios o fidelización futura

---

## 5. Módulos funcionales

## 5.1. Módulo de autenticación y permisos
### Requerimientos
- login seguro
- recuperación de contraseña
- middleware por roles
- permisos granulares por acción
- semilla inicial con usuario dev

### Seed obligatorio
Crear usuario inicial dev, configurable por `.env` o seeder:
- nombre: `Nacho` o configurable
- rol: `dev`
- acceso total

---

## 5.2. Módulo de carta / menú
Debe permitir construir una **carta visual, ordenada y flexible**.

### Requerimientos
- categorías de carta
  - entradas
  - principales
  - postres
  - bebidas
  - tragos
  - cervezas
  - cafetería
  - combos
  - extras
- subcategorías opcionales
- productos con:
  - nombre
  - descripción corta
  - descripción extendida opcional
  - precio
  - imagen
  - SKU/código interno opcional
  - disponibilidad
  - tiempo estimado
  - etiquetas (`vegano`, `picante`, `sin tacc`, `destacado`, etc.)
- orden manual por drag and drop dentro de categorías
- opción de producto destacado
- variantes opcionales
  - tamaños
  - puntos de cocción
  - agregados
  - extras
- complementos o modifiers

### Estado de producto
- disponible
- sin stock
- pausado
- oculto

### Objetivo UX
La administración de la carta debe sentirse como un editor moderno, rápido y agradable. No un CRUD tosco.

---

## 5.3. Módulo de stock
El stock debe ser práctico, no exageradamente complejo en esta etapa, pero sí bien diseñado.

### Requerimientos
- stock por producto vendible
- stock por insumo opcional para futura evolución
- movimientos de stock:
  - ingreso
  - ajuste
  - merma
  - consumo por venta
- contador de consumidos
- historial de movimientos
- alertas visuales de stock bajo
- reglas para marcar producto como no disponible cuando se queda sin stock

### Objetivo
Permitir algo simple pero útil:
- saber qué se consumió
- saber qué queda
- evitar vender productos sin disponibilidad real

---

## 5.4. Módulo de salón y mesas
Este módulo es central.

### Requerimientos
- gestión de sectores del local
  - salón principal
  - patio
  - terraza
  - barra
  - VIP
- mesas con:
  - número único visible
  - nombre opcional
  - capacidad
  - estado
  - posición en layout
  - forma (redonda, cuadrada, rectangular)

### Estados de mesa
- libre
- ocupada
- reservada
- pendiente de cobro
- cerrada/limpieza
- bloqueada

### Vista esperada
Un **mapa del local interactivo**, no solo una lista.

---

## 5.5. Editor drag and drop del local
Este módulo debe tener especial atención.

### Objetivo
Diseñar visualmente el local mediante una interfaz de arrastrar y soltar.

### Requerimientos
- canvas o área de diseño para el plano
- agregar mesas al plano
- mover mesas con drag and drop
- redimensionar elementos si aporta valor
- definir sectores
- numerar mesas
- guardar coordenadas y dimensiones
- vista adaptable para operación real
- permitir distintos tipos de mesa
- futura posibilidad de agregar:
  - caja
  - barra
  - cocina
  - baños
  - zonas bloqueadas

### Detalle técnico sugerido
Guardar layout en base de datos con estructura serializable tipo JSON + entidades persistidas.

Ejemplo conceptual:
- local
- sector
- mesa
- layout_items

### Importante
La interfaz debe verse moderna, limpia y usable en tablet.

---

## 5.6. Módulo de pedidos
### Requerimientos
- abrir pedido por mesa
- agregar productos
- agregar notas por ítem
- separar envío a cocina o barra según tipo de producto
- estados del pedido:
  - pendiente
  - enviado
  - en preparación
  - listo
  - entregado
  - cancelado
- estados por ítem y por pedido general
- timestamps de cada cambio
- historial

### UX operativa
- carga rápida
- pocos clics
- interfaz táctil amigable
- botones grandes en tablet
- confirmaciones suaves, no molestas

---

## 5.7. Módulo de cocina y barra
### Vista cocina
- cola de pedidos en tarjetas grandes
- orden por prioridad y hora
- cambiar estados con un toque
- destacar demoras
- resaltar observaciones

### Vista barra
- similar a cocina, filtrada por bebidas/tragos

### Estados visuales
Usar indicadores muy claros:
- nuevo
- en preparación
- listo
- entregado

No depender solo del color: también usar texto, iconos y jerarquía visual.

---

## 5.8. Módulo de clientes
### Visitantes
- acceso simple, fricción mínima
- sin obligación de registrarse

### Registrados
- registro opcional
- datos básicos
- posibilidad futura de historial, favoritos, descuentos y fidelización

### Importante
El sistema debe estar listo para convivir con ambos modelos:
- cliente anónimo
- cliente identificado

---

## 5.9. Módulo QR por mesa (dejar preparado, no cerrar todavía)
Todavía no desarrollar completo, pero dejar la arquitectura lista.

### Meta futura
Cada mesa tendrá un QR único que:
- identifica la mesa
- abre la carta digital
- permite pedir desde el dispositivo del cliente

### Desde ahora dejar previsto
- campo `qr_token` o identificador único por mesa
- rutas preparadas para carta pública por mesa
- separación entre vista interna y vista pública

---

## 6. Experiencia de usuario (UX) — prioridad alta
Este proyecto **debe diferenciarse por diseño y experiencia**, no solo por funcionalidades.

### Principios UX
- interfaz limpia
- navegación clara
- jerarquía visual fuerte
- tiempos de interacción cortos
- feedback inmediato
- transiciones suaves
- consistencia visual total
- diseño mobile/tablet-friendly para operación
- evitar interfaces saturadas o de “sistema viejo”

### Objetivo concreto
Que tanto el dueño como el mozo o cocina sientan que están usando un producto moderno, premium y bien pensado.

---

## 7. UI — dirección visual
### Estilo esperado
- moderno
- elegante
- oscuro o dual theme bien resuelto
- tarjetas con profundidad sutil
- bordes redondeados
- sombras suaves
- tipografía clara
- excelente espaciado
- animaciones cortas y agradables
- microinteracciones de hover, focus, carga y cambio de estado

### Componentes visuales deseados
- dashboard con métricas visuales
- tarjetas interactivas
- badges de estado
- drawers/modals finos y fluidos
- toasts elegantes
- skeleton loaders
- tablas modernas, no pesadas
- vista tipo kanban para cocina/bar si aporta valor
- mapa visual de mesas muy trabajado

### Animaciones
Agregar transiciones donde mejoren percepción de calidad:
- apertura de paneles
- cambio de estado de pedido
- hover de productos
- selección de mesa
- carga de vistas

Sin exagerar. Deben sumar calidad, no ruido.

---

## 8. Arquitectura funcional sugerida
Organizar el sistema por dominios/módulos.

### Dominios sugeridos
- Auth
- Users
- RolesPermissions
- Restaurants/Locations
- Menu
- Inventory
- FloorPlan
- Tables
- Orders
- Kitchen
- Bar
- Customers
- QR/PublicMenu
- Analytics

### Entidades iniciales sugeridas
- users
- roles
- permissions
- locations
- sectors
- tables
- table_layout_items
- menu_categories
- menu_items
- menu_item_variants
- menu_item_modifiers
- stock_items
- stock_movements
- orders
- order_items
- customers
- customer_sessions (futuro)
- qr_tables (o tokens asociados a mesas)

---

## 9. Modelo de datos base sugerido
### users
- id
- name
- email
- password
- role_id o relación many-to-many con roles
- is_active
- timestamps

### locations
- id
- name
- slug
- address
- is_active
- timestamps

### sectors
- id
- location_id
- name
- sort_order
- timestamps

### tables
- id
- location_id
- sector_id
- number
- name
- capacity
- shape
- status
- qr_token nullable
- pos_x
- pos_y
- width
- height
- rotation nullable
- timestamps

### menu_categories
- id
- location_id
- parent_id nullable
- name
- slug
- image nullable
- sort_order
- is_active
- timestamps

### menu_items
- id
- location_id
- category_id
- name
- slug
- short_description
- long_description nullable
- price
- image nullable
- item_type (`kitchen`, `bar`, `mixed`, etc.)
- stock_control_mode
- available
- highlighted
- prep_time_minutes nullable
- timestamps

### stock_items
- id
- location_id
- name
- unit
- current_stock
- minimum_stock
- timestamps

### stock_movements
- id
- stock_item_id
- type
- quantity
- reason
- user_id
- reference_type nullable
- reference_id nullable
- timestamps

### orders
- id
- location_id
- table_id nullable
- customer_id nullable
- waiter_id nullable
- status
- subtotal
- total
- notes nullable
- opened_at
- sent_at nullable
- closed_at nullable
- timestamps

### order_items
- id
- order_id
- menu_item_id
- quantity
- unit_price
- notes nullable
- target_station (`kitchen`, `bar`)
- status
- fired_at nullable
- ready_at nullable
- delivered_at nullable
- timestamps

### customers
- id
- name nullable
- email nullable
- phone nullable
- is_guest
- timestamps

---

## 10. Flujos principales a implementar

### Flujo 1 — Administrar carta
1. Crear categorías.
2. Crear productos.
3. Asignar precio, imagen, disponibilidad y stock.
4. Ordenar visualmente.
5. Publicar.

### Flujo 2 — Diseñar salón
1. Crear sectores.
2. Crear mesas.
3. Posicionarlas en editor drag and drop.
4. Guardar layout.
5. Operar desde mapa visual.

### Flujo 3 — Operación de mozo
1. Ver mapa de mesas.
2. Seleccionar mesa.
3. Abrir pedido.
4. Agregar productos.
5. Enviar.
6. Ver estados.
7. Entregar.
8. Cerrar.

### Flujo 4 — Cocina/bar
1. Recibir ítems filtrados por estación.
2. Marcar en preparación.
3. Marcar listos.
4. Notificar visualmente al salón.

---

## 11. Pantallas mínimas del MVP

### Panel general
- dashboard principal
- login
- perfil de usuario

### Administración
- gestión de usuarios
- roles y permisos
- locales
- sectores
- mesas
- categorías
- productos
- stock

### Operación
- mapa del salón
- detalle de mesa
- toma de pedido
- cola cocina
- cola barra

### Cliente/futuro público
- carta pública base preparada
- vista por QR preparada estructuralmente

---

## 12. Diseño del panel de mesas
Este punto requiere atención especial.

### Debe incluir
- vista tipo plano
- colores/estados claros
- número de mesa muy visible
- capacidad opcional visible
- estado en badge
- click/tap para abrir detalle
- filtros por sector
- animaciones suaves al seleccionar
- posibilidad de ver ocupación general rápidamente

### Sensación buscada
Que operar el salón desde esta pantalla sea rápido, intuitivo y agradable.

---

## 13. Consideraciones de frontend
### Requisitos de calidad visual
- no usar UI genérica sin criterio
- no dejar Filament “pelado”
- personalizar componentes, spacing, estados y apariencia
- trabajar una identidad visual consistente
- usar iconografía coherente
- priorizar legibilidad, contraste y claridad

### Requisitos de interacción
- loaders agradables
- estados vacíos bien diseñados
- confirmaciones no invasivas
- formularios con validación clara
- shortcuts o acciones rápidas donde tenga sentido

---

## 14. No hacer en esta primera etapa
Para no romper el foco, **no priorizar todavía**:
- facturación fiscal avanzada
- integración de pagos online
- delivery externo
- fidelización compleja
- multi-sucursal avanzada con reglas complejas
- analytics muy profundos
- IA

Dejar estructura lista, pero no desviar el MVP hacia eso.

---

## 15. Prioridad de desarrollo sugerida
### Fase 1
- auth
- roles/permisos
- usuario dev
- estructura base del proyecto
- dashboard inicial

### Fase 2
- categorías de carta
- productos
- stock base

### Fase 3
- sectores
- mesas
- editor drag and drop del local

### Fase 4
- pedidos
- flujo mozo
- flujo cocina/bar

### Fase 5
- pulido UX/UI
- animaciones
- optimización visual
- preparación para QR

---

## 16. Criterios de calidad para Cursor
Cursor debe implementar pensando en:

- mantenibilidad
- escalabilidad
- consistencia visual
- experiencia táctil
- bajo rozamiento operativo
- dominio claro del negocio gastronómico

### Regla importante
No conformarse con un CRUD administrativo estándar. La meta es un producto con sensación profesional y moderna.

---

## 17. Entregables esperados en esta etapa
1. Base de proyecto Laravel 13 + Filament correctamente estructurada.
2. Sistema de roles y permisos funcionando.
3. Usuario dev sembrado.
4. CRUD avanzado de carta y categorías.
5. Gestión de stock inicial.
6. Gestión de sectores y mesas.
7. Editor visual básico drag and drop del salón.
8. Flujo de pedido interno salón → cocina/bar.
9. UI moderna y consistente.
10. Base lista para evolucionar a QR por mesa.

---

## 18. Prompt operativo para Cursor
Usar este criterio durante toda la implementación:

> Implementá un sistema web de gestión para bares y restaurantes usando Laravel 13 y Filament. El sistema debe ser modular, escalable y con fuerte foco en UX/UI moderna. Priorizá una experiencia visual premium, transiciones suaves, microinteracciones y una operación muy ágil para salón, cocina y barra. Implementá roles para dev, dueño, gerente, jefes de cocina/bar, mozos y clientes. Creá módulos para carta por categorías, productos, stock, mesas, sectores, layout drag and drop del local y pedidos internos. Prepará desde la arquitectura la futura integración de QR por mesa y carta digital pública. Evitá hacer un CRUD genérico: el sistema debe sentirse como un producto moderno y bien diseñado.

---

## 19. Siguiente paso recomendado
Luego de esta guía, el siguiente documento ideal sería:
- un **PRD técnico por módulos**
- un **mapa de base de datos/migraciones**
- un **roadmap de implementación por sprints**
- un **design system base** para que la UI salga consistente desde el inicio

