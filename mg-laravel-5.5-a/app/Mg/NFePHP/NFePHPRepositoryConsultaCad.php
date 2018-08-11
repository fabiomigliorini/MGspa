<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Filial\Filial;

class NFePHPRepositoryConsultaCad
{

    public static function consultaCadastro($uf, $cnpj, $iest, $cpf){

        try {

          // $tools = NFePHPRepositoryConfig::instanciaTools($filial, '4.00');
          $tools = NFePHPRepositoryConfig::instanciaTools($filial);
          //só funciona para o modelo 55
          $tools->model('55');
          //este serviço somente opera em ambiente de produção
          $tools->setEnvironment(1);

          $response = $tools->sefazCadastro($uf, $cnpj, $iest, $cpf);

          //você pode padronizar os dados de retorno atraves da classe abaixo
          //de forma a facilitar a extração dos dados do XML
          //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
          //      quando houver a necessidade de protocolos
          $stdCl = new Standardize($response);

          //nesse caso $std irá conter uma representação em stdClass do XML
          $std = $stdCl->toStd();
          dd($std);

          return $std;
        } catch (\Exception $e) {
            //dd($tools);
          // dd($e);
          return $e->getMessage();
        }

    }

}
