import { PassportStrategy } from "@nestjs/passport";
import { ExtractJwt, Strategy } from "passport-jwt";
import { Injectable } from "@nestjs/common";
import { RefreshPayload } from "./types";

@Injectable()
export class RefreshStrategy extends PassportStrategy(Strategy, "refresh") {
  constructor() {
    super({
      jwtFromRequest: ExtractJwt.fromExtractors([
        (req) => req?.cookies?.refresh_token
      ]),
      secretOrKey: process.env.JWT_REFRESH_SECRET
    });
  }
  validate(payload: RefreshPayload) {
    return payload;
  }
}