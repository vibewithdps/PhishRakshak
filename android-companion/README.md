# PhishRakshak Guard Android Companion

This native Android companion is required for SMS and call auto-protection.

Why this exists:
- PWA cannot read/delete SMS inbox in background.
- PWA cannot reject or silence phone calls.
- Android requires a native app to become default SMS app or Call Screening app.

## Features

- Incoming SMS scan through `/api/scan` with `type=sms`
- Spam SMS notification
- Optional high-risk SMS delete/quarantine attempt when default SMS role is active
- CallScreeningService spam-call scan through `/api/scan` with `type=call`
- High-risk call reject/silence
- Local fallback detector if API token/backend is not available

## Build

Use Android Studio or Gradle CLI with Android SDK installed:

```bash
cd android-companion
gradle assembleDebug
```

Then install the debug APK from:

```text
app/build/outputs/apk/debug/app-debug.apk
```

## Setup on phone

1. Open PhishRakshak Guard.
2. Enter backend URL, example `http://192.168.1.5:8000`.
3. Paste Laravel API token from the PhishRakshak login response.
4. Tap Save Settings.
5. Tap Make Default SMS App.
6. Tap Enable Call Screening.

Important: SMS delete/trash behavior works only when Android allows this app as default SMS app. Call reject/silence works only after Call Screening role is granted.
