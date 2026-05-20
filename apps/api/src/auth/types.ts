export type JwtPayload = {
  sub: string;
  workspaceId: string;
  role: "ADMIN" | "ATTENDANT";
  email: string;
};

export type RefreshPayload = { sub: string };