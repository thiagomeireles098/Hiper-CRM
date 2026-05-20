"use client";
import Link from "next/link";
import { usePathname } from "next/navigation";
import clsx from "clsx";

const Item = ({ href, label }: { href: string; label: string }) => {
  const path = usePathname();
  const active = path === href || path.startsWith(href + "/");
  return (
    <Link
      href={href}
      className={clsx(
        "block rounded-lg px-3 py-2 text-sm transition",
        active
          ? "bg-[rgb(var(--brand))] text-white"
          : "text-[rgb(var(--muted))] hover:bg-[rgb(var(--surface))] hover:text-white"
      )}
    >
      {label}
    </Link>
  );
};

export default function Sidebar() {
  return (
    <aside className="w-64 hidden md:flex flex-col border-r border-[rgb(var(--border))] p-4">
      <div className="mb-6">
        <div className="text-lg font-semibold">
          <span className="text-[rgb(var(--brand))]">Hiperlink</span> CRM
        </div>
        <div className="text-xs text-[rgb(var(--muted))]">Painel</div>
      </div>

      <nav className="space-y-2">
        <Item href="/inicio" label="Início" />
        <Item href="/agentes" label="Agentes" />
        <Item href="/canais" label="Canais" />

        <div className="pt-3 text-xs text-[rgb(var(--muted))]">Atendimento</div>
        <Item href="/atendimento/conversas" label="Conversas" />
        <Item href="/atendimento/contatos" label="Contatos" />
        <Item href="/atendimento/crm" label="CRM" />
        <Item href="/atendimento/mensagens-agendadas" label="Mensagens agendadas" />
        <Item href="/atendimento/tarefas" label="Tarefas" />

        <div className="pt-3 text-xs text-[rgb(var(--muted))]">Apps</div>
        <Item href="/apps/itens" label="Cadastro de itens" />

        <div className="pt-3 text-xs text-[rgb(var(--muted))]">Sistema</div>
        <Item href="/configuracoes" label="Configurações" />
        <Item href="/assinatura" label="Assinatura" />
      </nav>
    </aside>
  );
}