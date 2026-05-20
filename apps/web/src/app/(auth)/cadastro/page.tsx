"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { setSession } from "@/lib/auth";

export default function Cadastro() {
  const r = useRouter();
  const [form, setForm] = useState({
    workspaceName: "",
    name: "",
    email: "",
    password: "",
    cnpj: "",
    phone: "",
    address: ""
  });

  function set(k: string, v: string) { setForm(s => ({ ...s, [k]: v })); }

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const res = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/auth/register`, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form)
    });
    if (!res.ok) return;
    const data = await res.json();
    setSession(data.accessToken, data.workspaceStatus);
    r.push("/assinatura");
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <form onSubmit={onSubmit} className="w-full max-w-xl rounded-2xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] p-6 space-y-3">
        <div className="text-xl font-semibold">Criar conta</div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
          <input className="rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="Empresa"
            value={form.workspaceName} onChange={e=>set("workspaceName", e.target.value)} />
          <input className="rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="Responsável"
            value={form.name} onChange={e=>set("name", e.target.value)} />
          <input className="rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="E-mail"
            value={form.email} onChange={e=>set("email", e.target.value)} />
          <input className="rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="Senha"
            type="password" value={form.password} onChange={e=>set("password", e.target.value)} />
          <input className="rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="CNPJ"
            value={form.cnpj} onChange={e=>set("cnpj", e.target.value)} />
          <input className="rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="Telefone"
            value={form.phone} onChange={e=>set("phone", e.target.value)} />
          <input className="md:col-span-2 rounded-lg bg-transparent border border-[rgb(var(--border))] px-3 py-2" placeholder="Endereço completo"
            value={form.address} onChange={e=>set("address", e.target.value)} />
        </div>
        <button className="w-full rounded-lg px-3 py-2 bg-[rgb(var(--brand))] text-white font-medium">
          Criar e ir para assinatura
        </button>
      </form>
    </div>
  );
}