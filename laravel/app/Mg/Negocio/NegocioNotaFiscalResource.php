<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Facades\DB;

class NegocioNotaFiscalResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->notas();
    }

    private function notas() 
    {
        $sql = '
            select 
                nf.codnotafiscal,
                nf.codfilial,
                f.filial,
                nf.emitida,
                nf.serie,
                nf.modelo,
                nf.numero,
                nf.emissao,
                nf.saida,
                nf.codnaturezaoperacao,
                nat.naturezaoperacao,
                nf.codpessoa,
                p.fantasia,
                nf.valortotal,
                nf.nfechave,
                nf.nfeimpressa,
                nf.nfereciboenvio,
                nf.nfedataenvio,
                nf.nfeautorizacao,
                nf.nfedataautorizacao,
                nf.nfecancelamento,
                nf.nfedatacancelamento,
                nf.nfeinutilizacao,
                nf.nfedatainutilizacao
            from tblnotafiscal nf
            inner join tblfilial f on (f.codfilial = nf.codfilial)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = nf.codnaturezaoperacao)
            inner join tblpessoa p on (p.codpessoa = nf.codpessoa)
        ';
        if (isset($this->codnotafiscal)) {
            $sql .= '
                where nf.codnotafiscal = :codnotafiscal
            ';
            return DB::select($sql, [
                'codnotafiscal' => $this->codnotafiscal,
            ])[0];
    
        }
        $sql .= '
            where nf.codnotafiscal in (
                select distinct nfpb.codnotafiscal 
                from tblnegocioprodutobarra npb
                inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnegocioprodutobarra = npb.codnegocioprodutobarra)
                where npb.codnegocio = :codnegocio 
            )        
        ';
        return DB::select($sql, [
            'codnegocio' => $this->codnegocio,
        ]);
    }
}
