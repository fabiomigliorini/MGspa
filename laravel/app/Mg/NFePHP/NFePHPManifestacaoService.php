<?php

namespace Mg\NFePHP;

use Mg\NotaFiscal\NotaFiscal;
use Mg\Filial\Filial;

use NFePHP\NFe\Common\Standardize;

class NFePHPManifestacaoService
{
    public static function manifestacao($request)
    {
        try {
            $filial = Filial::findOrFail($request->filial);

            $tools = NFePHPConfigService::instanciaTools($filial);

            //só funciona para o modelo 55
            $tools->model('55');

            //este serviço somente opera em ambiente de produção
            $tools->setEnvironment(1);

            //chave de 44 digitos da nota do fornecedor
            $chNFe = $request->nfechave;

            // 210200 OPERACAO REALIZADA
            // 210210 CIENCIA DA OPERACAO
            // 210220 OPERACAO DESOCNHECIDA
            // 210240 OPERACAO NAO REALIZADA
            $tpEvento =  $request->manifestacao;

            //a ciencia não requer justificativa
            $xJust = $request->justificativa??null;

            //a ciencia em geral será numero inicial de uma sequencia para essa nota e evento
            $nSeqEvento = 1;

            $response = $tools->sefazManifesta($chNFe, $tpEvento, $xJust, $nSeqEvento = 1);

            //você pode padronizar os dados de retorno atraves da classe abaixo
            //de forma a facilitar a extração dos dados do XML
            //NOTA: mas lembre-se que esse XML muitas vezes será necessário,
            //quando houver a necessidade de protocolos
            $st = new Standardize($response);

            //nesse caso $std irá conter uma representação em stdClass do XML
            $stdRes = $st->toStd();

            return $stdRes;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    } // FIM manifestacao
}
