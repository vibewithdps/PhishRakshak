export function registerServiceWorker() {
  if (!("serviceWorker" in navigator)) {
    return;
  }

  const isLocalhost = ["localhost", "127.0.0.1"].includes(window.location.hostname);
  const isSecure = window.location.protocol === "https:" || isLocalhost;

  if (!isSecure) {
    console.info("PhishRakshak PWA: Service worker needs HTTPS or localhost.");
    return;
  }

  window.addEventListener("load", async () => {
    try {
      const registration = await navigator.serviceWorker.register("/sw.js");
      console.info("PhishRakshak PWA ready:", registration.scope);
    } catch (error) {
      console.error("PhishRakshak PWA registration failed:", error);
    }
  });
}
