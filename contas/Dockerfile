FROM node:20.19.1-alpine
RUN npm i --location=global @quasar/cli@2.5.0
MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/contas

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf
#RUN npm install -g @quasar/app@1.9.6 --loglevel verbose
