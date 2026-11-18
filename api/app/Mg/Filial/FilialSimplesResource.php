<?php

namespace Mg\Filial;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class FilialSimplesResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codfilialsimples' => (int) $this->codfilialsimples,
            'codfilial' => (int) $this->codfilial,
            // O serializeDate do MgModel poria hora: a competencia e' so a data
            'competencia' => optional($this->competencia)->toDateString(),
            'tributospercentual' => (float) $this->tributospercentual,
            'issaliquota' => (float) $this->issaliquota,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
