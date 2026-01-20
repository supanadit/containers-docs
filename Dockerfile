FROM node:24 AS build

COPY . /app

WORKDIR /app

RUN npm install
RUN npm run build

FROM node:24-slim AS production

# Build metadata
ARG BUILD_DATE
ARG VCS_REF

LABEL org.opencontainers.image.created=$BUILD_DATE \
    org.opencontainers.image.revision=$VCS_REF \
    org.opencontainers.image.title="Container Docs" \
    org.opencontainers.image.description="This documentation powered by Docusaurus" \
    org.opencontainers.image.authors="Supan Adit Pratama <email@supanadit.com>" \
    org.opencontainers.image.source=https://github.com/supanadit/containers-docs

WORKDIR /app

COPY --from=build /app/build ./build
COPY --from=build /app/package.json ./package.json
COPY --from=build /app/package-lock.json ./package-lock.json
COPY --from=build /app/docusaurus.config.ts ./docusaurus.config.ts
COPY --from=build /app/sidebars.ts ./sidebars.ts

RUN npm install --omit=dev

CMD ["npm", "run", "serve", "--", "--host", "0.0.0.0"]