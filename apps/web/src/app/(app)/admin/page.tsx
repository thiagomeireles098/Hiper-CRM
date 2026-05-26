import { SaaSCard, Badge } from "@/components/SaaSCard";
export default function AdminSaaS() {
  return <div className="space-y-6">
    <div><h1 className="text-2xl font-bold">Painel SaaS Hiper-CRM</h1><p className="text-[rgb(var(--muted))]">Controle mestre de assinaturas, infraprodutores, planos e módulos.</p></div>
    <div className="grid gap-4 md:grid-cols-4">
      {[['Clientes ativos','0'],['Clientes vencidos','0'],['Receita mensal','R$ 0,00'],['Vendas totais','R$ 0,00']].map(([a,b])=><SaaSCard key={a} title={a}><div className="text-3xl font-bold">{b}</div></SaaSCard>)}
    </div>
    <SaaSCard title="Módulos principais">
      <div className="grid gap-3 md:grid-cols-3">
        {['Mercado Pago','Planos visíveis/invisíveis','Permissões ON/OFF','Histórico de compras','Suporte com permissões','Agente WhatsApp','PDV por caixa','Comprovantes','NF-e/NFC-e preparado'].map((x,i)=><div key={x} className="rounded-xl bg-black/20 p-3 flex items-center justify-between"><span>{x}</span><Badge tone={i>5?'warn':'ok'}>{i>5?'preparado':'ativo'}</Badge></div>)}
      </div>
    </SaaSCard>
  </div>;
}
