<?php

namespace Mg\NotaFiscalTerceiro;
use Mg\MgService;

use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Estoque\EstoqueLocal;

use Carbon\Carbon;
use DB;

class NotaFiscalTerceiroRegPassagemService extends MgService
{

    public static function armazenaDadosEvento ($filial) {

        $qry = NotaFiscalTerceiroDistribuicaoDfe::select('*')->where('schema', 'resEvento_v1.01.xsd')->orderBy('nsu', 'DESC')->get();
        $qry = end($qry);

        foreach ($qry as $key => $file) {

            $path = NotaFiscalTerceiroPathService::pathDFe($filial, $file->nsu);

            if(file_exists($path)){
                $xmlData = file_get_contents($path);
                $st = new Standardize();
                $xml = $st->toStd($xmlData);

                $coddistribuicaodfe = NotaFiscalTerceiroDistribuicaoDfe::select('coddistribuicaodfe')
                ->where( 'nsu', $file->nsu )->get();

                $resEvento = NotaFiscaleTerceiroEvento::firstOrNew([
                    'coddistribuicaodfe' => $coddistribuicaodfe[0]->coddistribuicaodfe
                ]);
                $resEvento->coddistribuicaodfe = $coddistribuicaodfe[0]->coddistribuicaodfe;
                $resEvento->nsu = $file->nsu;
                $resEvento->codorgao = $xml->cOrgao;
                $resEvento->cnpj = $xml->CNPJ;
                $resEvento->nfechave = $xml->chNFe;
                $resEvento->dhevento = Carbon::parse($xml->dhEvento);
                $resEvento->tpevento = $xml->tpEvento;
                $resEvento->nseqevento = $xml->nSeqEvento;
                $resEvento->evento = $xml->xEvento;
                $resEvento->dhrecebimento = Carbon::parse($xml->dhRecbto);
                $resEvento->protocolo = $xml->nProt;
                // dd($resEvento);
                $resEvento->save();
            }
        }

        return true;

    } // FIM DO armazenaDadosEvento


}
