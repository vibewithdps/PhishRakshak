# PhishRakshak Auto Protection Update

This update adds the next phase after PWA:

- Gmail OAuth backend protection
- Gmail recent inbox scan
- Gmail safe modes: notify, label, trash high-risk only
- PWA `/protection` page
- Android companion project for SMS + call auto-protection
- Scan engine now supports `sms`, `url`, `apk`, `email/mail`, and `call`

## Important truth

PWA cannot automatically read/delete SMS, reject phone calls, or control Gmail inbox by itself.

So the project now uses this practical architecture:

```text
PWA Frontend
  -> manual scan, protection dashboard, Gmail connect/settings, history

Laravel Backend
  -> scan engine, Gmail OAuth, Gmail API scan/label/trash

Android Companion
  -> default SMS app role, SMS receiver, CallScreeningService
```

## Backend setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Add this in `backend/.env`:

```env
FRONTEND_URL=http://localhost:5173
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/api/gmail/callback
```

Google Cloud Console redirect URI must exactly match:

```text
http://127.0.0.1:8000/api/gmail/callback
```

## Frontend setup

```bash
cd frontend
cp .env.example .env
npm install
npm run lint
npm run build
npm run dev
```

Open:

```text
http://localhost:5173/protection
```

## Gmail protection flow

1. Login to PhishRakshak PWA.
2. Open Protection page.
3. Click Connect Gmail.
4. Select Gmail account and allow permission.
5. Select mode:
   - Notify Only
   - PhishRakshak-Spam label
   - Trash High Risk Only
6. Click Scan Gmail Now.

## Android companion setup for SMS + call

Native Android is required for SMS/call auto-protection.

```bash
cd android-companion
gradle assembleDebug
```

Install debug APK:

```text
android-companion/app/build/outputs/apk/debug/app-debug.apk
```

On phone:

1. Open PhishRakshak Guard.
2. Enter backend URL.
   - Real phone example: `http://YOUR_MAC_IP:8000`
   - Emulator example: `http://10.0.2.2:8000`
3. Enter login email/password and tap Login and Save API Token.
4. Tap Make Default SMS App.
5. Tap Enable Call Screening.
6. Enable optional high-risk SMS delete only after testing.

## Test status completed here

- PHP syntax check: passed for updated backend files.
- Frontend ESLint: passed.
- Frontend production build: passed.

Android companion code is included as a native project. It requires Android SDK/Gradle to build on your machine.
