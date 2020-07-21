<?php

namespace Mg\Dfe;

use Mg\Pessoa\Pessoa;

use Illuminate\Http\Resources\Json\Resource;

class DistribuicaoDfeResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // DfeTipo
        $ret['DfeTipo'] = $this->DfeTipo()->select([
            'coddfetipo',
            'dfetipo',
            'schemaxml',
        ])->first();

        // Filial
        $ret['Filial'] = $this->Filial()->select([
            'codfilial',
            'filial',
        ])->first();

        // Eventos
        $ret['DistribuicaoDfeEventoS'] = [];
        $qry = $this->DistribuicaoDfeEventoS()->select([
            'coddistribuicaodfeevento',
            'orgao',
            'nfechave',
            'data',
            'sequencia',
            'recebimento',
            'protocolo',
            'cnpj',
            'cpf',
            'descricao',
            'coddfeevento',
        ]);
        if ($ddes = $qry->get()) {
            foreach ($ddes as $dde) {
                $arr = $dde->toArray();
                $arr['DfeEvento'] = $dde->DfeEvento()->select([
                    'coddfeevento',
                    'dfeevento',
                    'tpevento'
                    ])->first()->toArray();
                $ret['DistribuicaoDfeEventoS'][] = $arr;
            }
        }

        // NotaFiscalTerceiro
        $ret['NotaFiscalTerceiro'] = [];
        $qry = $this->NotaFiscalTerceiro()->select([
            'codpessoa',
            'emitente',
            'indsituacao',
            'nfechave',
            'valortotal',
            'cnpj',
            'cpf',
            'natop'
        ]);
        if ($nft = $qry->first()) {
            $arr = $nft->toArray();
            $pessoa = null;
            if (!empty($nft->codpessoa)) {
                $pessoa = $nft->Pessoa()->select([
                    'codpessoa',
                    'fantasia',
                    'pessoa'
                ])->first();
            }
            $arr['Pessoa'] = $pessoa;
            $ret['NotaFiscalTerceiro'] = $arr;
        }

        return $ret;
    }
}
