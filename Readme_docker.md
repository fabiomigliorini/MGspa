
# MGapps

---

### MGdata

Inície o container e Importe o banco de dados:
```
cd ~
mkdir Docker
cd Docker
git clone  git@github.com:fabiomigliorini/MGdb.git
cd ~/Docker/MGdb
./start
./copiar-base-producao
```

Acesse pelo endereço endereço: pgsql://localhost:54320/

---

### MGLara
Clonar e copiar o arquivo `.env` para a raíz do projeto
```
cd ~
mkdir Docker
cd Docker
git clone  git@github.com:fabiomigliorini/MGLara.git
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
git clone  git@github.com:fabiomigliorini/MGsis.git
cd ~/Docker/MGsis
scp super@netuno.mgpapelaria.com.br:/opt/www/MGsis/protected/.env.php protected/
```
Lembrar de alterar a conexão no arquivo `.env`:
```
DB_HOST=localhost:54320
```

Copiar o arquivo `.env` do diretório `protected`

Inície o container
```
$ ./start
```
Acesse pelo endereço endereço: http://localhost:82/MGsis/

---


### Início

Clone os repositórios a seguir em uma mesma pasta:
```
git clone  git@github.com:fabiomigliorini/MGUplon.git
git clone  git@github.com:fabiomigliorini/MGspa.git
```




### MGUplon
Copiar o arquivo `.env` para a raíz do projeto

Inície o container
```
$ ./start
```

Acesse o container
```
$ ./shell
```

Instale as dependências
```
$ composer install
```
Acesse pelo endereço endereço: http://localhost:81/MGUplon

### MGspa
Copiar o arquivo `.env` para o diretório `laravel/`

Copiar o arquivo `.env` para o diretório `quasar/`

Alterar a propriedade API_URL do .env do **quasar** para:
```
API_URL=http://localhost:91/api/v1/
```
Inície o container
```
$ ./start
```
Acesse o container
```
$ ./shell
```
Instale as dependências do **Laravel**
```
$ cd laravel/
$ composer install
```
Instale as dependências do **Quasar**
```
$ cd ../quasar/
$ npm install
```

Compile o projeto
```
$ quasar build -m pwa
```

Copie os arquivos compilados para o diretório `producao/`
```
$ rsync -uva dist/pwa-mat/ ../producao/pwa/
```
Acesse pelo endereço endereço: http://localhost:83/


http://localhost:82/MGsis/
