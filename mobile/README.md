# PRISM Mobile (Expo + React Native)

Parent/staff shell: login + parent aggregate dashboard.

## Setup

```bash
npm install
npx expo start
```

## API URL

Laragon serves the API at **`http://prism.test/api`** (see repo root `.htaccess`).

Copy `.env.example` to `.env`:

- `EXPO_PUBLIC_API_URL=http://prism.test/api`
- `EXPO_PUBLIC_API_LAN_IP=` your PC Wi‑Fi IP (`ipconfig`) — required on a **physical phone**

The app calls your LAN IP with `Host: prism.test` so Laragon’s vhost matches. **Android emulator** can omit `LAN_IP` (uses `10.0.2.2` automatically).

## FCM

Wire Firebase / Expo push later; the API records device tokens at `POST /api/prism/device-tokens` and logs FCM sends until a real FCM HTTP v1 client is configured.
