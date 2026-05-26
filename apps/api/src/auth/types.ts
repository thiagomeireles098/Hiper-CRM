export type JwtPayload = {
  sub: string;
  workspaceId?: string | null;
  role: "SUPER_ADMIN" | "SUPPORT" | "ADMIN" | "CASHIER";
  email: string;
};

export type RefreshPayload = { sub: string };
