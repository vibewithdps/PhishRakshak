import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import api from "../api/axios";
import dipuuuImage from "../assets/dipuuu.jpg";

function Register() {
  const navigate = useNavigate();

  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
  });

  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setForm({
      ...form,
      [e.target.name]: e.target.value,
    });
  };

  const handleRegister = async (e) => {
    e.preventDefault();

    if (!form.name || !form.email || !form.password) {
      setMessage("Please fill all fields.");
      return;
    }

    try {
      setLoading(true);
      setMessage("");

      const response = await api.post("/register", form);

      localStorage.setItem("token", response.data.token);
      navigate("/scan");
    } catch (error) {
      console.log("REGISTER ERROR:", error.response?.data || error.message);

      if (error.response?.data?.errors?.email) {
        setMessage("This email is already registered. Please login or use another email.");
      } else {
        setMessage(error.response?.data?.message || "Registration failed.");
      }
    } finally {
      setLoading(false);
    }
  };

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

          .auth-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 45%, #ecfeff 100%);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            display: flex;
            flex-direction: column;
          }

          .auth-header {
            width: 100%;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid #e2e8f0;
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

          .header-pill {
            background: #dcfce7;
            color: #15803d;
            padding: 10px 16px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 14px;
          }

          .auth-main {
            flex: 1;
            max-width: 1180px;
            width: 100%;
            margin: 0 auto;
            padding: 40px 28px;
            display: grid;
            grid-template-columns: 1fr 0.9fr;
            gap: 34px;
            align-items: center;
          }

          .hero-panel {
            padding: 20px 6px;
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

          .hero-title {
            font-size: 54px;
            line-height: 1.05;
            margin: 22px 0 16px 0;
            color: #0f172a;
            letter-spacing: -1.6px;
          }

          .hero-text {
            color: #475569;
            font-size: 17px;
            line-height: 1.8;
            max-width: 550px;
            margin: 0;
          }

          .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-top: 28px;
            max-width: 620px;
          }

          .feature-card {
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.07);
          }

          .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
          }

          .feature-title {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
          }

          .feature-text {
            margin: 6px 0 0 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
            font-weight: 600;
          }

          .auth-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.14);
          }

          .card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
          }

          .card-title {
            margin: 0;
            font-size: 32px;
            color: #0f172a;
            letter-spacing: -0.6px;
          }

          .card-desc {
            margin: 7px 0 0 0;
            color: #64748b;
            font-weight: 600;
          }

          .card-ai {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, #ecfdf5, #dcfce7);
            color: #15803d;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
            flex-shrink: 0;
          }

          .label {
            display: block;
            margin-bottom: 8px;
            font-weight: 900;
            color: #334155;
          }

          .input {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border-radius: 15px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            outline: none;
            font-size: 15px;
          }

          .register-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #16a34a, #2563eb);
            color: white;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 14px 28px rgba(22, 163, 74, 0.2);
          }

          .register-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
          }

          .error {
            margin: 16px 0 0 0;
            color: #dc2626;
            background: #fee2e2;
            padding: 12px;
            border-radius: 14px;
            font-weight: 800;
          }

          .switch-text {
            margin: 20px 0 0 0;
            color: #475569;
            text-align: center;
            font-weight: 600;
          }

          .switch-link {
            color: #2563eb;
            font-weight: 900;
            text-decoration: none;
          }

          .mini-profile {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 14px;
          }

          .profile-img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #dbeafe;
          }

          .profile-title {
            margin: 0;
            color: #0f172a;
            font-weight: 900;
          }

          .profile-text {
            margin: 4px 0 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
          }

          .auth-footer {
            background: #0f172a;
            color: white;
            margin-top: auto;
          }

          .footer-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 22px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            color: #cbd5e1;
            font-size: 14px;
          }

          @media (max-width: 950px) {
            .auth-main {
              grid-template-columns: 1fr;
            }

            .hero-title {
              font-size: 42px;
            }
          }

          @media (max-width: 650px) {
            .header-inner {
              padding: 14px 18px;
              flex-direction: column;
              align-items: flex-start;
            }

            .auth-main {
              padding: 24px 18px;
            }

            .hero-title {
              font-size: 34px;
            }

            .feature-grid {
              grid-template-columns: 1fr;
            }

            .auth-card {
              padding: 22px;
              border-radius: 24px;
            }

            .card-title {
              font-size: 27px;
            }

            .footer-inner {
              flex-direction: column;
              align-items: flex-start;
              padding: 20px 18px;
            }
          }
        `}
      </style>

      <div className="auth-page">
        <header className="auth-header">
          <div className="header-inner">
            <div className="brand-box">
              <div className="brand-icon">DPS</div>

              <div>
                <h2 className="brand-title">PhishRakshak</h2>
                <p className="brand-subtitle">AI Scam Detector Dashboard</p>
              </div>
            </div>

            <div className="header-pill">Create Account</div>
          </div>
        </header>

        <main className="auth-main">
          <section className="hero-panel">
            <span className="badge">Join PhishRakshak</span>

            <h1 className="hero-title">Start Detecting Scam Messages with One Secure Account</h1>

            <p className="hero-text">
              Create Your Account and Scan Suspicious Messages, Links, and APK Details.
              Your Scan History will be Saved Safely for Review.
            </p>

            <div className="feature-grid">
              <div className="feature-card">
                <div className="feature-icon">🛡️</div>
                <h3 className="feature-title">Safe Scanning</h3>
                <p className="feature-text">Detect Risky Content Instantly.</p>
              </div>

              <div className="feature-card">
                <div className="feature-icon">🇮🇳</div>
                <h3 className="feature-title">India Focus</h3>
                <p className="feature-text">KYC, UPI, Loan, Bill Scams.</p>
              </div>

              <div className="feature-card">
                <div className="feature-icon">📁</div>
                <h3 className="feature-title">Records</h3>
                <p className="feature-text">Scan History in One Place.</p>
              </div>
            </div>
          </section>

          <section className="auth-card">
            <div className="card-top">
              <div>
                <h2 className="card-title">Create Account</h2>
                <p className="card-desc">Register to Start Using PhishRakshak.</p>
              </div>

              <div className="card-ai">NEW</div>
            </div>

            <form onSubmit={handleRegister}>
              <label className="label">Full Name</label>
              <input
                className="input"
                type="text"
                name="name"
                placeholder="Enter your name"
                value={form.name}
                onChange={handleChange}
              />

              <label className="label">Email Address</label>
              <input
                className="input"
                type="email"
                name="email"
                placeholder="Enter your email"
                value={form.email}
                onChange={handleChange}
              />

              <label className="label">Password</label>
              <input
                className="input"
                type="password"
                name="password"
                placeholder="Create password"
                value={form.password}
                onChange={handleChange}
              />

              <button className="register-btn" type="submit" disabled={loading}>
                {loading ? "Creating account..." : "Register"}
              </button>
            </form>

            {message && <p className="error">{message}</p>}

            <p className="switch-text">
              Already Have Account?{" "}
              <Link className="switch-link" to="/">
                Login
              </Link>
            </p>

            <div className="mini-profile">
              <img className="profile-img" src={dipuuuImage} alt="Dipendra Pratap Singh" />
              <div>
                <p className="profile-title">Created by Dipendra Pratap Singh</p>
                <p className="profile-text">MCA Student • Atmiya University, Rajkot, Gujarat, India</p>
              </div>
            </div>
          </section>
        </main>

        <footer className="auth-footer">
          <div className="footer-inner">
            <span>© 2026 PhishRakshak • Laravel • React • MySQL</span>
            <span>Built with DPS for Safer Digital Citizens.</span>
          </div>
        </footer>
      </div>
    </>
  );
}

export default Register;