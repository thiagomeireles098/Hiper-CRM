# syntax=docker/dockerfile:1

FROM node:20-alpine AS base
WORKDIR /repo
RUN corepack enable

FROM base AS deps
COPY package.json pnpm-workspace.yaml ./
COPY apps/web/package.json apps/web/package.json
RUN pnpm install --filter @hiperlink/web... --no-frozen-lockfile

FROM base AS build
COPY --from=deps /repo/node_modules /repo/node_modules
COPY --from=deps /repo/apps/web/node_modules /repo/apps/web/node_modules
COPY package.json pnpm-workspace.yaml ./
COPY apps/web apps/web

WORKDIR /repo/apps/web
RUN pnpm build

FROM node:20-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production
COPY --from=build /repo/apps/web/.next ./.next
COPY --from=build /repo/apps/web/public ./public
COPY --from=build /repo/apps/web/package.json ./package.json
COPY --from=deps  /repo/apps/web/node_modules ./node_modules
EXPOSE 3000
CMD ["node", "node_modules/next/dist/bin/next", "start", "-p", "3000"]
