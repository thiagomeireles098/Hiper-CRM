export default function Inicio() {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">Início</h1>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="rounded-xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] p-4">Agentes ativos</div>
        <div className="rounded-xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] p-4">Canais conectados</div>
        <div className="rounded-xl border border-[rgb(var(--border))] bg-[rgb(var(--surface))] p-4">Negócios</div>
      </div>
    </div>
  );
}