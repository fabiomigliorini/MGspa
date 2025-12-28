FROM node:20-alpine

RUN npm i --location=global @quasar/cli@2.3.0

MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/notas

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf


