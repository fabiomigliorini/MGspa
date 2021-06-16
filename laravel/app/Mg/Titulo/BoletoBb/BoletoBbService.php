<?php

namespace Mg\Titulo\BoletoBb;

use DB;
use Carbon\Carbon;

use Mg\Titulo\Titulo;
use Mg\Portador\Portador;

class BoletoBbService
{

    /***
     * Verifica se o Token do Portador ainda não expirou
     * se expirou renova o token
     */
    public static function verificaTokenValido (Portador $portador)
    {
        if (!empty($portador->bbtokenexpiracao)) {
            if ($portador->bbtokenexpiracao->isFuture()) {
                return $portador->bbtoken;
            }
        }
        $token = BoletoBbApiService::token();
        $expiracao = Carbon::now()->addSeconds($token['expires_in']);
        $portador->update([
            'bbtoken' => $token['access_token'],
            'bbtokenexpiracao' => $expiracao,
        ]);
        return $portador->bbtoken;
    }

    /**
     * Monta Nosso Numero de Acordo com Documentacao do BB:
     * Número de identificação do boleto (correspondente ao NOSSO NÚMERO),
     * no formato STRING, com 20 dígitos, que deverá ser formatado da
     * seguinte forma:  “000” +  (número do convênio com 7 dígitos) + (10
     * algarismos - se necessário, completar com zeros à esquerda).
     */
    public static function atribuirNossoNumero (Titulo $titulo)
    {
        // Caso ja tenha numero atribuido, aborta
        if (!empty($titulo->nossonumero)) {
            return $titulo->nossonumero;
        }

        // Monta nome da Sequence
        $sequence = "tbltitulo_nossonumero_{$titulo->codportador}_seq";

        // Cria Sequence se nao existir
        $sql = "CREATE SEQUENCE IF NOT EXISTS {$sequence}";
        DB::statement($sql);

        // Busca proximo numero da sequence
        $sql = 'SELECT NEXTVAL(:sequence) AS numero';
        $res = DB::select($sql, ['sequence' => $sequence]);
        $nossonumero = '000';
        $nossonumero .= str_pad((int)$titulo->Portador->convenio, 7,  '0', STR_PAD_LEFT);
        $nossonumero .= str_pad((int)$res[0]->numero, 10,  '0', STR_PAD_LEFT);
        $titulo->update([
            'nossonumero' => $nossonumero
        ]);
        return $nossonumero;
    }


    public static function registrar(Titulo $titulo)
    {
        $bbtoken = static::verificaTokenValido($titulo->Portador);
        $endereco = $titulo->Pessoa->enderecocobranca;
        if (!empty($titulo->Pessoa->numerocobranca)) {
            $endereco .= ", {$titulo->Pessoa->numerocobranca}";
        }
        if (!empty($titulo->Pessoa->complementocobranca)) {
            $endereco .= " - {$titulo->Pessoa->complementocobranca}";
        }
        $numeroTituloBeneficiario = preg_replace("/\//", '-', $titulo->numero);
        $nossonumero = static::atribuirNossoNumero($titulo);
        $ret = BoletoBbApiService::registrar(
            $bbtoken,
            (int)$titulo->Portador->convenio,
            $titulo->Portador->carteira,
            $titulo->Portador->carteiravariacao,
            $titulo->emissao,
            $titulo->vencimento,
            $titulo->saldo,
            $numeroTituloBeneficiario,
            $nossonumero,
            $titulo->Pessoa->fisica?1:2,
            (int)$titulo->Pessoa->cnpj,
            $titulo->Pessoa->pessoa,
            $endereco,
            $titulo->Pessoa->cepcobranca,
            $titulo->Pessoa->CidadeCobranca->cidade,
            $titulo->Pessoa->bairrocobranca,
            $titulo->Pessoa->CidadeCobranca->Estado->sigla,
            $titulo->Pessoa->telefone1??$titulo->Pessoa->telefone2
        );
        if (isset($ret['erros'])) {
            throw new \Exception("{$ret['erros'][0]['mensagem']} - {$ret['erros'][0]['codigo']}", 0);
        }
        return [
            'codtitulo' => $titulo->codtitulo,
            'bbtoken' => $bbtoken,
            'ret' => $ret,
            'status' => 'Boleto Registrado'
        ];
    }
}
