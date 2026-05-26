# syntax=docker/dockerfile:1
FROM node:20-bookworm-slim AS base
WORKDIR /app

RUN corepack enable && corepack prepare pnpm@9.0.0 --activate

# deps do web
COPY apps/web/package.json ./
COPY pnpm-lock.yaml ./pnpm-lock.yaml
RUN pnpm install --no-frozen-lockfile

# código do web
COPY apps/web/next.config.ts ./next.config.ts
COPY apps/web/postcss.config.js ./postcss.config.js
COPY apps/web/tailwind.config.ts ./tailwind.config.ts
COPY apps/web/tsconfig.json ./tsconfig.json
COPY apps/web/src ./src
COPY apps/web/public ./public

# build
ARG NEXT_PUBLIC_API_URL
ENV NEXT_PUBLIC_API_URL=${NEXT_PUBLIC_API_URL}
RUN pnpm build

# runtime
FROM node:20-bookworm-slim AS runtime
WORKDIR /app
ENV NODE_ENV=production

COPY --from=base /app/.next ./.next
COPY --from=base /app/public ./public
COPY --from=base /app/node_modules ./node_modules
COPY --from=base /app/package.json ./package.json

EXPOSE 3000
CMD ["node", "node_modules/next/dist/bin/next", "start", "-p", "3000"]
