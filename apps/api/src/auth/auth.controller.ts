import {
  Body,
  Controller,
  Get,
  Post,
  Req,
  Res,
  UseGuards,
} from "@nestjs/common";
import type { Request, Response } from "express";
import { AuthService } from "./auth.service";
import { LoginDto, RegisterDto } from "./dto";
import { JwtAuthGuard } from "./guards/jwt-auth.guard";
import { RefreshAuthGuard } from "./guards/refresh-auth.guard";

@Controller("auth")
export class AuthController {
  constructor(private readonly auth: AuthService) {}

  private setRefreshCookie(res: Response, refreshToken: string) {
    const secure = process.env.COOKIE_SECURE === "true";
    res.cookie("refresh_token", refreshToken, {
      httpOnly: true,
      secure,
      sameSite: "lax",
      path: "/",
      // se você quiser definir maxAge:
      // maxAge: 1000 * 60 * 60 * 24 * 30,
    });
  }

  @Post("register")
  async register(
    @Body() dto: RegisterDto,
    @Res({ passthrough: true }) res: Response
  ) {
    const out = await this.auth.register(dto);
    this.setRefreshCookie(res, out.refreshToken);

    return {
      accessToken: out.accessToken,
      workspaceStatus: out.workspaceStatus,
    };
  }

  @Post("login")
  async login(
    @Body() dto: LoginDto,
    @Res({ passthrough: true }) res: Response
  ) {
    const out = await this.auth.login(dto.email, dto.password);
    this.setRefreshCookie(res, out.refreshToken);

    return {
      accessToken: out.accessToken,
      workspaceStatus: out.workspaceStatus,
      role: out.role,
    };
  }

  @UseGuards(RefreshAuthGuard)
  @Post("refresh")
  async refresh(
    @Req() req: Request & { user: any; cookies: any },
    @Res({ passthrough: true }) res: Response
  ) {
    const userId = req.user.sub;
    const refreshToken = req.cookies?.refresh_token;

    await this.auth.validateRefresh(userId, refreshToken);

    const out = await this.auth.refresh(userId);
    this.setRefreshCookie(res, out.refreshToken);

    return {
      accessToken: out.accessToken,
      workspaceStatus: out.workspaceStatus,
    };
  }

  @UseGuards(JwtAuthGuard)
  @Post("logout")
  async logout(
    @Req() req: Request & { user: any },
    @Res({ passthrough: true }) res: Response
  ) {
    await this.auth.logout(req.user.sub);
    res.clearCookie("refresh_token", { path: "/" });
    return { ok: true };
  }

  @UseGuards(JwtAuthGuard)
  @Get("me")
  async me(@Req() req: Request & { user: any }) {
    return { user: req.user };
  }
}