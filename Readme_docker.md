
# MGapps

Pré Requisito Docker, Git e Xclip
```
sudo apt install docker docker-compose docker.io git git-man xclip
```

Adicionar o usuario do linux ao grupo do Docker
```
sudo usermod -a -G docker usuario
```

Reinicie o computador por causa do Grupo, é mais efetivo do que ficar descobrindo
as variáveis de ambiente pra corrigir.

Configurar o git:
```
git config --global user.name "Seu Nome"
git config --global user.email "seu@email_do_github.com"
ssh-keygen -t rsa -b 4096 -C "seu@email_do_github.com"
ssh-add ~/.ssh/id_rsa
xclip -sel clip < ~/.ssh/id_rsa.pub
```
Vincular a chave ssh no https://github.com/settings/ssh/new e preencher:
* Title: Nome do Computador, por exemplo: "escmig98-db"
* Key: Ctrl + V
* Clicar em "Add SSH Key"

---

### MGdata

Inície o container e Importe o banco de dados:
```
cd ~
mkdir Docker
cd Docker
git clone git@github.com:fabiomigliorini/MGdb.git
cd ~/Docker/MGdb
./start
./copiar-base-producao
```

Acesse pelo endereço endereço: pgsql://localhost:54320/

---

### MGLara
Clonar a base de código e copiar o arquivo `.env` para a raiz do projeto:
```
cd ~
mkdir Docker
cd Docker
git clone git@github.com:fabiomigliorini/MGLara.git
cd ~/Docker/MGLara
sudo chmod a+w storage/ -R
scp super@netuno.mgpapelaria.com.br:/opt/www/MGLara/.env .env
```
Lembrar de alterar a porta do postgresql `.env`:
```
DB_PORT=54320
```
Inície e acesse o container
```
./start
./shell
```
Certifique-se que esteja no diretório `/opt/www/MGLara/` e instale as dependências
```
composer install
```
> _Caso Ocorra o erro “Class 'Memcached' not found“, ignore-o, não haverá problemas._

Copie as Imagens

```
rsync -uva super@netuno.mgpapelaria.com.br:/opt/www/MGLara/public/imagens/ ~/Docker/MGLara/public/imagens/ --progress --delete
```

Acesse pelo endereço endereço: http://localhost:83/MGLara/


---

### MGsis

Clonar e copiar o arquivo `.env` para a pasta protected do projeto
```
cd ~
mkdir Docker
cd Docker
git clone git@github.com:fabiomigliorini/MGsis.git
```

Copiar o arquivo `.env.php` do diretório `protected`
```
cd ~/Docker/MGsis
scp super@netuno.mgpapelaria.com.br:/opt/www/MGsis/protected/.env.php protected/
```

Lembrar de alterar a conexão no arquivo `protected/.env.php`:
```
define('MGSPA_NFEPHP_URL', 'https://localhost:82/api/v1/nfe-php/');
define('MGLARA_URL', 'http://localhost:83/MGLara/');
define('CONNECTION_STRING', 'pgsql:host=127.0.0.1;port=54320;dbname=mgsis');
```

Inície o container
```
./start
```

Acesse pelo endereço endereço: http://localhost:82/MGsis/

---

### MGUplon

Clonar e copiar o arquivo `.env` para a pasta protected do projeto
```
cd ~
mkdir Docker
cd Docker
git clone git@github.com:fabiomigliorini/MGUplon.git
cd ~/Docker/MGUplon
sudo chmod a+w storage/ -R
scp super@netuno.mgpapelaria.com.br:/opt/www/MGUplon/.env .env
```
Lembrar de alterar a porta do postgresql `.env`:
```
DB_PORT=54320
```
Inície e acesse o container
```
./start
./shell
```
Certifique-se que esteja no diretório `/opt/www/MGUplon/` e instale as dependências
```
composer install
```

Acesse pelo endereço endereço: http://localhost:81/MGUplon/

---

### MGspa

Clonar e copiar o arquivo `.env` para a pasta protected do projeto
```
cd ~
mkdir Docker
cd Docker
git clone git@github.com:fabiomigliorini/MGspa.git
cd ~/Docker/MGspa
sudo chmod a+w laravel/storage/ -R
scp super@netuno.mgpapelaria.com.br:/opt/www/MGspa/laravel/.env laravel/.env
scp super@netuno.mgpapelaria.com.br:/opt/www/MGspa/quasar-v1/.env quasar-v1/.env
```
Alterar no `.env` do laravel:
```
DB_PORT=54320
QUEUE_DRIVER=sync
```
Aterar no `.env` do quasar:
```
API_URL=http://<seu-ip>:91/api/v1/
```
Inície e acess o container
```
./start
./shell
```
Instale as dependências do **Laravel**
```
cd laravel/
composer install
```
Instale as dependências do **Quasar**
```
cd ../quasar-v1/
npm install
```

Rodando o projeto
```
quasar dev -m pwa
```
Acesse pelo endereço endereço: http://localhost:8080/#/
