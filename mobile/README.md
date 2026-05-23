# PRISM Mobile (Expo + React Native)

Parent app: login + tabs for Home, Homework, Marks, Attendance, Timetable, Feed, Fees, Online class, Leave, Notifications.

## Setup

```bash
npm install
npx expo start
```

## Test in browser (fastest on Laragon)

```bash
npm run web
```

Opens the app at `http://localhost:8081` (or the port Expo prints). Uses `http://prism.test/api` directly — no LAN IP needed. Good for login, dashboard, and UI work.

For push notifications and device behavior, use **Expo Go** (`npx expo start` → scan QR).

## API URL

Laragon serves the API at **`http://prism.test/api`** (see repo root `.htaccess`).

Copy `.env.example` to `.env`:

- `EXPO_PUBLIC_API_URL=http://prism.test/api`
- `EXPO_PUBLIC_API_LAN_IP=` your PC Wi‑Fi IP (`ipconfig`) — required on a **physical phone**

The app calls your LAN IP with `Host: prism.test` so Laragon’s vhost matches. **Android emulator** can omit `LAN_IP` (uses `10.0.2.2` automatically).

## FCM

Wire Firebase / Expo push later; the API records device tokens at `POST /api/prism/device-tokens` and logs FCM sends until a real FCM HTTP v1 client is configured.
