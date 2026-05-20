import { Body, Controller, Get, Post, Req, Res, UseGuards } from "@nestjs/common";
import { Response } from "express";
import { AuthService } from "./auth.service";
import { LoginDto, RegisterDto } from "./dto";
import { RefreshAuthGuard } from "./guards/refresh-auth.guard";
import { JwtAuthGuard } from "./guards/jwt-auth.guard";

@Controller("auth")
export class AuthController {
  constructor(private auth: AuthService) {}

  @Post("register")
  async register(@Body() dto: RegisterDto, @Res({ passthrough: true }) res: Response) {
    const out = await this.auth.register(dto);
    // set refresh cookie
    res.cookie("refresh_token", out.refreshToken, {
      httpOnly: true,
      secure: process.env.COOKIE_SECURE === "true",
      sameSite: "lax",
      path: "/"
    });
    return { accessToken: out.accessToken, workspaceStatus: out.workspaceStatus };
  }

  @Post("login")
  async login(@Body() dto: LoginDto, @Res({ passthrough: true }) res: Response) {
    const out = await this.auth.login(dto.email, dto.password);
    res.cookie("refresh_token", out.refreshToken, {
      httpOnly: true,
      secure: process.env.COOKIE_SECURE === "true",
      sameSite: "lax",
      path: "/"
    });
    return { accessToken: out.accessToken, workspaceStatus: out.workspaceStatus, role: out.role };
  }

  @UseGuards(RefreshAuthGuard)
  @Post("refresh")
  async refresh(@Req() req: any, @Res({ passthrough: true }) res: Response) {
    const userId = req.user.sub;
    const refreshToken = req.cookies.refresh_token;
    await this.auth.validateRefresh(userId, refreshToken);
    const out = await this.auth.refresh(userId);
    res.cookie("refresh_token", out.refreshToken, {
      httpOnly: true,
      secure: process.env.COOKIE_SECURE === "true",
      sameSite: "lax",
      path: "/"
    });
    return { accessToken: out.accessToken, workspaceStatus: out.workspaceStatus };
  }

  @UseGuards(JwtAuthGuard)
  @Post("logout")
  async logout(@Req() req: any, @Res({ passthrough: true }) res: Response) {
    await this.auth.logout(req.user.sub);
    res.clearCookie("refresh_token");
    return { ok: true };
  }

  @UseGuards(JwtAuthGuard)
  @Get("me")
  async me(@Req() req: any) {
    return { user: req.user };
  }
}