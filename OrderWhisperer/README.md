<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Upshop AI Integration Layer (Laravel 11)

This repository hosts a lightweight Laravel 11 backend (PHP 8.3) for an AI integration layer. It accepts JSON requests, optionally calls internal data providers, and forwards prompts to OpenAI using `openai-php/laravel`.

### Requirements
- PHP 8.3+
- Composer 2

### Setup
1. Install dependencies:
```bash
composer install
```
2. Create env file and app key:
```bash
copy .env.example .env
php artisan key:generate
```
3. Configure OpenAI in `.env`:
```dotenv
OPENAI_API_KEY=
OPENAI_ORGANIZATION=
OPENAI_PROJECT=
OPENAI_BASE_URL=
OPENAI_REQUEST_TIMEOUT=30
```
4. Serve locally:
```bash
php artisan serve
```

### API
- POST `/api/openai/generate`
  - Body fields:
    - `model` (string, required)
    - `messages` (array, required)
    - `temperature` (float, optional)
    - `max_tokens` (int, optional)
    - `top_p` (float, optional)

### Structure
- `app/Services/OpenAIService.php` — wrapper around OpenAI client
- `app/Http/Controllers/Api/OpenAIController.php` — validates inputs, calls service
- `routes/api.php` — defines API endpoints
- `config/openai.php` — reads from `.env`

No database scaffolding is included.
