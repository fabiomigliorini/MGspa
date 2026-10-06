<?php

namespace Mg\Conferencia;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Maquineta\MaquinetaLoteService;

// Lote da maquineta (M9 doc-3). Aberto, o sistema nao vai: a conferencia
// e' as cegas. Com `lancamentos`, vem a lista do lote.
class MaquinetaLoteResource extends Resource
{
    public bool $comLancamentos = false;

    public function comLancamentos(): self
    {
        $this->comLancamentos = true;
        return $this;
    }

    public function toArray($request)
    {
        $m = $this->Maquineta;
        $aberto = $this->aberto();
        $ret = [
            'codmaquinetalote' => $this->codmaquinetalote,
            'codmaquineta' => $this->codmaquineta,
            'maquineta' => $m->apelido,
            'adquirente' => optional($m->Pessoa)->fantasia,
            'codfilial' => $m->codfilial,
            'filial' => optional($m->Filial)->filial,
            'compartilhada' => (bool) $m->compartilhada,
            'abertura' => $this->abertura,
            'fechamento' => $this->fechamento,
            'aberto' => $aberto,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'creditoinformado' => $this->creditoinformado,
            'debitoinformado' => $this->debitoinformado,
            'creditosistema' => $this->creditosistema,
            'debitosistema' => $this->debitosistema,
            'observacoes' => $this->observacoes,
            'fotos' => MaquinetaLoteService::fotos($this->resource),
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => $this->usuariocriacao,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => $this->usuarioalteracao,
        ];
        if (!$aberto) {
            $ret['sistema'] = MaquinetaLoteService::sistema($this->codmaquinetalote);
        }
        if ($this->comLancamentos) {
            $ret['lancamentos'] = ConferenciaPagamentoResource::collection(
                MaquinetaLoteService::pagamentos($this->codmaquinetalote)
            );
        }
        return $ret;
    }
}
