"use client";
import { getWorkspaceStatus } from "@/lib/auth";

export default function Assinatura() {
  const st = getWorkspaceStatus();
  return (
    <div className="min-h-screen flex items-center justify-center p-4">
      <div className="w-full max-w-lg rounded-2xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] p-6 space-y-3">
        <div className="text-xl font-semibold">Assinatura</div>
        <div className="text-sm text-[rgb(var(--muted))]">
          Status do workspace: <span className="text-white">{st ?? "desconhecido"}</span>
        </div>
        <div className="rounded-xl border border-[rgb(var(--border))] p-4">
          <div className="font-medium">Plano Único</div>
          <div className="text-sm text-[rgb(var(--muted))]">R$ 350,00 / mês</div>
          <button className="mt-3 w-full rounded-lg px-3 py-2 bg-[rgb(var(--brand))] text-white font-medium">
            Gerar cobrança (Efí) - em breve no template
          </button>
        </div>
        <div className="text-xs text-[rgb(var(--muted))]">
          Enquanto não estiver ACTIVE, o acesso ao sistema fica bloqueado.
        </div>
      </div>
    </div>
  );
}