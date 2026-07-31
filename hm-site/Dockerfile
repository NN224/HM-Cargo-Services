FROM node:22-alpine AS builder

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .

# Build the project
RUN npm run build

# Production stage
FROM node:22-alpine

WORKDIR /app
COPY --from=builder /app/.output ./.output
COPY --from=builder /app/package.json ./

ENV PORT=8080
ENV NODE_ENV=production
EXPOSE 8080

CMD ["node", ".output/server/index.mjs"]
