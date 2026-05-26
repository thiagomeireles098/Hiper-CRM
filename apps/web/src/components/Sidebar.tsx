"use client";
import Link from "next/link";
import { usePathname } from "next/navigation";
import clsx from "clsx";

const Item = ({ href, label }: { href: string; label: string }) => {
  const path = usePathname();
  const active = path === href || path.startsWith(href + "/");
  return <Link href={href} className={clsx("block rounded-lg px-3 py-2 text-sm transition", active ? "bg-[rgb(var(--brand))] text-white" : "text-[rgb(var(--muted))] hover:bg-[rgb(var(--surface))] hover:text-white")}>{label}</Link>;
};

export default function Sidebar() {
  return <aside className="w-64 hidden md:flex flex-col border-r border-[rgb(var(--border))] p-4 overflow-y-auto">
    <div className="mb-6"><div className="text-lg font-semibold"><span className="text-[rgb(var(--brand))]">Hiperlink</span> CRM</div><div className="text-xs text-[rgb(var(--muted))]">SaaS Comercial</div></div>
    <nav className="space-y-2">
      <Item href="/inicio" label="Início" />
      <div className="pt-3 text-xs text-[rgb(var(--muted))]">SaaS Master</div>
      <Item href="/admin" label="Dashboard SaaS" />
      <Item href="/admin/planos" label="Planos" />
      <Item href="/admin/infraprodutores" label="Infraprodutores" />
      <Item href="/admin/suporte" label="Suporte" />
      <Item href="/assinatura" label="Histórico de compras" />
      <div className="pt-3 text-xs text-[rgb(var(--muted))]">Loja / Infraprodutor</div>
      <Item href="/loja/produtos" label="Produtos" />
      <Item href="/loja/caixas" label="Caixas" />
      <Item href="/loja/pagamentos" label="Mercado Pago" />
      <Item href="/pdv" label="PDV Caixa" />
      <div className="pt-3 text-xs text-[rgb(var(--muted))]">WhatsApp</div>
      <Item href="/agentes" label="Agentes" />
      <Item href="/canais" label="Canais" />
      <div className="pt-3 text-xs text-[rgb(var(--muted))]">Sistema</div>
      <Item href="/configuracoes" label="Configurações" />
    </nav>
  </aside>;
}
