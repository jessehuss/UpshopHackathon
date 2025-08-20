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

Base URL
- Local: `http://127.0.0.1:8000`

Common
- `Content-Type: application/json`
- For streaming: also set `Accept: text/event-stream`
- First turn auto-injects mocked data; subsequent turns use `conversation_id` history.

Endpoints

1) POST `/api/openai/generate`
- Description: Single-response chat completion.
- Body:
  - `content` (string, required): user message
  - `conversation_id` (string, optional): reuse to maintain context
  - `unhinged_mode` (boolean, optional): playful/unhinged system prompts
- Response:
```json
{ "content": "Here are three taglines..." }
```
- Example:
```bash
curl -X POST http://127.0.0.1:8000/api/openai/generate \
  -H "Content-Type: application/json" \
  -d '{"content":"Give me 3 taglines for organic apple juice.","conversation_id":"chat-1"}'
```

2) POST `/api/openai/generate/stream`
- Description: Server-Sent Events (SSE) streaming for chatbot typing UI.
- Headers: `Accept: text/event-stream`, `Content-Type: application/json`
- Body: same as above (`content`, `conversation_id`, `unhinged_mode`)
- Stream events:
  - `event: start` with `data: {"status":"started"}`
  - `event: token` with `data: {"content":"<partial token>"}` (repeats)
  - `event: end` with `data: {"status":"completed"}`
  - `event: error` with `data: {"error":"...", "message":"..."}`
- Example (PowerShell):
```powershell
curl.exe -N -H "Accept: text/event-stream" -H "Content-Type: application/json" `
  -d '{ "content": "Hello", "conversation_id": "chat-1" }' `
  http://127.0.0.1:8000/api/openai/generate/stream
```

Minimal browser streaming helper
```javascript
export async function streamChat({ content, conversationId, unhingedMode = false, onToken }) {
  const res = await fetch('/api/openai/generate/stream', {
    method: 'POST',
    headers: { 'Accept': 'text/event-stream', 'Content-Type': 'application/json' },
    body: JSON.stringify({ content, conversation_id: conversationId, unhinged_mode: unhingedMode }),
  });
  const reader = res.body.getReader();
  const decoder = new TextDecoder();
  let buf = '';
  while (true) {
    const { value, done } = await reader.read();
    if (done) break;
    buf += decoder.decode(value, { stream: true });
    let idx;
    while ((idx = buf.indexOf('\n\n')) >= 0) {
      const chunk = buf.slice(0, idx).trim(); buf = buf.slice(idx + 2);
      let event = 'message', data = '';
      for (const line of chunk.split('\n')) {
        if (line.startsWith('event:')) event = line.slice(6).trim();
        else if (line.startsWith('data:')) data = line.slice(5).trim();
      }
      if (event === 'token') {
        const { content: delta } = JSON.parse(data || '{}');
        if (delta) onToken(delta);
      }
    }
  }
}
```

### Structure
- `app/Services/OpenAIService.php` — wrapper around OpenAI client
- `app/Http/Controllers/Api/OpenAIController.php` — standard chat
- `app/Http/Controllers/Api/OpenAIStreamController.php` — SSE streaming
- `routes/api.php` — API routes
- `config/openai.php` — env + defaults

No database; file cache only.
