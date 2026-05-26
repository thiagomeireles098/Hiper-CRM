import { SaaSCard, Badge } from "@/components/SaaSCard";
const plans = [
  { name:'Plano Básico', price:'R$ 99,00', visible:true, features:['PIX','Cartão','3 caixas','500 produtos'] },
  { name:'Plano Agentes WhatsApp', price:'R$ 199,00', visible:true, features:['PIX','Boleto','Cartão','5 agentes WhatsApp'] }
];
export default function Planos() { return <div className="space-y-6"><div><h1 className="text-2xl font-bold">Planos de assinatura</h1><p className="text-[rgb(var(--muted))]">Editar valor, deixar visível/invisível e ligar/desligar recursos.</p></div><div className="grid gap-4 md:grid-cols-2">{plans.map(p=><SaaSCard key={p.name} title={p.name} action={<Badge>{p.visible?'Visível':'Invisível'}</Badge>}><div className="text-3xl font-bold">{p.price}</div><div className="mt-4 flex flex-wrap gap-2">{p.features.map(f=><Badge key={f}>{f}</Badge>)}</div><button className="mt-5 rounded-lg bg-[rgb(var(--brand))] px-4 py-2 text-sm text-white">Editar plano</button></SaaSCard>)}</div></div> }
