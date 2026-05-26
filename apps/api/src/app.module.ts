import { Module } from "@nestjs/common";
import { PrismaModule } from "./prisma/prisma.module";
import { AuthModule } from "./auth/auth.module";
import { WorkspacesModule } from "./workspaces/workspaces.module";
import { HealthController } from "./health.controller";
import { AdminModule } from "./admin/admin.module";
import { TenantModule } from "./tenant/tenant.module";
import { PdvModule } from "./pdv/pdv.module";

@Module({
  imports: [PrismaModule, AuthModule, WorkspacesModule, AdminModule, TenantModule, PdvModule],
  controllers: [HealthController]
})
export class AppModule {}