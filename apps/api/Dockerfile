# syntax=docker/dockerfile:1

FROM node:20-alpine AS base
WORKDIR /repo
RUN corepack enable

# 1) Copia manifests para instalar deps
FROM base AS deps
COPY package.json pnpm-workspace.yaml pnpm-lock.yaml ./
COPY apps/api/package.json apps/api/package.json
RUN pnpm install --filter @hiperlink/api... --frozen-lockfile

# 2) Build
FROM base AS build
COPY package.json pnpm-workspace.yaml pnpm-lock.yaml ./
COPY apps/api apps/api
COPY --from=deps /repo/node_modules /repo/node_modules
COPY --from=deps /repo/apps/api/node_modules /repo/apps/api/node_modules

WORKDIR /repo/apps/api
RUN npx prisma generate
RUN pnpm build

# 3) Runtime (com node_modules da API)
FROM node:20-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production

COPY --from=build /repo/apps/api/dist ./dist
COPY --from=build /repo/apps/api/prisma ./prisma
COPY --from=build /repo/apps/api/package.json ./package.json
COPY --from=deps  /repo/apps/api/node_modules ./node_modules

EXPOSE 3001
CMD ["node", "dist/src/main.js"]