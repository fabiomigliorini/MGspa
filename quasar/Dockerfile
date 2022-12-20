FROM node:10-alpine

MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/quasar

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf 
RUN npm install -g @quasar/cli --loglevel verbose

