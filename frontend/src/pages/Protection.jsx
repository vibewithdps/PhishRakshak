import { useCallback, useEffect, useMemo, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import {
  disconnectGmail,
  getGmailAuthUrl,
  getGmailStatus,
  scanGmailInbox,
  updateGmailSettings,
} from "../api/protectionApi";

const protectionModes = [
  {
    value: "notify",
    label: "Notify Only",
    desc: "Safe mode: PhishRakshak only detects risky mail and saves the result in history.",
  },
  {
    value: "label",
    label: "Move to PhishRakshak-Spam Label",
    desc: "Suspicious Gmail messages get a PhishRakshak-Spam label, but are not deleted.",
  },
  {
    value: "trash_high_risk",
    label: "Trash High Risk Only",
    desc: "Only very high-risk phishing mail is moved to Gmail Trash based on your threshold.",
  },
];

function getStatusText(params) {
  if (params.get("gmail") === "connected") {
    return "Gmail connected successfully ✅";
  }

  if (params.get("gmail") === "failed") {
    return `Gmail connection failed: ${params.get("reason") || "unknown error"}`;
  }

  return "";
}

function Protection() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [gmailStatus, setGmailStatus] = useState(null);
  const [settings, setSettings] = useState({
    protection_mode: "notify",
    trash_threshold: 0.9,
    scan_limit: 10,
  });
  const [scanResults, setScanResults] = useState([]);
  const [message, setMessage] = useState(getStatusText(searchParams));
  const [loading, setLoading] = useState(false);
  const [scanLoading, setScanLoading] = useState(false);

  const selectedMode = useMemo(
    () => protectionModes.find((mode) => mode.value === settings.protection_mode) || protectionModes[0],
    [settings.protection_mode]
  );

  const loadGmailStatus = useCallback(async () => {
    try {
      const data = await getGmailStatus();
      setGmailStatus(data);
      setSettings({
        protection_mode: data.protection_mode || "notify",
        trash_threshold: data.trash_threshold || 0.9,
        scan_limit: data.scan_limit || 10,
      });
    } catch (error) {
      setMessage(error.response?.data?.message || "Unable to load Gmail protection status.");
    }
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      loadGmailStatus();
    }, 0);

    return () => window.clearTimeout(timer);
  }, [loadGmailStatus]);

  const handleLogout = async () => {
    localStorage.removeItem("token");
    navigate("/");
  };

  const handleConnectGmail = async () => {
    try {
      setLoading(true);
      setMessage("");
      const authUrl = await getGmailAuthUrl();
      window.location.href = authUrl;
    } catch (error) {
      setMessage(error.response?.data?.message || "Gmail connect URL failed. Check backend .env Google keys.");
    } finally {
      setLoading(false);
    }
  };

  const handleSaveSettings = async () => {
    try {
      setLoading(true);
      setMessage("");
      await updateGmailSettings(settings);
      setMessage("Mail protection settings saved ✅");
      await loadGmailStatus();
    } catch (error) {
      setMessage(error.response?.data?.message || "Settings update failed.");
    } finally {
      setLoading(false);
    }
  };

  const handleScanInbox = async () => {
    try {
      setScanLoading(true);
      setMessage("");
      const data = await scanGmailInbox({ limit: settings.scan_limit });
      setScanResults(data);
      setMessage(`Gmail scan completed. ${data.length} messages checked ✅`);
      await loadGmailStatus();
    } catch (error) {
      setMessage(error.response?.data?.message || "Gmail scan failed.");
    } finally {
      setScanLoading(false);
    }
  };

  const handleDisconnect = async () => {
    try {
      setLoading(true);
      await disconnectGmail();
      setScanResults([]);
      setMessage("Gmail disconnected ✅");
      await loadGmailStatus();
    } catch (error) {
      setMessage(error.response?.data?.message || "Gmail disconnect failed.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <style>
        {`
          * { box-sizing: border-box; }
          body { margin: 0; }
          .protection-page {
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 42%, #ecfeff 100%);
            color: #0f172a;
          }
          .site-header {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
          }
          .header-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
          }
          .brand-box { display: flex; align-items: center; gap: 12px; }
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
          .brand-title { margin: 0; font-size: 25px; letter-spacing: -0.5px; }
          .brand-subtitle { margin: 2px 0 0 0; color: #64748b; font-size: 13px; font-weight: 700; }
          .nav-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
          .nav-link, .logout-btn {
            border: none;
            border-radius: 999px;
            padding: 10px 15px;
            font-weight: 900;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
          }
          .nav-link { color: #2563eb; background: #dbeafe; }
          .logout-btn { color: white; background: #ef4444; }
          .main-content { max-width: 1180px; margin: 0 auto; padding: 34px 24px 46px; }
          .hero-card {
            border-radius: 30px;
            padding: 30px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.98), rgba(79, 70, 229, 0.96));
            color: white;
            box-shadow: 0 25px 70px rgba(37, 99, 235, 0.24);
            display: grid;
            grid-template-columns: 1.5fr 0.9fr;
            gap: 24px;
            align-items: center;
          }
          .hero-badge {
            display: inline-flex;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 8px 13px;
            border-radius: 999px;
            font-weight: 900;
            margin-bottom: 14px;
          }
          .hero-title { font-size: clamp(32px, 5vw, 56px); margin: 0; line-height: 1.02; letter-spacing: -1.5px; }
          .hero-text { color: #dbeafe; font-size: 16px; line-height: 1.7; font-weight: 650; max-width: 780px; }
          .hero-mini-grid { display: grid; gap: 12px; }
          .mini-card {
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.22);
            padding: 16px;
            border-radius: 20px;
          }
          .mini-card h3 { margin: 0 0 6px; font-size: 17px; }
          .mini-card p { margin: 0; color: #dbeafe; line-height: 1.5; font-size: 13px; font-weight: 650; }
          .message {
            margin: 22px 0 0;
            padding: 14px 16px;
            border-radius: 16px;
            background: #fff7ed;
            color: #7c2d12;
            border: 1px solid #fed7aa;
            font-weight: 850;
          }
          .section-grid {
            margin-top: 26px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
          }
          .panel {
            background: rgba(255,255,255,0.96);
            border: 1px solid #e2e8f0;
            border-radius: 26px;
            padding: 24px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
          }
          .panel.wide { grid-column: span 2; }
          .panel.full { grid-column: 1 / -1; }
          .panel-title { margin: 0 0 8px; font-size: 24px; letter-spacing: -0.5px; }
          .panel-text { margin: 0 0 18px; color: #64748b; line-height: 1.65; font-weight: 650; }
          .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 999px;
            font-weight: 950;
            margin-bottom: 14px;
          }
          .connected { background: #dcfce7; color: #166534; }
          .disconnected { background: #fee2e2; color: #991b1b; }
          .form-grid { display: grid; gap: 14px; }
          .label { display: block; font-weight: 900; color: #334155; margin-bottom: 7px; }
          .select, .input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 12px;
            font: inherit;
            font-weight: 750;
            background: #f8fafc;
            color: #0f172a;
          }
          .mode-desc {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            border-radius: 16px;
            padding: 12px;
            line-height: 1.55;
            font-weight: 750;
          }
          .button-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
          .primary-btn, .secondary-btn, .danger-btn {
            border: none;
            border-radius: 14px;
            padding: 13px 16px;
            font-weight: 950;
            cursor: pointer;
          }
          .primary-btn { background: linear-gradient(135deg, #2563eb, #4f46e5); color: white; }
          .secondary-btn { background: #e0f2fe; color: #075985; }
          .danger-btn { background: #fee2e2; color: #991b1b; }
          .primary-btn:disabled, .secondary-btn:disabled, .danger-btn:disabled { opacity: 0.65; cursor: not-allowed; }
          .device-card {
            display: grid;
            gap: 12px;
          }
          .step {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px;
          }
          .step strong { display: block; margin-bottom: 6px; }
          .step span { color: #64748b; line-height: 1.55; font-weight: 650; }
          .truth-note {
            margin-top: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #7c2d12;
            border-radius: 16px;
            padding: 14px;
            font-weight: 800;
            line-height: 1.6;
          }
          .result-list { display: grid; gap: 14px; margin-top: 18px; }
          .mail-result {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 18px;
            padding: 16px;
          }
          .mail-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
          .mail-subject { margin: 0; font-size: 18px; }
          .risk-badge { border-radius: 999px; padding: 8px 12px; color: white; font-weight: 950; white-space: nowrap; }
          .risk { background: #dc2626; }
          .safe { background: #16a34a; }
          .mail-meta { color: #475569; font-weight: 700; margin: 8px 0; line-height: 1.5; }
          .mail-explain { color: #334155; line-height: 1.6; margin: 0; }
          @media (max-width: 980px) {
            .hero-card, .section-grid { grid-template-columns: 1fr; }
            .panel.wide, .panel.full { grid-column: auto; }
          }
          @media (max-width: 640px) {
            .header-inner { align-items: flex-start; flex-direction: column; }
            .main-content { padding: 22px 14px 34px; }
            .hero-card, .panel { padding: 20px; border-radius: 22px; }
            .mail-top { align-items: flex-start; flex-direction: column; }
          }
        `}
      </style>

      <div className="protection-page">
        <header className="site-header">
          <div className="header-inner">
            <div className="brand-box">
              <div className="brand-icon">DPS</div>
              <div>
                <h2 className="brand-title">PhishRakshak</h2>
                <p className="brand-subtitle">Mail + SMS + Call Protection</p>
              </div>
            </div>

            <div className="nav-actions">
              <Link className="nav-link" to="/scan">Scan</Link>
              <Link className="nav-link" to="/history">History</Link>
              <button className="logout-btn" type="button" onClick={handleLogout}>Logout</button>
            </div>
          </div>
        </header>

        <main className="main-content">
          <section className="hero-card">
            <div>
              <span className="hero-badge">Smart Auto Protection Phase</span>
              <h1 className="hero-title">Mail, SMS and Spam-Call Protection Dashboard</h1>
              <p className="hero-text">
                The Gmail Protection Backend will Operate Using OAuth. Code for an Android Companion App has been Added for SMS and Calls, Because a Browser or PWA cannot Directly Control the Phone's Inbox or Call Screen..
              </p>
            </div>
            <div className="hero-mini-grid">
              <div className="mini-card"><h3>📧 Gmail</h3><p>OAuth Connect, Scan Latest Mail, Label or High-Risk Trash Mode.</p></div>
              <div className="mini-card"><h3>💬 SMS</h3><p>Incoming SMS Scan/Quarantine after Native Default SMS Role.</p></div>
              <div className="mini-card"><h3>📞 Calls</h3><p>Risky Calls Reject/Silence from Android CallScreeningService.</p></div>
            </div>
          </section>

          {message && <div className="message">{message}</div>}

          <section className="section-grid">
            <div className="panel wide">
              <h2 className="panel-title">📧 Gmail Live Protection</h2>
              <p className="panel-text">
                Connect Gmail, Then PhishRakshak will Scan Recent Inbox Messages Through Laravel Backend and Gmail API.
              </p>

              <div className={`status-pill ${gmailStatus?.connected ? "connected" : "disconnected"}`}>
                {gmailStatus?.connected ? "Connected ✅" : "Not Connected ⚠️"}
                {gmailStatus?.google_email ? ` • ${gmailStatus.google_email}` : ""}
              </div>

              {gmailStatus?.connected ? (
                <div className="form-grid">
                  <div>
                    <label className="label" htmlFor="protectionMode">Protection Mode</label>
                    <select
                      id="protectionMode"
                      className="select"
                      value={settings.protection_mode}
                      onChange={(event) => setSettings((prev) => ({ ...prev, protection_mode: event.target.value }))}
                    >
                      {protectionModes.map((mode) => (
                        <option key={mode.value} value={mode.value}>{mode.label}</option>
                      ))}
                    </select>
                  </div>

                  <div className="mode-desc">{selectedMode.desc}</div>

                  <div>
                    <label className="label" htmlFor="threshold">Trash Threshold</label>
                    <input
                      id="threshold"
                      className="input"
                      type="number"
                      step="0.01"
                      min="0.5"
                      max="0.99"
                      value={settings.trash_threshold}
                      onChange={(event) => setSettings((prev) => ({ ...prev, trash_threshold: Number(event.target.value) }))}
                    />
                  </div>

                  <div>
                    <label className="label" htmlFor="scanLimit">Mail Scan Limit</label>
                    <input
                      id="scanLimit"
                      className="input"
                      type="number"
                      min="1"
                      max="25"
                      value={settings.scan_limit}
                      onChange={(event) => setSettings((prev) => ({ ...prev, scan_limit: Number(event.target.value) }))}
                    />
                  </div>

                  <div className="button-row">
                    <button className="primary-btn" type="button" onClick={handleSaveSettings} disabled={loading}>Save Settings</button>
                    <button className="secondary-btn" type="button" onClick={handleScanInbox} disabled={scanLoading}>{scanLoading ? "Scanning..." : "Scan Gmail Now"}</button>
                    <button className="danger-btn" type="button" onClick={handleDisconnect} disabled={loading}>Disconnect Gmail</button>
                  </div>

                  <p className="panel-text">
                    Last scan: {gmailStatus.last_scan_at || "Not scanned yet"}
                  </p>
                </div>
              ) : (
                <div>
                  <button className="primary-btn" type="button" onClick={handleConnectGmail} disabled={loading}>
                    {loading ? "Opening Google..." : "Connect Gmail"}
                  </button>
                  <div className="truth-note">
                    It is Necessary to Set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in the Backend.
                  </div>
                </div>
              )}
            </div>

            <div className="panel">
              <h2 className="panel-title">🔐 Safe Default</h2>
              <p className="panel-text">
                Direct Auto-Deletion is Risky. Therefore, The Default Mode is 'Notify Only'. The 'High-Risk Trash' Feature must be Enabled Manually Via Settings.
              </p>
              <div className="step"><strong>0.50 - 0.74</strong><span>Suspicious: Notify Only.</span></div>
              <div className="step"><strong>0.75 - 0.89</strong><span>Strong Warning: Label Recommended.</span></div>
              <div className="step"><strong>0.90+</strong><span>High Risk: Trash Mode Allowed.</span></div>
            </div>

            <div className="panel">
              <h2 className="panel-title">💬 SMS Auto Protection</h2>
              <div className="device-card">
                <div className="step"><strong>Step 1</strong><span>Install Android Companion App from Android-Companion Folder.</span></div>
                <div className="step"><strong>Step 2</strong><span>Make It Default SMS App from Android Role Prompt.</span></div>
                <div className="step"><strong>Step 3</strong><span>Incoming SMS will be Scanned and Risky SMS will be Quarantined/Flagged.</span></div>
              </div>
            </div>

            <div className="panel">
              <h2 className="panel-title">📞 Call Auto Protection</h2>
              <div className="device-card">
                <div className="step"><strong>Step 1</strong><span>Enable Call Screening Role in Android Companion.</span></div>
                <div className="step"><strong>Step 2</strong><span>Unknown Caller Number Goes to Backend /Api/Scan as Call Type.</span></div>
                <div className="step"><strong>Step 3</strong><span>High-Risk Calls can be Rejected/Silenced and User Gets Notification.</span></div>
              </div>
            </div>

            <div className="panel">
              <h2 className="panel-title">📲 PWA App Role</h2>
              <p className="panel-text">
                PWA Dashboard is for Manual Scan, Gmail Settings, History and Installable Web-App Experience.
                SMS/Call Hardware-Level Access Android Companion Layer Handle.
              </p>
              <Link className="nav-link" to="/scan">Open the Manual Scan.</Link>
            </div>

            {scanResults.length > 0 && (
              <div className="panel full">
                <h2 className="panel-title">Latest Gmail Scan Results</h2>
                <div className="result-list">
                  {scanResults.map((item) => (
                    <div className="mail-result" key={item.gmail_message_id}>
                      <div className="mail-top">
                        <h3 className="mail-subject">{item.subject || "No subject"}</h3>
                        <span className={`risk-badge ${item.is_phishing ? "risk" : "safe"}`}>
                          {item.is_phishing ? "Risk" : "Safe"} • {Math.round(item.confidence * 100)}%
                        </span>
                      </div>
                      <p className="mail-meta">From: {item.from || "Unknown"}</p>
                      <p className="mail-meta">Action: {item.action}</p>
                      <p className="mail-explain">{item.explanation}</p>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </section>
        </main>
      </div>
    </>
  );
}

export default Protection;
