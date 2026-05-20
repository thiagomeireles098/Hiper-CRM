const API_URL = process.env.NEXT_PUBLIC_API_URL!;

export async function apiFetch(path: string, opts: RequestInit = {}) {
  const token = typeof window !== "undefined" ? localStorage.getItem("access_token") : null;

  const res = await fetch(`${API_URL}${path}`, {
    ...opts,
    headers: {
      "Content-Type": "application/json",
      ...(opts.headers ?? {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {})
    },
    credentials: "include"
  });

  // If access expired, try refresh once
  if (res.status === 401) {
    const rr = await fetch(`${API_URL}/api/auth/refresh`, {
      method: "POST",
      credentials: "include"
    });
    if (rr.ok) {
      const data = await rr.json();
      localStorage.setItem("access_token", data.accessToken);
      return apiFetch(path, opts);
    }
  }

  return res;
}