<?php

namespace Mg\Mdfe;

use NFePHP\MDFe\Tools;
use NFePHP\Common\Certificate;

use Mg\Filial\Filial;

class MdfeNfePhpConfigService
{
    public static function config(Filial $filial, $versao = '3.00')
    {

        if ($filial->Pessoa->fisica) {
            $cnpj = '';
            $cpf = str_pad($filial->Pessoa->cnpj, 11, '0', STR_PAD_LEFT);
            // $typePerson = 'F';
        } else {
            $cnpj = str_pad($filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
            $cpf = '';
            // $typePerson = 'J';
        }

        $config = [
           'atualizacao' => '2021-01-27 17:33:55',
           'tpAmb' => $filial->nfeambiente, // Se deixar o tpAmb como 2 você emitirá a nota em ambiente de homologação(teste) e as notas fiscais aqui não tem valor fiscal
           'razaosocial' => $filial->Pessoa->pessoa,
           'cpf' => $cpf,
           'cnpj' => $cnpj,
           'siglaUF' => $filial->Pessoa->Cidade->Estado->sigla,
           'ie' => $filial->Pessoa->ie,
           'versao' => $versao,
        ];

        return json_encode($config);
    }

    public static function instanciaTools(Filial $filial, $versao = '3.00')
    {
        // Monta Configuracao da Filial
        $config = static::config($filial, $versao);

        // Le Certificado Digital
        $arquivo = env('NFE_PHP_PATH') . "/Certs/{$filial->codfilial}.pfx";
        if (!file_exists($arquivo)) {
            throw new \Exception("Arquivo de certificado não localizado ({$arquivo})!");
        }
        $pfx = file_get_contents($arquivo);

        // retorna Instancia Tools para a configuracao e certificado
        if (empty($filial->senhacertificado)) {
            throw new \Exception("Não foi informado senha para o Certificado!");
        }
        return new Tools($config, Certificate::readPfx($pfx, $filial->senhacertificado));
    }
}
