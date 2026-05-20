export function setSession(accessToken: string, workspaceStatus: string) {
  localStorage.setItem("access_token", accessToken);
  localStorage.setItem("workspace_status", workspaceStatus);
}

export function clearSession() {
  localStorage.removeItem("access_token");
  localStorage.removeItem("workspace_status");
}

export function getWorkspaceStatus() {
  return localStorage.getItem("workspace_status");
}

export function isLoggedIn() {
  return !!localStorage.getItem("access_token");
}