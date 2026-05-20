"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { setSession } from "@/lib/auth";

export default function Login() {
  const [email, setEmail] = useState("admin@demo.com");
  const [password, setPassword] = useState("admin123");
  const [err, setErr] = useState<string | null>(null);
  const r = useRouter();

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErr(null);
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/auth/login`, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password })
    });
    if (!res.ok) return setErr("Credenciais inválidas.");
    const data = await res.json();
    setSession(data.accessToken, data.workspaceStatus);
    if (data.workspaceStatus !== "ACTIVE") r.push("/assinatura");
    else r.push("/inicio");
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <form onSubmit={onSubmit} className="w-full max-w-md rounded-2xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] p-6 space-y-3">
        <div className="text-xl font-semibold">Hiperlink CRM</div>
        <div className="text-sm text-[rgb(var(--muted))]">Entrar</div>
        {err && <div className="text-sm text-red-400">{err}</div>}
        <input className="w-full rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2"
          value={email} onChange={e=>setEmail(e.target.value)} placeholder="E-mail" />
        <input className="w-full rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2"
          value={password} onChange={e=>setPassword(e.target.value)} placeholder="Senha" type="password" />
        <button className="w-full rounded-lg px-3 py-2 bg-[rgb(var(--brand))] text-white font-medium">
          Entrar
        </button>
        <a className="block text-sm text-[rgb(var(--muted))] hover:text-white" href="/cadastro">Criar conta</a>
      </form>
    </div>
  );
}