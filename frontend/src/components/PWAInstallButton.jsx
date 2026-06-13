import { useEffect, useState } from "react";

function isRunningStandalone() {
  return (
    window.matchMedia?.("(display-mode: standalone)").matches ||
    window.navigator.standalone === true
  );
}

function PWAInstallButton() {
  const [installPrompt, setInstallPrompt] = useState(null);
  const [isInstalled, setIsInstalled] = useState(() => isRunningStandalone());
  const [message, setMessage] = useState("");

  useEffect(() => {
    const handleBeforeInstallPrompt = (event) => {
      event.preventDefault();
      setInstallPrompt(event);
      setMessage("");
    };

    const handleInstalled = () => {
      setInstallPrompt(null);
      setIsInstalled(true);
      setMessage("Installed ✅");
    };

    window.addEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
    window.addEventListener("appinstalled", handleInstalled);

    return () => {
      window.removeEventListener("beforeinstallprompt", handleBeforeInstallPrompt);
      window.removeEventListener("appinstalled", handleInstalled);
    };
  }, []);

  const handleInstall = async () => {
    if (!installPrompt) {
      setMessage("Chrome menu → Install app / Add to Home screen se install karo.");
      return;
    }

    installPrompt.prompt();
    const choice = await installPrompt.userChoice;

    if (choice.outcome === "accepted") {
      setMessage("Installing...");
    } else {
      setMessage("Install cancelled");
    }

    setInstallPrompt(null);
  };

  if (isInstalled) {
    return null;
  }

  return (
    <>
      <style>
        {`
          .pwa-install-floating {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            pointer-events: none;
          }

          .pwa-install-btn {
            pointer-events: auto;
            border: none;
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            box-shadow: 0 16px 35px rgba(37, 99, 235, 0.32);
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 900;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
          }

          .pwa-install-msg {
            pointer-events: auto;
            max-width: 260px;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 14px;
            padding: 10px 12px;
            font-size: 12px;
            line-height: 1.45;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.25);
          }

          @media (max-width: 520px) {
            .pwa-install-floating {
              right: 12px;
              bottom: 12px;
            }

            .pwa-install-btn {
              padding: 11px 14px;
              font-size: 13px;
            }
          }
        `}
      </style>

      <div className="pwa-install-floating">
        {message && <div className="pwa-install-msg">{message}</div>}
        <button className="pwa-install-btn" type="button" onClick={handleInstall}>
          📲 Install App
        </button>
      </div>
    </>
  );
}

export default PWAInstallButton;
