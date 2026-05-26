import { Module } from "@nestjs/common";
import { PrismaModule } from "./prisma/prisma.module";
import { AuthModule } from "./auth/auth.module";
import { WorkspacesModule } from "./workspaces/workspaces.module";
import { HealthController } from "./health.controller";

@Module({
  imports: [PrismaModule, AuthModule, WorkspacesModule],
  controllers: [HealthController]
})
export class AppModule {}