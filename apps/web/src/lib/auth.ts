export function getWorkspaceStatus() {
  if (typeof window === "undefined") return null;
  return localStorage.getItem("workspace_status");
}

export function isLoggedIn() {
  if (typeof window === "undefined") return false;
  return !!localStorage.getItem("access_token");
}

export function setSession(accessToken: string, workspaceStatus: string) {
  if (typeof window === "undefined") return;
  localStorage.setItem("access_token", accessToken);
  localStorage.setItem("workspace_status", workspaceStatus);
}