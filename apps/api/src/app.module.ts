import { Module } from "@nestjs/common";
import { PrismaModule } from "./prisma/prisma.module";
import { AuthModule } from "./auth/auth.module";
import { WorkspacesModule } from "./workspaces/workspaces.module";

@Module({
  imports: [PrismaModule, AuthModule, WorkspacesModule]
})
export class AppModule {}