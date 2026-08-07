<?php

namespace Mg\Inutilizacao;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class InutilizacaoResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codinutilizacao' => $this->codinutilizacao,
            'codfilial' => $this->codfilial,
            'filial' => $this->Filial?->filial,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'numeroinicial' => $this->numeroinicial,
            'numerofinal' => $this->numerofinal,
            'quantidade' => $this->quantidade,
            'ambiente' => $this->ambiente,
            'justificativa' => $this->justificativa,
            'protocolo' => $this->protocolo,
            'protocolodata' => $this->protocolodata,
            'homologada' => $this->homologada,
            'cstat' => $this->cstat,
            'xmotivo' => $this->xmotivo,
            'temxml' => !empty($this->arquivo),
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
