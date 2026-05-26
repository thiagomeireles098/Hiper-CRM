# syntax=docker/dockerfile:1
FROM node:20-alpine AS base
WORKDIR /repo
RUN corepack enable

# deps do monorepo
FROM base AS deps
COPY package.json pnpm-workspace.yaml pnpm-lock.yaml ./
COPY apps/api/package.json apps/api/package.json
RUN pnpm install --frozen-lockfile

# build da API
FROM base AS build
COPY . .
COPY --from=deps /repo/node_modules /repo/node_modules
WORKDIR /repo/apps/api
RUN pnpm exec prisma generate
RUN pnpm build

# runtime (COPIANDO node_modules COMPLETO DO MONOREPO)
FROM node:20-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production

# dist + prisma
COPY --from=build /repo/apps/api/dist ./dist
COPY --from=build /repo/apps/api/prisma ./prisma
COPY --from=build /repo/apps/api/package.json ./package.json

# aqui está o segredo: copiar o node_modules do monorepo inteiro
# (isso garante que @nestjs/core existe)
COPY --from=deps /repo/node_modules ./node_modules

EXPOSE 3001
CMD ["node", "dist/src/main.js"]