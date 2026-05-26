export function SaaSCard({ title, children, action }: { title: string; children: React.ReactNode; action?: React.ReactNode }) {
  return <section className="rounded-2xl border border-[rgb(var(--border))] bg-[rgb(var(--panel))] p-5 shadow-sm">
    <div className="mb-4 flex items-center justify-between gap-3">
      <h2 className="text-lg font-semibold text-white">{title}</h2>{action}
    </div>{children}
  </section>;
}
export function Badge({ children, tone="ok" }: { children: React.ReactNode; tone?: "ok"|"warn"|"off" }) {
  const cls = tone === "ok" ? "bg-emerald-500/15 text-emerald-300" : tone === "warn" ? "bg-yellow-500/15 text-yellow-300" : "bg-zinc-500/15 text-zinc-300";
  return <span className={`rounded-full px-2 py-1 text-xs ${cls}`}>{children}</span>;
}
