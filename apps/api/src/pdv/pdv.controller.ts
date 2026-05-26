import { Body, Controller, Get, Post, Query, UseGuards } from "@nestjs/common";
import { JwtAuthGuard } from "../auth/guards/jwt-auth.guard";
import { CurrentUser } from "../auth/decorators/current-user.decorator";
import { PrismaService } from "../prisma/prisma.service";

@Controller("pdv")
@UseGuards(JwtAuthGuard)
export class PdvController {
  constructor(private prisma: PrismaService) {}
  @Get("scan") scan(@CurrentUser() user:any, @Query("barcode") barcode:string) { return this.prisma.product.findFirst({ where:{ workspaceId:user.workspaceId, barcode, active:true } }); }
  @Post("sales") async sale(@CurrentUser() user:any, @Body() body:any) {
    const items = body.items ?? [];
    const totalCents = items.reduce((sum:number, item:any)=> sum + Number(item.quantity) * Number(item.unitCents), 0);
    const sale = await this.prisma.sale.create({ data:{ workspaceId:user.workspaceId, cashierId:user.id, totalCents, method:body.method, receiptCode:`HPR-${Date.now()}`, items:{ create: items.map((i:any)=>({ productId:i.productId, name:i.name, quantity:Number(i.quantity), unitCents:Number(i.unitCents), totalCents:Number(i.quantity)*Number(i.unitCents) })) } }, include:{ items:true, workspace:true } });
    return sale;
  }
}
