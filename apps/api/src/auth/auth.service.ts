import { Injectable, UnauthorizedException } from "@nestjs/common";
import { JwtService } from "@nestjs/jwt";
import { PrismaService } from "../prisma/prisma.service";
import * as bcrypt from "bcrypt";
import { RegisterDto } from "./dto";
import { WorkspaceStatus } from "@prisma/client";

@Injectable()
export class AuthService {
  constructor(private prisma: PrismaService, private jwt: JwtService) {}

  private signAccessToken(user: { id: string; workspaceId?: string | null; role: any; email: string }) {
    return this.jwt.sign(
      { sub: user.id, workspaceId: user.workspaceId ?? null, role: user.role, email: user.email },
      { secret: process.env.JWT_ACCESS_SECRET, expiresIn: process.env.ACCESS_TOKEN_TTL ?? "15m" }
    );
  }

  private signRefreshToken(userId: string) {
    return this.jwt.sign(
      { sub: userId },
      { secret: process.env.JWT_REFRESH_SECRET, expiresIn: process.env.REFRESH_TOKEN_TTL ?? "30d" }
    );
  }

  async register(dto: RegisterDto) {
    const passwordHash = await bcrypt.hash(dto.password, 10);

    const ws = await this.prisma.workspace.create({
      data: { name: dto.workspaceName, status: WorkspaceStatus.INACTIVE }
    });

    const user = await this.prisma.user.create({
      data: {
        workspaceId: ws.id,
        name: dto.name,
        email: dto.email,
        passwordHash,
        role: "ADMIN"
      }
    });

    // user created; workspace inactive -> will be blocked by guard on protected routes
    const accessToken = this.signAccessToken({ id: user.id, workspaceId: ws.id, role: user.role, email: user.email });
    const refreshToken = this.signRefreshToken(user.id);

    const refreshHash = await bcrypt.hash(refreshToken, 10);
    await this.prisma.user.update({ where: { id: user.id }, data: { refreshHash } });

    return { accessToken, refreshToken, workspaceId: ws.id, workspaceStatus: ws.status };
  }

  async login(email: string, password: string) {
    const user = await this.prisma.user.findUnique({ where: { email }, include: { workspace: true } });
    if (!user) throw new UnauthorizedException("INVALID_CREDENTIALS");

    const ok = await bcrypt.compare(password, user.passwordHash);
    if (!ok) throw new UnauthorizedException("INVALID_CREDENTIALS");

    const accessToken = this.signAccessToken({ id: user.id, workspaceId: user.workspaceId ?? null, role: user.role, email: user.email });
    const refreshToken = this.signRefreshToken(user.id);

    const refreshHash = await bcrypt.hash(refreshToken, 10);
    await this.prisma.user.update({ where: { id: user.id }, data: { refreshHash } });

    return { accessToken, refreshToken, workspaceId: user.workspaceId, workspaceStatus: user.workspace.status, role: user.role };
  }

  async refresh(userId: string) {
    const user = await this.prisma.user.findUnique({ where: { id: userId }, include: { workspace: true } });
    if (!user) throw new UnauthorizedException("INVALID_REFRESH");

    const accessToken = this.signAccessToken({ id: user.id, workspaceId: user.workspaceId ?? null, role: user.role, email: user.email });
    const refreshToken = this.signRefreshToken(user.id);

    const refreshHash = await bcrypt.hash(refreshToken, 10);
    await this.prisma.user.update({ where: { id: user.id }, data: { refreshHash } });

    return { accessToken, refreshToken, workspaceStatus: user.workspace.status };
  }

  async logout(userId: string) {
    await this.prisma.user.update({ where: { id: userId }, data: { refreshHash: null } });
    return { ok: true };
  }

  async validateRefresh(userId: string, refreshToken: string) {
    const user = await this.prisma.user.findUnique({ where: { id: userId } });
    if (!user?.refreshHash) throw new UnauthorizedException("INVALID_REFRESH");
    const ok = await bcrypt.compare(refreshToken, user.refreshHash);
    if (!ok) throw new UnauthorizedException("INVALID_REFRESH");
    return true;
  }
}