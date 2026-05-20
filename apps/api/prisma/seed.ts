import { PrismaClient, WorkspaceStatus, UserRole } from "@prisma/client";
import * as bcrypt from "bcrypt";

const prisma = new PrismaClient();

async function main() {
  const ws = await prisma.workspace.upsert({
    where: { id: "demo-workspace" },
    update: {},
    create: { id: "demo-workspace", name: "Demo Workspace", status: WorkspaceStatus.ACTIVE }
  });

  const passwordHash = await bcrypt.hash("admin123", 10);

  await prisma.user.upsert({
    where: { email: "admin@demo.com" },
    update: {},
    create: {
      workspaceId: ws.id,
      name: "Admin Demo",
      email: "admin@demo.com",
      passwordHash,
      role: UserRole.ADMIN
    }
  });
}

main().finally(() => prisma.$disconnect());