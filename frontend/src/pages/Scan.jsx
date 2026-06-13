import { useEffect, useMemo, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import api from "../api/axios";
import dipuuuImage from "../assets/dipuuu.jpg";

const scanTypes = [
  {
    value: "sms",
    label: "SMS Message",
    hint: "Paste suspicious SMS, OTP/KYC message, UPI request or delivery text.",
    placeholder: "Example: Your SBI KYC is blocked. Click this link urgently...",
  },
  {
    value: "url",
    label: "Website URL",
    hint: "Paste any unknown website, payment link, short link or login URL.",
    placeholder: "Example: https://bit.ly/verify-kyc-now",
  },
  {
    value: "apk",
    label: "APK Detail",
    hint: "Paste APK name, message, source website or installation warning.",
    placeholder: "Example: Download SBI security update APK and install now...",
  },
  {
    value: "email",
    label: "Email Content",
    hint: "Paste email subject/body/sender details to detect phishing or fake invoice mail.",
    placeholder: "Example: Subject: Account suspended. Verify your mailbox password now...",
  },
  {
    value: "call",
    label: "Spam Call / Number Note",
    hint: "Paste caller number/name and what the caller said. PWA cannot auto-read call log.",
    placeholder: "Example: Unknown caller said bank account blocked, asked OTP/AnyDesk...",
  },
];

function getNotificationPermission() {
  if (!("Notification" in window)) {
    return "unsupported";
  }

  return Notification.permission;
}

function getInstallStatus() {
  return window.matchMedia?.("(display-mode: standalone)").matches || window.navigator.standalone === true;
}

function Scan() {
  const navigate = useNavigate();

  const [type, setType] = useState("sms");
  const [content, setContent] = useState("");
  const [result, setResult] = useState(null);
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const [notificationPermission, setNotificationPermission] = useState(getNotificationPermission());
  const [isInstalled, setIsInstalled] = useState(() => getInstallStatus());

  const selectedType = useMemo(
    () => scanTypes.find((item) => item.value === type) || scanTypes[0],
    [type]
  );

  useEffect(() => {
    const mediaQuery = window.matchMedia?.("(display-mode: standalone)");
    const handleDisplayModeChange = () => setIsInstalled(getInstallStatus());

    mediaQuery?.addEventListener?.("change", handleDisplayModeChange);

    return () => {
      mediaQuery?.removeEventListener?.("change", handleDisplayModeChange);
    };
  }, []);

  const enableNotifications = async () => {
    if (!("Notification" in window)) {
      setMessage("This browser does not support notifications.");
      setNotificationPermission("unsupported");
      return;
    }

    if (Notification.permission === "granted") {
      setNotificationPermission("granted");
      setMessage("Notifications already enabled ✅");
      return;
    }

    const permission = await Notification.requestPermission();
    setNotificationPermission(permission);

    if (permission === "granted") {
      setMessage("Protection alerts enabled ✅");
      await showProtectionNotification({
        is_phishing: false,
        category: "Protection Alerts Enabled",
        explanation: "PhishRakshak will notify you when a manually scanned item looks risky.",
      });
    } else {
      setMessage("Notification permission not allowed. Browser settings me enable karna padega.");
    }
  };

  const showProtectionNotification = async (scanResult) => {
    if (!("Notification" in window) || Notification.permission !== "granted") {
      return;
    }

    const title = scanResult.is_phishing
      ? "⚠️ PhishRakshak Alert: Scam Risk Found"
      : "✅ PhishRakshak: Looks Safe";

    const body = `${scanResult.category || "Scan Result"} - ${scanResult.explanation || "Scan completed."}`;

    try {
      const registration = await navigator.serviceWorker?.getRegistration?.();

      if (registration?.showNotification) {
        await registration.showNotification(title, {
          body,
          icon: "/icons/icon-192.png",
          badge: "/icons/icon-192.png",
          tag: "phishrakshak-scan-alert",
          renotify: true,
        });
        return;
      }

      new Notification(title, {
        body,
        icon: "/icons/icon-192.png",
      });
    } catch (error) {
      console.log("NOTIFICATION ERROR:", error.message);
    }
  };

  const handleScan = async (e) => {
    e.preventDefault();

    if (!content.trim()) {
      setMessage("Please enter content to scan.");
      return;
    }

    try {
      setLoading(true);
      setMessage("");
      setResult(null);

      const response = await api.post("/scan", {
        type,
        content,
      });

      const scanResult = response.data.data;
      setResult(scanResult);

      if (scanResult?.is_phishing) {
        await showProtectionNotification(scanResult);
      }
    } catch (error) {
      console.log("SCAN ERROR:", error.response?.data || error.message);
      const backendMessage =
  error.response?.data?.message ||
  error.response?.data?.error ||
  error.message ||
  "Scan failed.";

setMessage(backendMessage);
console.log("SCAN FULL ERROR:", error.response?.status, error.response?.data);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await api.post("/logout");
      localStorage.removeItem("token");
      navigate("/");
    } catch {
      localStorage.removeItem("token");
      navigate("/");
    }
  };

  const isDanger = result?.is_phishing;
  const confidencePercent = result ? Math.round(result.confidence * 100) : 0;

  return (
    <>
      <style>
        {`
          * {
            box-sizing: border-box;
          }

          body {
            margin: 0;
          }

          .scan-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 45%, #ecfeff 100%);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            flex-direction: column;
          }

          .site-header {
            width: 100%;
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 20;
          }

          .header-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
          }

          .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
          }

          .brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 17px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 15px;
            font-weight: 950;
            letter-spacing: 1px;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
          }

          .brand-title {
            margin: 0;
            font-size: 26px;
            color: #0f172a;
            letter-spacing: -0.5px;
          }

          .brand-subtitle {
            margin: 2px 0 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
          }

          .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
          }

          .nav-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 800;
            background: #dbeafe;
            padding: 10px 15px;
            border-radius: 999px;
            white-space: nowrap;
          }

          .logout-btn {
            padding: 10px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 800;
            white-space: nowrap;
          }

          .main-content {
            flex: 1;
            padding: 34px 28px 28px 28px;
          }

          .hero {
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 0.86fr 1.14fr;
            gap: 28px;
            align-items: start;
          }

          .left-panel {
            padding: 30px 10px;
          }

          .badge {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 13px;
          }

          .title {
            font-size: 48px;
            line-height: 1.05;
            margin: 22px 0 16px 0;
            color: #0f172a;
            letter-spacing: -1.5px;
          }

          .subtitle {
            color: #475569;
            font-size: 17px;
            line-height: 1.7;
            max-width: 500px;
          }

          .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 26px;
          }

          .stat-card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
          }

          .stat-number {
            margin: 0;
            font-size: 22px;
            color: #2563eb;
            letter-spacing: -0.3px;
          }

          .stat-text {
            margin: 6px 0 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
          }

          .protection-card {
            margin-top: 20px;
            background: rgba(15, 23, 42, 0.94);
            color: white;
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
          }

          .protection-title {
            margin: 0 0 8px 0;
            font-size: 22px;
          }

          .protection-text {
            margin: 0;
            color: #cbd5e1;
            line-height: 1.65;
            font-size: 14px;
            font-weight: 600;
          }

          .protection-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 16px 0;
          }

          .protection-pill {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 12px;
          }

          .protection-label {
            display: block;
            color: #93c5fd;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 4px;
          }

          .protection-value {
            font-weight: 900;
          }

          .notify-btn {
            border: none;
            background: #38bdf8;
            color: #082f49;
            font-weight: 950;
            border-radius: 14px;
            padding: 11px 14px;
            cursor: pointer;
            width: 100%;
          }

          .truth-note {
            margin-top: 12px;
            background: #fff7ed;
            color: #7c2d12;
            border: 1px solid #fed7aa;
            border-radius: 16px;
            padding: 14px;
            line-height: 1.6;
            font-size: 13px;
            font-weight: 750;
          }

          .scan-card {
            background: rgba(255, 255, 255, 0.96);
            padding: 28px;
            border-radius: 26px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
            border: 1px solid #e2e8f0;
          }

          .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            gap: 16px;
          }

          .card-title {
            margin: 0;
            font-size: 30px;
            color: #0f172a;
          }

          .card-desc {
            margin: 6px 0 0 0;
            color: #64748b;
            line-height: 1.5;
          }

          .shield-icon {
            width: 54px;
            height: 54px;
            background: #eff6ff;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            flex-shrink: 0;
          }

          .type-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 18px;
          }

          .type-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            border-radius: 16px;
            padding: 12px 10px;
            cursor: pointer;
            font-weight: 900;
            text-align: center;
            min-height: 70px;
          }

          .type-card.active {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: white;
            border-color: transparent;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
          }

          .type-small {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            opacity: 0.74;
            line-height: 1.35;
          }

          .label {
            display: block;
            margin-bottom: 8px;
            font-weight: 900;
            color: #334155;
          }

          .helper-text {
            margin: 0 0 10px 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.55;
            font-weight: 650;
          }

          .textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            font-size: 15px;
            outline: none;
            background: #f8fafc;
            min-height: 175px;
            padding: 15px;
            margin-bottom: 18px;
            resize: vertical;
            line-height: 1.6;
            font-family: inherit;
          }

          .textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            background: white;
          }

          .action-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
          }

          .scan-btn,
          .clear-btn {
            padding: 15px;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 900;
          }

          .scan-btn {
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: white;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
          }

          .clear-btn {
            background: #e2e8f0;
            color: #334155;
            padding-inline: 20px;
          }

          .scan-btn:disabled {
            cursor: not-allowed;
            opacity: 0.7;
          }

          .error {
            color: #dc2626;
            font-weight: 800;
            background: #fee2e2;
            padding: 12px;
            border-radius: 12px;
            margin-top: 16px;
          }

          .result-box {
            margin-top: 24px;
            border: 2px solid;
            padding: 20px;
            border-radius: 20px;
          }

          .result-top {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
            margin-bottom: 18px;
          }

          .result-title {
            margin: 0;
            color: #0f172a;
          }

          .result-small {
            margin: 5px 0 0 0;
            color: #64748b;
          }

          .risk-badge {
            color: white;
            padding: 9px 14px;
            border-radius: 999px;
            font-weight: 900;
            white-space: nowrap;
          }

          .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
          }

          .info-item {
            background: white;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
          }

          .info-label {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
            font-weight: 900;
            text-transform: uppercase;
          }

          .progress-outer {
            width: 100%;
            height: 11px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 18px;
          }

          .progress-inner {
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
          }

          .explain-box,
          .content-box,
          .tips-box,
          .trash-box {
            padding: 16px;
            border-radius: 15px;
            margin-bottom: 14px;
            border: 1px solid #e2e8f0;
          }

          .explain-box {
            background: white;
          }

          .content-box {
            background: #f8fafc;
            word-break: break-word;
          }

          .tips-box {
            background: #fff7ed;
            border-color: #fed7aa;
          }

          .trash-box {
            background: #fef2f2;
            border-color: #fecaca;
          }

          .section-heading {
            margin: 0 0 8px 0;
            color: #0f172a;
            font-size: 16px;
          }

          .explain-text {
            margin: 0;
            color: #334155;
            line-height: 1.6;
          }

          .tips-list {
            margin: 0;
            padding-left: 20px;
            color: #7c2d12;
            line-height: 1.8;
            font-weight: 700;
          }

          .trash-box .tips-list {
            color: #991b1b;
          }

          .site-footer {
            margin-top: 40px;
            background: #0f172a;
            color: white;
          }

          .footer-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 28px;
            display: grid;
            grid-template-columns: 1.3fr 0.9fr 0.8fr;
            gap: 22px;
            align-items: center;
          }

          .creator-box {
            display: flex;
            align-items: center;
            gap: 14px;
          }

          .creator-avatar {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: linear-gradient(135deg, #38bdf8, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            color: white;
            border: 3px solid rgba(255, 255, 255, 0.25);
            overflow: hidden;
            flex-shrink: 0;
          }

          .creator-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
          }

          .creator-title {
            margin: 0;
            font-size: 18px;
          }

          .creator-text {
            margin: 5px 0 0 0;
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
          }

          .footer-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 16px;
          }

          .footer-heading {
            margin: 0 0 8px 0;
            font-size: 15px;
            color: #e0f2fe;
          }

          .footer-list {
            margin: 0;
            padding-left: 18px;
            color: #cbd5e1;
            line-height: 1.8;
            font-size: 14px;
          }

          .copyright {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
            text-align: right;
          }

          @media (max-width: 1080px) {
            .type-grid {
              grid-template-columns: repeat(3, 1fr);
            }
          }

          @media (max-width: 950px) {
            .hero {
              grid-template-columns: 1fr;
            }

            .left-panel {
              padding: 10px 4px;
            }

            .title {
              font-size: 40px;
            }

            .subtitle {
              max-width: 100%;
            }

            .footer-inner {
              grid-template-columns: 1fr;
            }

            .copyright {
              text-align: left;
            }
          }

          @media (max-width: 650px) {
            .header-inner {
              padding: 14px 18px;
              flex-direction: column;
              align-items: flex-start;
            }

            .nav-actions {
              width: 100%;
              justify-content: space-between;
            }

            .main-content {
              padding: 22px 18px;
            }

            .brand-title {
              font-size: 24px;
            }

            .title {
              font-size: 34px;
            }

            .stats-row,
            .protection-grid,
            .info-grid,
            .action-row {
              grid-template-columns: 1fr;
            }

            .type-grid {
              grid-template-columns: repeat(2, 1fr);
            }

            .scan-card {
              padding: 20px;
              border-radius: 22px;
            }

            .card-header {
              align-items: flex-start;
            }

            .card-title {
              font-size: 25px;
            }

            .result-top {
              flex-direction: column;
              align-items: flex-start;
            }

            .nav-link,
            .logout-btn {
              padding: 9px 12px;
              font-size: 14px;
            }

            .footer-inner {
              padding: 22px 18px;
            }

            .creator-box {
              align-items: flex-start;
            }
          }

          @media (max-width: 420px) {
            .main-content {
              padding: 16px 12px;
            }

            .title {
              font-size: 30px;
            }

            .subtitle {
              font-size: 15px;
            }

            .scan-card {
              padding: 16px;
            }

            .shield-icon {
              display: none;
            }

            .creator-avatar {
              width: 54px;
              height: 54px;
              font-size: 24px;
            }
          }
        `}
      </style>

      <div className="scan-page">
        <header className="site-header">
          <div className="header-inner">
            <div className="brand-box">
              <div className="brand-icon">DPS</div>

              <div>
                <h2 className="brand-title">PhishRakshak</h2>
                <p className="brand-subtitle">PWA AI Scam Detector Dashboard</p>
              </div>
            </div>

            <div className="nav-actions">
              <Link className="nav-link" to="/protection">
                Protection
              </Link>

              <Link className="nav-link" to="/history">
                History
              </Link>

              <button className="logout-btn" onClick={handleLogout}>
                Logout
              </button>
            </div>
          </div>
        </header>

        <main className="main-content">
          <div className="hero">
            <div className="left-panel">
              <span className="badge">Installable PWA + Smart Alerts</span>

              <h1 className="title">Check SMS, URL, APK, Email or Spam-Call Notes</h1>

              <p className="subtitle">
                Paste Suspicious Content Manually. PhishRakshak will Detect Scam Patterns,
                Show a Risk Score, Save Scan History and Notify You when High-Risk Content is Found.
              </p>

              <div className="stats-row">
                <div className="stat-card">
                  <h3 className="stat-number">21+</h3>
                  <p className="stat-text">Scam Categories</p>
                </div>

                <div className="stat-card">
                  <h3 className="stat-number">PWA</h3>
                  <p className="stat-text">Installable App</p>
                </div>

                <div className="stat-card">
                  <h3 className="stat-number">Alerts</h3>
                  <p className="stat-text">Risk Notifications</p>
                </div>
              </div>

              <div className="protection-card">
                <h2 className="protection-title">Smart Protection Center</h2>
                <p className="protection-text">
                  Using This PWA, You Can Manually Scan Suspicious SMS, URLs, APK Details, Emails or Call Notes Anytime.
                  When a Scan Result Indicates High Risk, PhishRakshak Will Send You an Instant Notification Alerting You of the Potential Threat.
                </p>

                <div className="protection-grid">
                  <div className="protection-pill">
                    <span className="protection-label">PWA Status</span>
                    <span className="protection-value">{isInstalled ? "Installed ✅" : "Install Available 📲"}</span>
                  </div>

                  <div className="protection-pill">
                    <span className="protection-label">Notifications</span>
                    <span className="protection-value">
                      {notificationPermission === "granted"
                        ? "Enabled ✅"
                        : notificationPermission === "denied"
                          ? "Blocked ⚠️"
                          : notificationPermission === "unsupported"
                            ? "Unsupported"
                            : "Not Enabled"}
                    </span>
                  </div>
                </div>

                <button className="notify-btn" type="button" onClick={enableNotifications}>
                  Enable Protection Alerts
                </button>

                <div className="truth-note">
                  Auto Trash/Delete for Calls, SMS and Email Needs Native Android default-Handler Permissions or Gmail OAuth API.    
                  Currently, PhishRakshak PWA Provides Manual Scan and Risk Alerts Only. Always Verify from Official Apps/Websites and Report Suspicious Content to Cybercrime Portal.
                </div>
              </div>
            </div>

            <div className="scan-card">
              <div className="card-header">
                <div>
                  <h2 className="card-title">AI Scam Detector</h2>
                  <p className="card-desc">Choose Content Type and Paste Suspicious Details Below.</p>
                </div>

                <div className="shield-icon">🔍</div>
              </div>

              <form onSubmit={handleScan}>
                <label className="label">Scan Type</label>

                <div className="type-grid">
                  {scanTypes.map((item) => (
                    <button
                      key={item.value}
                      type="button"
                      className={type === item.value ? "type-card active" : "type-card"}
                      onClick={() => {
                        setType(item.value);
                        setResult(null);
                        setMessage("");
                      }}
                    >
                      {item.label}
                      <span className="type-small">{item.value.toUpperCase()}</span>
                    </button>
                  ))}
                </div>

                <label className="label">Content</label>
                <p className="helper-text">{selectedType.hint}</p>

                <textarea
                  className="textarea"
                  placeholder={selectedType.placeholder}
                  value={content}
                  onChange={(e) => setContent(e.target.value)}
                />

                <div className="action-row">
                  <button className="scan-btn" type="submit" disabled={loading}>
                    {loading ? "Scanning..." : "Scan Now"}
                  </button>

                  <button
                    className="clear-btn"
                    type="button"
                    onClick={() => {
                      setContent("");
                      setResult(null);
                      setMessage("");
                    }}
                  >
                    Clear
                  </button>
                </div>
              </form>

              {message && <p className="error">{message}</p>}

              {result && (
                <div
                  className="result-box"
                  style={{
                    borderColor: isDanger ? "#ef4444" : "#22c55e",
                    background: isDanger ? "#fff1f2" : "#f0fdf4",
                  }}
                >
                  <div className="result-top">
                    <div>
                      <h2 className="result-title">
                        {isDanger ? "⚠️ Phishing / Spam Detected" : "✅ Looks Safe"}
                      </h2>

                      <p className="result-small">Scan Completed Successfully</p>
                    </div>

                    <span
                      className="risk-badge"
                      style={{
                        background: isDanger ? "#dc2626" : "#16a34a",
                      }}
                    >
                      {isDanger ? "High Risk" : "Low Risk"}
                    </span>
                  </div>

                  <div className="info-grid">
                    <div className="info-item">
                      <span className="info-label">Type</span>
                      <strong>{result.type.toUpperCase()}</strong>
                    </div>

                    <div className="info-item">
                      <span className="info-label">Category</span>
                      <strong>{result.category}</strong>
                    </div>

                    <div className="info-item">
                      <span className="info-label">Confidence</span>
                      <strong>{confidencePercent}%</strong>
                    </div>
                  </div>

                  <div className="progress-outer">
                    <div
                      className="progress-inner"
                      style={{
                        width: `${confidencePercent}%`,
                        background: isDanger ? "#dc2626" : "#16a34a",
                      }}
                    ></div>
                  </div>

                  <div className="explain-box">
                    <h3 className="section-heading">Why This Result?</h3>
                    <p className="explain-text">{result.explanation}</p>
                  </div>

                  <div className="content-box">
                    <h3 className="section-heading">Scanned Content</h3>
                    <p>{result.content}</p>
                  </div>

                  {isDanger && (
                    <>
                      <div className="trash-box">
                        <h3 className="section-heading">Recommended Trash Action</h3>
                        <ul className="tips-list">
                          <li>SMS: Delete the Message or Report It as Spam; Do Not Open the <Link>Link</Link>.</li>
                          <li>Email: Block the Sender, Move the Email to Spam/Trash.</li>
                          <li>Call: Block the Number; Never Share or Install OTPs, PINs, or Remote Access Apps.</li>
                        </ul>
                      </div>

                      <div className="tips-box">
                        <h3 className="section-heading">Safety Tips</h3>
                        <ul className="tips-list">
                          <li>Do Not Click Unknown Links.</li>
                          <li>Never Share OTP, PIN, Password, CVV, or UPI PIN.</li>
                          <li>Verify from Official App or Website Only.</li>
                          <li>Report Suspicious Messages to the CyberCrime Portal.</li>
                        </ul>
                      </div>
                    </>
                  )}
                </div>
              )}
            </div>
          </div>
        </main>

        <footer className="site-footer">
          <div className="footer-inner">
            <div className="creator-box">
              <div className="creator-avatar">
                <img src={dipuuuImage} alt="Dipuuu" />
              </div>

              <div>
                <h3 className="creator-title">Created by Dipendra Pratap Singh</h3>
                <p className="creator-text">
                  MCA Student • Atmiya University, Rajkot, Gujarat, India
                </p>
              </div>
            </div>

            <div className="footer-card">
              <h4 className="footer-heading">Project Modules</h4>
              <ul className="footer-list">
                <li>AI Scam Detection</li>
                <li>PWA Install Support</li>
                <li>Risk Notifications</li>
              </ul>
            </div>

            <div className="copyright">
              © 2026 PhishRakshak. Built with <span>DPS</span> For Safer Digital Citizens.
              <br />
              Laravel • React • MySQL • PWA
            </div>
          </div>
        </footer>
      </div>
    </>
  );
}

export default Scan;
