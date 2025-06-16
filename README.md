# **Título del Proyecto:**
Plataforma de Estandarización y Gestión Documental CFE (PEGD-CFE)

## Descripción:
Qué hace la aplicación, su propósito.
## Características Principales:
Lista de lo que ya puede hacer.
## Requisitos del Sistema:
Versión de PHP, MySQL, Composer, etc.
## Guía de Instalación (Paso a Paso):
Clonar el repositorio.
composer install.
cp .env.example .env.
php artisan key:generate.
Configurar .env (especialmente APP_URL, DB_* y GOOGLE_*).
Configurar Cloud SQL Proxy y ejecutarlo.
php artisan migrate.
php artisan db:seed (para roles y admin inicial).
php artisan serve.
## Uso: 
Cómo ejecutar la aplicación, credenciales de login de prueba.
Contactos/Licencia (Opcional).