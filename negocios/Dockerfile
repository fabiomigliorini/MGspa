#FROM node:lts-alpine
FROM node:18.20-alpine

RUN npm i --location=global @quasar/cli@latest
MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/negocios

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf
#RUN npm install -g @quasar/app@1.9.6 --loglevel verbose
