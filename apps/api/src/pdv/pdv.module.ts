import { Module } from "@nestjs/common";
import { PdvController } from "./pdv.controller";
import { PrismaModule } from "../prisma/prisma.module";
@Module({ imports: [PrismaModule], controllers: [PdvController] })
export class PdvModule {}
