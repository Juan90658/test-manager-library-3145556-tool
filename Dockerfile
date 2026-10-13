FROM node:22-alpine
WORKDIR /tool
COPY package.json ./
COPY src ./src
CMD ["node", "src/seed.mjs"]
