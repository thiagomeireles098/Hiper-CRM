import { CanActivate, ExecutionContext, ForbiddenException, Injectable } from "@nestjs/common";
import { PrismaService } from "../../prisma/prisma.service";

@Injectable()
export class ActiveSubscriptionGuard implements CanActivate {
  constructor(private prisma: PrismaService) {}

  async canActivate(ctx: ExecutionContext) {
    const req = ctx.switchToHttp().getRequest();
    const user = req.user as any;
    const ws = await this.prisma.workspace.findUnique({ where: { id: user.workspaceId } });
    if (!ws || ws.status !== "ACTIVE") throw new ForbiddenException("WORKSPACE_NOT_ACTIVE");
    return true;
  }
}