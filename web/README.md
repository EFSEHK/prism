# PRISM Web (Vue 3 + Vite)

Admin/staff SPA for the school API.

## Setup

```bash
npm install
cp .env.example .env
npm run dev
```

Dev server: `http://localhost:5173`. With empty `VITE_API_URL`, the browser calls `/api/*` on the dev server and Vite proxies to `VITE_API_PROXY_TARGET` (Laragon: `http://prism.test`; `php artisan serve`: `http://127.0.0.1:8000`). Do not include `/api` in the proxy target — that would double the prefix to `/api/api/*`.

Login uses `POST /api/login` with Bearer token stored in `localStorage`.

## Production build

```bash
npm run build
```

Point `VITE_API_URL` at your API origin if the app is not served with a reverse proxy to the same host.
