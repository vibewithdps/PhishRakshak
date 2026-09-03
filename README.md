# PhishRakshak - AI Scam Detector 🛡️

PhishRakshak is an advanced AI-powered scam detection platform designed to protect users from modern digital threats, including Phishing, Vishing, Smishing, and Malicious Apps. Built as an installable Progressive Web App (PWA), it offers a seamless experience on both desktop and mobile devices.

## 🚀 Key Features

*   **Multi-Vector Scam Detection:** Detects threats across SMS, URLs, Emails, APKs, and Call notes.
*   **Strong Rule-Based AI Engine:** Identifies 20+ categories of scams, including Bank/KYC fraud, OTP theft, Job scams, Loan app fraud, and Fake utility bills.
*   **Gmail Live Protection:** Connects securely via Google OAuth API to scan recent inbox messages and automatically label or trash high-risk emails.
*   **PWA Installable:** Can be installed natively on Android and iOS devices directly from the browser.
*   **Smart Risk Notification:** Delivers instant security alerts directly to the user when high-risk content is detected.
*   **Safe Domain Bypassing:** Intelligently ignores genuine trusted domains (e.g., GitHub, LinkedIn, Vercel) while actively scanning malicious content.

## 💻 Tech Stack

*   **Frontend:** React (Vite), Custom Responsive UI, PWA configuration
*   **Backend:** Laravel 9, Sanctum Authentication
*   **Database:** SQLite (Local) / PostgreSQL (Production)
*   **Integration:** Google Cloud Platform (Gmail API OAuth 2.0)

## 👤 Author

**Created by Dipendra Pratap Singh**  
*MCA Student • Atmiya University, Rajkot, Gujarat, India*

## 🛠️ Local Development Setup

### Backend (Laravel)
1. Clone the repository and navigate to the \`backend\` folder.
2. Run \`composer install\`.
3. Copy \`.env.example\` to \`.env\` and configure your local database (SQLite).
4. Run \`php artisan key:generate\`.
5. Run \`php artisan migrate:fresh\` to create tables.
6. Configure Google Cloud OAuth credentials in \`.env\`:
   - \`GOOGLE_CLIENT_ID\`
   - \`GOOGLE_CLIENT_SECRET\`
   - \`GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/api/gmail/callback\`
7. Start the server: \`php artisan serve\`.

### Frontend (React + Vite)
1. Navigate to the \`frontend\` folder.
2. Run \`npm install\`.
3. Start the Vite development server: \`npm run dev\`.
4. Open \`http://localhost:5174\` in your browser.

## ⚖️ License
This project is built for educational purposes and digital safety awareness.
