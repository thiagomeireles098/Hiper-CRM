# Hiper-CRM SaaS

Versão adaptada para SaaS multi-tenant com:

- Login mestre: admin@hiperlinksolutions.com.br
- Senha mestre: @Thiagocros618325913
- Planos de assinatura com valor editável e visibilidade ON/OFF
- Plano Agentes WhatsApp
- Infraprodutores com permissões ON/OFF
- Suporte com permissões administrativas
- Mercado Pago por infraprodutor
- Produtos, caixas e PDV
- Histórico de pagamentos e assinaturas
- Estrutura preparada para NF-e/NFC-e via nfewizard futuramente

## Depois de subir na VPS

```bash
cd /opt/hiperlink
docker compose exec api pnpm prisma generate
docker compose exec api pnpm prisma db push
docker compose exec api pnpm prisma db seed
docker compose restart
```

Acesse `/login` com o usuário mestre acima.
