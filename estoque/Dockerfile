FROM node:22.22.0-alpine
RUN npm i --location=global @quasar/cli@2.5.0
LABEL maintainer="mgpapelaria.com.br"

WORKDIR /opt/www/MGspa/estoque

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf git
