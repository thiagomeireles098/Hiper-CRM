# syntax=docker/dockerfile:1
FROM node:20-alpine AS base
WORKDIR /repo
RUN corepack enable

# 1) Instala deps do monorepo com lock
FROM base AS deps
COPY package.json pnpm-workspace.yaml pnpm-lock.yaml ./
COPY apps/api/package.json apps/api/package.json
RUN pnpm install --frozen-lockfile

# 2) Build da API
FROM base AS build
COPY . .
COPY --from=deps /repo/node_modules /repo/node_modules
WORKDIR /repo/apps/api
RUN npx prisma generate
RUN pnpm build

# 3) Gera uma pasta com deps de produção somente da API
FROM deps AS prod_deps
RUN pnpm --filter @hiperlink/api... deploy --prod /out

# 4) Runtime
FROM node:20-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production

COPY --from=prod_deps /out/node_modules ./node_modules
COPY --from=prod_deps /out/package.json ./package.json

COPY --from=build /repo/apps/api/dist ./dist
COPY --from=build /repo/apps/api/prisma ./prisma

EXPOSE 3001
CMD ["node", "dist/src/main.js"]