import { Controller, Get, UseGuards } from "@nestjs/common";
import { JwtAuthGuard } from "../auth/guards/jwt-auth.guard";
import { PrismaService } from "../prisma/prisma.service";
import { ActiveSubscriptionGuard } from "../auth/guards/active-subscription.guard";

@Controller("workspace")
export class WorkspacesController {
  constructor(private prisma: PrismaService) {}

  @UseGuards(JwtAuthGuard)
  @Get("status")
  async status(req: any) {
    // For simplicity, frontend uses token payload; this is optional
    return { ok: true };
  }

  @UseGuards(JwtAuthGuard, ActiveSubscriptionGuard)
  @Get("protected-ping")
  async protectedPing() {
    return { ok: true };
  }
}