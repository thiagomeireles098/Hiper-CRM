import { PrismaClient, UserRole, WorkspaceStatus, SubscriptionStatus } from "@prisma/client";
import * as bcrypt from "bcrypt";

const prisma = new PrismaClient();

async function main() {
  const passwordHash = await bcrypt.hash("@Thiagocros618325913", 10);

  await prisma.user.upsert({
    where: { email: "admin@hiperlinksolutions.com.br" },
    update: { passwordHash, role: UserRole.SUPER_ADMIN, isActive: true },
    create: {
      name: "Hiperlink Master",
      email: "admin@hiperlinksolutions.com.br",
      passwordHash,
      role: UserRole.SUPER_ADMIN,
      isActive: true,
      permissions: { all: true }
    }
  });

  const basic = await prisma.plan.upsert({
    where: { id: "plan-basic" },
    update: {},
    create: {
      id: "plan-basic",
      name: "Plano Básico",
      description: "PDV, produtos, caixas e relatórios básicos.",
      priceCents: 9900,
      maxProducts: 500,
      maxCashiers: 3,
      maxAgents: 0,
      visible: true,
      active: true,
      features: { pix: true, boleto: false, creditCard: true, debitCard: true, whatsappAgent: false, nfce: false }
    }
  });

  await prisma.plan.upsert({
    where: { id: "plan-agents" },
    update: {},
    create: {
      id: "plan-agents",
      name: "Plano Agentes WhatsApp",
      description: "Inclui PDV completo e agentes de WhatsApp.",
      priceCents: 19900,
      maxProducts: 3000,
      maxCashiers: 10,
      maxAgents: 5,
      visible: true,
      active: true,
      features: { pix: true, boleto: true, creditCard: true, debitCard: true, whatsappAgent: true, nfce: false }
    }
  });

  const ws = await prisma.workspace.upsert({
    where: { id: "demo-workspace" },
    update: { status: WorkspaceStatus.ACTIVE },
    create: { id: "demo-workspace", name: "Loja Demonstração", status: WorkspaceStatus.ACTIVE }
  });

  await prisma.user.upsert({
    where: { email: "admin@demo.com" },
    update: {},
    create: {
      workspaceId: ws.id,
      name: "Admin Demo",
      email: "admin@demo.com",
      passwordHash: await bcrypt.hash("admin123", 10),
      role: UserRole.ADMIN
    }
  });

  await prisma.subscription.upsert({
    where: { id: "sub-demo" },
    update: {},
    create: { id: "sub-demo", workspaceId: ws.id, planId: basic.id, status: SubscriptionStatus.ACTIVE }
  });

  await prisma.modulePermission.upsert({ where: { workspaceId: ws.id }, update: {}, create: { workspaceId: ws.id } });
  await prisma.mercadoPagoConfig.upsert({ where: { workspaceId: ws.id }, update: {}, create: { workspaceId: ws.id } });
}

main().finally(() => prisma.$disconnect());
