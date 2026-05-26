# syntax=docker/dockerfile:1
FROM node:20-alpine AS base
WORKDIR /app

# fixa pnpm compatível com Node 20
RUN corepack enable && corepack prepare pnpm@9.0.0 --activate

# deps
COPY apps/api/package.json ./
COPY pnpm-lock.yaml ./pnpm-lock.yaml
RUN pnpm install --no-frozen-lockfile

# código
COPY apps/api/tsconfig.json ./tsconfig.json
COPY apps/api/src ./src
COPY apps/api/prisma ./prisma

# prisma + build
RUN pnpm exec prisma generate
RUN pnpm build

# runtime
FROM node:20-alpine AS runtime
WORKDIR /app
ENV NODE_ENV=production

COPY --from=base /app/dist ./dist
COPY --from=base /app/prisma ./prisma
COPY --from=base /app/node_modules ./node_modules
COPY --from=base /app/package.json ./package.json

EXPOSE 3001
CMD ["node", "dist/src/main.js"]