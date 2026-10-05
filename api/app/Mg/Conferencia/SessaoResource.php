<?php

namespace Mg\Conferencia;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Caixa\CaixaService;

// Sessao da gaveta (M9 doc-3). Para o gerente, o sistema e o contado pelo
// caixa so' vao depois da conferencia (as cegas); o PDV pede com `resumo`.
class SessaoResource extends Resource
{
    public bool $comResumo = false;

    public function comResumo(): self
    {
        $this->comResumo = true;
        return $this;
    }

    public function toArray($request)
    {
        $conferida = !empty($this->conferencia);
        $resumo = $conferida || $this->comResumo;
        $ret = [
            'codportadorperiodo' => $this->codportadorperiodo,
            'codportador' => $this->codportador,
            'portador' => optional($this->Portador)->portador,
            'codfilial' => optional($this->Portador)->codfilial,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'aberta' => $this->aberto(),
            'usuarioabertura' => optional($this->UsuarioAbertura)->usuario,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'conferencia' => $this->conferencia,
            'usuarioconferencia' => optional($this->UsuarioConferencia)->usuario,
            'observacoes' => $this->observacoes,
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => optional($this->UsuarioCriacao)->usuario,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
        ];
        if ($resumo) {
            $ret = array_merge($ret, [
                'saldoinicial' => $this->saldoinicial,
                'moedasabertura' => $this->moedasabertura,
                'cedulasabertura' => $this->cedulasabertura,
                'moedasfechamento' => $this->moedasfechamento,
                'cedulasfechamento' => $this->cedulasfechamento,
                'saldofinal' => $this->saldofinal,
                'valorconferido' => $this->valorconferido,
                'resumo' => CaixaService::resumo($this->resource),
            ]);
        }
        return $ret;
    }
}
