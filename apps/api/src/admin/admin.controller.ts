import { Body, Controller, Delete, Get, Param, Patch, Post, UseGuards } from "@nestjs/common";
import { JwtAuthGuard } from "../auth/guards/jwt-auth.guard";
import { CurrentUser } from "../auth/decorators/current-user.decorator";
import { PrismaService } from "../prisma/prisma.service";
import * as bcrypt from "bcrypt";

function isMaster(user: any) { return user?.role === "SUPER_ADMIN" || user?.role === "SUPPORT"; }

@Controller("admin")
@UseGuards(JwtAuthGuard)
export class AdminController {
  constructor(private prisma: PrismaService) {}

  @Get("dashboard")
  async dashboard(@CurrentUser() user: any) {
    if (!isMaster(user)) return { error: "FORBIDDEN" };
    const [workspaces, active, overdue, plans, payments, sales] = await Promise.all([
      this.prisma.workspace.count(),
      this.prisma.workspace.count({ where: { status: "ACTIVE" } }),
      this.prisma.workspace.count({ where: { status: { in: ["OVERDUE", "BLOCKED"] } } }),
      this.prisma.plan.count(),
      this.prisma.payment.findMany({ where: { status: "APPROVED" }, select: { amountCents: true } }),
      this.prisma.sale.findMany({ select: { totalCents: true } })
    ]);
    return { workspaces, active, overdue, plans, revenueCents: payments.reduce((a,p)=>a+p.amountCents,0), salesCents: sales.reduce((a,s)=>a+s.totalCents,0) };
  }

  @Get("plans")
  plans(@CurrentUser() user: any) { if (!isMaster(user)) return []; return this.prisma.plan.findMany({ orderBy: { createdAt: "desc" } }); }
  @Post("plans")
  createPlan(@CurrentUser() user: any, @Body() body: any) { if (!isMaster(user)) return { error: "FORBIDDEN" }; return this.prisma.plan.create({ data: body }); }
  @Patch("plans/:id")
  updatePlan(@CurrentUser() user: any, @Param("id") id: string, @Body() body: any) { if (!isMaster(user)) return { error: "FORBIDDEN" }; return this.prisma.plan.update({ where: { id }, data: body }); }
  @Delete("plans/:id")
  deletePlan(@CurrentUser() user: any, @Param("id") id: string) { if (!isMaster(user)) return { error: "FORBIDDEN" }; return this.prisma.plan.delete({ where: { id } }); }

  @Get("tenants")
  tenants(@CurrentUser() user: any) { if (!isMaster(user)) return []; return this.prisma.workspace.findMany({ include: { users: true, subscriptions: { include: { plan: true } }, modulePermission: true, mercadoPagoConfig: true }, orderBy: { createdAt: "desc" } }); }
  @Post("tenants")
  async createTenant(@CurrentUser() user: any, @Body() body: any) {
    if (!isMaster(user)) return { error: "FORBIDDEN" };
    const ws = await this.prisma.workspace.create({ data: { name: body.name, document: body.document, phone: body.phone, status: body.status ?? "TRIAL", trialUntil: body.trialUntil ? new Date(body.trialUntil) : undefined } });
    if (body.adminEmail) await this.prisma.user.create({ data: { workspaceId: ws.id, name: body.adminName ?? "Administrador", email: body.adminEmail, passwordHash: await bcrypt.hash(body.adminPassword ?? "123456", 10), role: "ADMIN" } });
    await this.prisma.modulePermission.create({ data: { workspaceId: ws.id, ...(body.permissions ?? {}) } });
    await this.prisma.mercadoPagoConfig.create({ data: { workspaceId: ws.id } });
    if (body.planId) await this.prisma.subscription.create({ data: { workspaceId: ws.id, planId: body.planId, status: "TRIAL" } });
    return ws;
  }
  @Patch("tenants/:id")
  updateTenant(@CurrentUser() user: any, @Param("id") id: string, @Body() body: any) { if (!isMaster(user)) return { error: "FORBIDDEN" }; return this.prisma.workspace.update({ where: { id }, data: body }); }
  @Patch("tenants/:id/permissions")
  permissions(@CurrentUser() user: any, @Param("id") id: string, @Body() body: any) { if (!isMaster(user)) return { error: "FORBIDDEN" }; return this.prisma.modulePermission.upsert({ where: { workspaceId: id }, update: body, create: { workspaceId: id, ...body } }); }
  @Post("tenants/:id/grace")
  async grace(@CurrentUser() user: any, @Param("id") id: string, @Body() body: any) { if (!isMaster(user)) return { error: "FORBIDDEN" }; const days = Number(body.days ?? 7); const graceUntil = new Date(Date.now()+days*86400000); return this.prisma.subscription.updateMany({ where: { workspaceId: id }, data: { graceUntil, status: "ACTIVE" } }); }

  @Get("support")
  support(@CurrentUser() user: any) { if (user?.role !== "SUPER_ADMIN") return []; return this.prisma.user.findMany({ where: { role: "SUPPORT" }, orderBy: { createdAt: "desc" } }); }
  @Post("support")
  async createSupport(@CurrentUser() user: any, @Body() body: any) { if (user?.role !== "SUPER_ADMIN") return { error: "FORBIDDEN" }; return this.prisma.user.create({ data: { name: body.name, email: body.email, passwordHash: await bcrypt.hash(body.password ?? "123456", 10), role: "SUPPORT", permissions: body.permissions ?? {} } }); }
  @Patch("support/:id")
  updateSupport(@CurrentUser() user: any, @Param("id") id: string, @Body() body: any) { if (user?.role !== "SUPER_ADMIN") return { error: "FORBIDDEN" }; return this.prisma.user.update({ where: { id }, data: body }); }
}
