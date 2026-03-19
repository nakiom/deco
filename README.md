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

Tras crear un usuario administrador: `http://deco.local/admin`
