import { Module } from "@nestjs/common";
import { TenantController } from "./tenant.controller";
import { PrismaModule } from "../prisma/prisma.module";
@Module({ imports: [PrismaModule], controllers: [TenantController] })
export class TenantModule {}
