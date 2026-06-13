import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import api from "../api/axios";
import dipuuuImage from "../assets/dipuuu.jpg";


function History() {
  const navigate = useNavigate();

  const [scans, setScans] = useState([]);
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(true);

  const fetchHistory = async () => {
    try {
      setLoading(true);
      const response = await api.get("/scan-history");
      setScans(response.data.data);
      setMessage("");
    } catch (error) {
      console.log("HISTORY ERROR:", error.response?.data || error.message);
      setMessage("Unable to load history. Please login again.");
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

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    fetchHistory();
  }, []);

  const totalScans = scans.length;
  const phishingCount = scans.filter((scan) => scan.is_phishing).length;
  const safeCount = scans.filter((scan) => !scan.is_phishing).length;

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

          .history-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 45%, #ecfeff 100%);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            flex-direction: column;
          }

          .site-header {
            width: 100%;
            background: rgba(255, 255, 255, 0.88);
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
            border-radius: 16px;
            background: linear-gradient(135deg, #1d4ed8, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: 900;
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
            padding: 34px 28px 34px 28px;
          }

          .history-wrapper {
            max-width: 1150px;
            margin: 0 auto;
          }

          .page-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
          }

          .badge {
            display: inline-block;
            background: #e0e7ff;
            color: #3730a3;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 13px;
          }

          .title {
            font-size: 42px;
            line-height: 1.1;
            margin: 16px 0 10px 0;
            color: #0f172a;
            letter-spacing: -1px;
          }

          .subtitle {
            color: #475569;
            font-size: 16px;
            line-height: 1.7;
            max-width: 650px;
            margin: 0;
          }

          .refresh-btn {
            padding: 12px 18px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: white;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 800;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.18);
          }

          .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
          }

          .stat-card {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
          }

          .stat-label {
            margin: 0 0 8px 0;
            color: #64748b;
            font-weight: 800;
            font-size: 13px;
            text-transform: uppercase;
          }

          .stat-number {
            margin: 0;
            color: #0f172a;
            font-size: 34px;
            letter-spacing: -1px;
          }

          .history-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e2e8f0;
            border-radius: 26px;
            padding: 24px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.11);
          }

          .loading,
          .error,
          .empty-box {
            padding: 18px;
            border-radius: 16px;
            font-weight: 800;
          }

          .loading {
            background: #eff6ff;
            color: #1d4ed8;
          }

          .error {
            background: #fee2e2;
            color: #dc2626;
          }

          .empty-box {
            background: #f8fafc;
            color: #475569;
            border: 1px dashed #cbd5e1;
            text-align: center;
            padding: 38px 18px;
          }

          .scan-list {
            display: grid;
            gap: 16px;
          }

          .scan-item {
            border: 1px solid #e2e8f0;
            border-left: 7px solid;
            border-radius: 20px;
            padding: 18px;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
          }

          .scan-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
          }

          .scan-title {
            margin: 0;
            font-size: 22px;
            color: #0f172a;
          }

          .scan-date {
            margin: 6px 0 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
          }

          .risk-badge {
            color: white;
            padding: 8px 13px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 13px;
            white-space: nowrap;
          }

          .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 14px;
          }

          .info-item {
            background: #f8fafc;
            padding: 13px;
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

          .info-value {
            color: #0f172a;
            font-weight: 800;
          }

          .explain-box,
          .content-box {
            padding: 14px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-top: 12px;
          }

          .explain-box {
            background: #ffffff;
          }

          .content-box {
            background: #f8fafc;
            word-break: break-word;
          }

          .section-title {
            margin: 0 0 8px 0;
            color: #0f172a;
            font-size: 15px;
          }

          .section-text {
            margin: 0;
            color: #334155;
            line-height: 1.6;
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
            grid-template-columns: 1.2fr 0.8fr 0.9fr;
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
  background: linear-gradient(135deg, #2563eb, #4338ca);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 18px;
  font-weight: 900;
  letter-spacing: 1px;
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

          @media (max-width: 900px) {
            .page-top {
              flex-direction: column;
            }

            .stats-grid {
              grid-template-columns: 1fr;
            }

            .info-grid {
              grid-template-columns: 1fr;
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
              font-size: 32px;
            }

            .history-card {
              padding: 18px;
              border-radius: 22px;
            }

            .scan-top {
              flex-direction: column;
            }

            .footer-inner {
              padding: 22px 18px;
            }
          }
        `}
      </style>

      <div className="history-page">
        <header className="site-header">
          <div className="header-inner">
            <div className="brand-box">
              <div className="brand-icon">DPS</div>

              <div>
                <h2 className="brand-title">PhishRakshak</h2>
                <p className="brand-subtitle">AI Scam Detector Dashboard</p>
              </div>
            </div>

            <div className="nav-actions">
              <Link className="nav-link" to="/scan">
                Scan
              </Link>

              <button className="logout-btn" onClick={handleLogout}>
                Logout
              </button>
            </div>
          </div>
        </header>

        <main className="main-content">
          <div className="history-wrapper">
            <div className="page-top">
              <div>
                <span className="badge">User Scan Records</span>
                <h1 className="title">Scan History</h1>
                <p className="subtitle">
                  Review All Your Previous SMS, URL, and APK Scans with Scam Category,
                  Confidence Score, and Explanation.
                </p>
              </div>

              <button className="refresh-btn" onClick={fetchHistory}>
                Refresh History
              </button>
            </div>

            <div className="stats-grid">
              <div className="stat-card">
                <p className="stat-label">Total Scans</p>
                <h2 className="stat-number">{totalScans}</h2>
              </div>

              <div className="stat-card">
                <p className="stat-label">Phishing Found</p>
                <h2 className="stat-number">{phishingCount}</h2>
              </div>

              <div className="stat-card">
                <p className="stat-label">Safe Results</p>
                <h2 className="stat-number">{safeCount}</h2>
              </div>
            </div>

            <div className="history-card">
              {loading && <div className="loading">Loading Scan History...</div>}

              {message && <div className="error">{message}</div>}

              {!loading && !message && scans.length === 0 && (
                <div className="empty-box">
                  <h2>No Scan History Found</h2>
                  <p>Go To Scan Page and Test Your First Suspicious Message.</p>
                </div>
              )}

              {!loading && !message && scans.length > 0 && (
                <div className="scan-list">
                  {scans.map((scan) => {
                    const isDanger = scan.is_phishing;
                    const confidence = Math.round(scan.confidence * 100);

                    return (
                      <div
                        className="scan-item"
                        key={scan.id}
                        style={{
                          borderLeftColor: isDanger ? "#dc2626" : "#16a34a",
                        }}
                      >
                        <div className="scan-top">
                          <div>
                            <h3 className="scan-title">
                              {isDanger ? "⚠️ Phishing Detected" : "✅ Looks Safe"}
                            </h3>
                            <p className="scan-date">
                              {new Date(scan.created_at).toLocaleString()}
                            </p>
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
                            <span className="info-value">
                              {scan.type.toUpperCase()}
                            </span>
                          </div>

                          <div className="info-item">
                            <span className="info-label">Category</span>
                            <span className="info-value">{scan.category}</span>
                          </div>

                          <div className="info-item">
                            <span className="info-label">Confidence</span>
                            <span className="info-value">{confidence}%</span>
                          </div>
                        </div>

                        <div className="explain-box">
                          <h4 className="section-title">Explanation</h4>
                          <p className="section-text">{scan.explanation}</p>
                        </div>

                        <div className="content-box">
                          <h4 className="section-title">Scanned Content</h4>
                          <p className="section-text">{scan.content}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        </main>

        <footer className="site-footer">
          <div className="footer-inner">
            <div className="creator-box">
              <div className="creator-avatar">
                <img src={dipuuuImage} alt="DPS" />
              </div>

              <div>
                <h3 className="creator-title">Created by Dipendra Pratap Singh</h3>
                <p className="creator-text">
                  MCA Student •  Atmiya University, Rajkot, Gujarat, India
                </p>
              </div>
            </div>

            <div className="footer-card">
              <h4 className="footer-heading">Project Modules</h4>
              <ul className="footer-list">
                <li>AI Scam Detection</li>
                <li>Hindi + English Support</li>
                <li>Scan History Tracking</li>
              </ul>
            </div>

            <div className="copyright">
              © 2026 PhishRakshak. Built with DPS for safer digital citizens.
              <br />
              Laravel • React • MySQL • AI Rules Engine
            </div>
          </div>
        </footer>
      </div>
    </>
  );
}

export default History;