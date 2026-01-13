<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NfeTerceiro\NfeTerceiro;

class NotaFiscalReferenciadaResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = [
            'codnotafiscalreferenciada' => $this->codnotafiscalreferenciada,
            'codnotafiscal' => $this->codnotafiscal,

            // Dados da Nota Referenciada
            'nfechave' => $this->nfechave,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];

        $ret['notas'] = NotaFiscal::where('nfechave', $this->nfechave)->select('codnotafiscal')->get();
        $ret['nfeterceiros'] = NfeTerceiro::where('nfechave', $this->nfechave)->select('codnfeterceiro')->get();

        return $ret;
    }
}
