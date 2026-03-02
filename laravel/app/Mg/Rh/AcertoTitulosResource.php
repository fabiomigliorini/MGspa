<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class AcertoTitulosResource extends JsonResource
{
    public function toArray($request)
    {
        $data = $this->resource;

        $titulos = collect($data['titulos'])->map(function ($t) {
            return [
                'codtitulo'           => (int) $t->codtitulo,
                'numero'              => $t->numero,
                'vencimento'          => $t->vencimento,
                'saldo'               => (float) $t->saldo,
                'debitosaldo'         => (float) $t->debitosaldo,
                'creditosaldo'        => (float) $t->creditosaldo,
                'tipotitulo'          => $t->tipotitulo,
                'codtipotitulo'       => (int) $t->codtipotitulo,
                'sugestao_descontando'=> (float) $t->sugestao_descontando,
                'sugestao_pagando'    => (float) $t->sugestao_pagando,
            ];
        })->values();

        return [
            'colaborador' => $data['colaborador'],
            'titulos'     => $titulos,
        ];
    }
}
