import { Body, Controller, Delete, Get, Param, Patch, Post, UseGuards } from "@nestjs/common";
import { JwtAuthGuard } from "../auth/guards/jwt-auth.guard";
import { CurrentUser } from "../auth/decorators/current-user.decorator";
import { PrismaService } from "../prisma/prisma.service";
import * as bcrypt from "bcrypt";

@Controller("tenant")
@UseGuards(JwtAuthGuard)
export class TenantController {
  constructor(private prisma: PrismaService) {}
  private ws(user:any){ return user.workspaceId; }

  @Get("me")
  me(@CurrentUser() user:any){ return this.prisma.workspace.findUnique({ where:{ id:this.ws(user) }, include:{ modulePermission:true, mercadoPagoConfig:true, subscriptions:{ include:{ plan:true } } } }); }

  @Get("products") products(@CurrentUser() user:any){ return this.prisma.product.findMany({ where:{ workspaceId:this.ws(user) }, orderBy:{ createdAt:"desc" } }); }
  @Post("products") createProduct(@CurrentUser() user:any,@Body() body:any){ return this.prisma.product.create({ data:{ workspaceId:this.ws(user), name:body.name, barcode:body.barcode, description:body.description, priceCents:Number(body.priceCents??0), stock:Number(body.stock??0), active:body.active??true } }); }
  @Patch("products/:id") updateProduct(@CurrentUser() user:any,@Param("id") id:string,@Body() body:any){ return this.prisma.product.update({ where:{ id }, data:body }); }
  @Delete("products/:id") deleteProduct(@Param("id") id:string){ return this.prisma.product.delete({ where:{ id } }); }

  @Get("cashiers") cashiers(@CurrentUser() user:any){ return this.prisma.user.findMany({ where:{ workspaceId:this.ws(user), role:"CASHIER" } }); }
  @Post("cashiers") async createCashier(@CurrentUser() user:any,@Body() body:any){ return this.prisma.user.create({ data:{ workspaceId:this.ws(user), name:body.name, email:body.email, passwordHash:await bcrypt.hash(body.password??"123456",10), role:"CASHIER" } }); }
  @Patch("cashiers/:id") updateCashier(@Param("id") id:string,@Body() body:any){ return this.prisma.user.update({ where:{ id }, data:body }); }
  @Delete("cashiers/:id") deleteCashier(@Param("id") id:string){ return this.prisma.user.delete({ where:{ id } }); }

  @Get("payments/config") paymentConfig(@CurrentUser() user:any){ return this.prisma.mercadoPagoConfig.findUnique({ where:{ workspaceId:this.ws(user) } }); }
  @Patch("payments/config") updatePaymentConfig(@CurrentUser() user:any,@Body() body:any){ return this.prisma.mercadoPagoConfig.upsert({ where:{ workspaceId:this.ws(user) }, update:body, create:{ workspaceId:this.ws(user), ...body } }); }

  @Get("agents") agents(@CurrentUser() user:any){ return this.prisma.whatsappAgent.findMany({ where:{ workspaceId:this.ws(user) } }); }
  @Post("agents") createAgent(@CurrentUser() user:any,@Body() body:any){ return this.prisma.whatsappAgent.create({ data:{ workspaceId:this.ws(user), name:body.name, enabled:body.enabled??false } }); }
}
