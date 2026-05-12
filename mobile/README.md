# PRISM Mobile (Expo + React Native)

Parent/staff shell: login + parent aggregate dashboard.

## Setup

```bash
npm install
npx expo start
```

## API URL

- **Android emulator:** default `http://10.0.2.2:8000/api` in `App.js` (host machine Laravel `php artisan serve`).
- **Physical device:** set `EXPO_PUBLIC_API_URL=http://YOUR_LAN_IP:8000/api` or add `extra.apiUrl` in `app.json` under `expo.extra`.

## FCM

Wire Firebase / Expo push later; the API records device tokens at `POST /api/prism/device-tokens` and logs FCM sends until a real FCM HTTP v1 client is configured.
