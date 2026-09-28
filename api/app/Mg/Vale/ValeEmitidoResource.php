<?php

namespace Mg\Vale;

use Illuminate\Http\Resources\Json\JsonResource;

/** Linha da consulta de vales emitidos (ValeEmitidoService::pesquisar). */
class ValeEmitidoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnegociovale' => (int) $this->codnegociovale,
            'codnegocio' => (int) $this->codnegocio,
            'codvalecompra' => $this->codvalecompra ? (int) $this->codvalecompra : null,
            'codvalemodelo' => $this->codvalemodelo ? (int) $this->codvalemodelo : null,
            'modelo' => $this->modelo,
            'codpessoafavorecido' => (int) $this->codpessoafavorecido,
            'favorecido' => $this->favorecido,
            'aluno' => $this->aluno,
            'turma' => $this->turma,
            'valorvale' => (float) $this->valorvale,
            'valortotal' => (float) $this->valortotal,
            'lancamento' => $this->lancamento,
            'cancelado' => (bool) $this->cancelado,
            'codtitulo' => $this->codtitulo ? (int) $this->codtitulo : null,
            'numero' => $this->numero,
            // no titulo o credito e saldo negativo; aqui sai o que resta do vale
            // (+ 0 tira o "-0" do saldo zerado)
            'saldo' => $this->codtitulo ? round(-1 * (float) $this->saldo, 2) + 0 : null,
        ];
    }
}
