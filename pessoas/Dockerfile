FROM node:20.19.1-alpine
RUN npm i --location=global @quasar/cli@2.5.0
LABEL maintainer="mgpapelaria.com.br"

WORKDIR /opt/www/MGspa/pessoas

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf git
