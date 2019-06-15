<?php

namespace Mg\NFePHP;

use NFePHP\NFe\Tools;
use NFePHP\Common\Certificate;

use Mg\Filial\Filial;

class NFePHPRepositoryConfig
{
    public static function config (Filial $filial, $versao = '4.00')
    {

        if (!$filial->Pessoa->fisica) {
            if (empty($filial->nfcetoken)) {
                throw new \Exception("Não foi informado o Token CSC para a Filial!");
            }

            if (empty($filial->nfcetokenid)) {
                throw new \Exception("Não foi informado o ID do Token CSC para a Filial!");
            }

            if (empty($filial->tokenibpt)) {
                throw new \Exception("Não foi informado o ID do Token do IBPT para a Filial!");
	        }
        }

        if ($filial->Pessoa->fisica) {
            $cnpj = str_pad($filial->Pessoa->cnpj, 11, '0', STR_PAD_LEFT);
            $typePerson = 'F';
        } else {
            $cnpj = str_pad($filial->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);
            $typePerson = 'J';
        }


        $config = [
           'atualizacao' => '2018-02-06 06:01:21',
           'tpAmb' => $filial->nfeambiente, // Se deixar o tpAmb como 2 você emitirá a nota em ambiente de homologação(teste) e as notas fiscais aqui não tem valor fiscal
           'razaosocial' => $filial->Pessoa->pessoa,
           'siglaUF' => $filial->Pessoa->Cidade->Estado->sigla,
           'cnpj' => $cnpj,
           'typePerson' => $typePerson,
           'schemes' => 'PL_009_V4',
           'versao' => '4.00',
           'CSC' => $filial->nfcetoken,
           'CSCid' => $filial->nfcetokenid,
           'tokenIBPT' => $filial->tokenibpt
        ];

        if ($versao == '3.10') {
            $config['schemes'] = 'PL_008i2';
            $config['versao'] = '3.10';
        }

        return json_encode($config);
    }

    public static function instanciaTools (Filial $filial, $versao = '4.00')
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
