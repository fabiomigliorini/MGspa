<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource;

class ValeEmitidoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnegociovale' => (int) $this->codnegociovale,
            'codnegocio' => (int) $this->codnegocio,
            'codvalemodelo' => $this->codvalemodelo ? (int) $this->codvalemodelo : null,
            'modelo' => $this->modelo ?: 'Vale avulso',
            'codpessoafavorecido' => $this->codpessoafavorecido ? (int) $this->codpessoafavorecido : null,
            'favorecido' => $this->favorecido,
            'nome' => $this->aluno,
            'aluno' => $this->aluno,
            'turma' => $this->turma,
            'valorvale' => (float) $this->valorvale,
            'valortotal' => (float) $this->valortotal,
            'titulos' => $this->titulo_codtitulo ? [[
                'codtitulo' => (int) $this->titulo_codtitulo,
                'numero' => $this->titulo_numero,
                'saldo' => (float) $this->titulo_saldo,
            ]] : [],
            'data' => $this->lancamento ?: $this->criacao,
            'situacao' => $this->codnegociostatus == 3 || $this->inativo ? 'cancelado' : 'ativo',
        ];
    }
}
