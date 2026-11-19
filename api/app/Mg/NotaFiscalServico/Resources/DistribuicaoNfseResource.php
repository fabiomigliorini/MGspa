<?php

namespace Mg\NotaFiscalServico\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DistribuicaoNfseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'coddistribuicaonfse' => $this->coddistribuicaonfse,
            'codfilial' => $this->codfilial,
            'filial' => $this->Filial?->only(['codfilial', 'filial']),
            'nsu' => $this->nsu,
            'tipodocumento' => $this->tipodocumento,
            'tipoevento' => $this->tipoevento,
            'nfsechave' => $this->nfsechave,
            'data' => $this->data,
            'nfsenumero' => $this->nfsenumero,
            'emitentecnpj' => $this->emitentecnpj,
            'emitente' => $this->emitente,
            'tomadorcnpj' => $this->tomadorcnpj,
            'tomador' => $this->tomador,
            'valorservico' => $this->valorservico,
            'emitida' => $this->emitida,
            'codnotafiscalservico' => $this->codnotafiscalservico,
            'notafiscalservico' => $this->NotaFiscalServico?->only(['codnotafiscalservico', 'status', 'nfsenumero']),
            'criacao' => $this->criacao,
        ];
    }
}
