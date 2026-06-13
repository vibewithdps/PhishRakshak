import api from "./axios";

export async function getGmailStatus() {
  const response = await api.get("/gmail/status");
  return response.data.data;
}

export async function getGmailAuthUrl() {
  const response = await api.get("/gmail/auth-url");
  return response.data.data.auth_url;
}

export async function updateGmailSettings(settings) {
  const response = await api.post("/gmail/settings", settings);
  return response.data;
}

export async function scanGmailInbox(payload = {}) {
  const response = await api.post("/gmail/scan-inbox", payload);
  return response.data.data;
}

export async function disconnectGmail() {
  const response = await api.delete("/gmail/disconnect");
  return response.data;
}
