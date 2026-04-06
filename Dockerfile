FROM node:24-alpine AS build

COPY . /app

WORKDIR /app

RUN npm ci && npm run build

FROM nginx:alpine AS production

COPY --from=build /app/build /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
