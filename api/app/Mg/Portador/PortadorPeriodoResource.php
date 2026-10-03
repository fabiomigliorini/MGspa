<?php

namespace Mg\Portador;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;

// Periodo do portador na aba Periodos do contas (M12 doc-3). Sessao de
// gaveta so' mostra saldos depois de conferida (o gerente confere as
// cegas, M9); com `lancamentos`, as linhas do razao do periodo.
class PortadorPeriodoResource extends Resource
{
    public bool $comLancamentos = false;

    public function comLancamentos(): self
    {
        $this->comLancamentos = true;
        return $this;
    }

    public function toArray($request)
    {
        $gaveta = $this->Portador->ehGaveta();
        $saldos = !$gaveta || !empty($this->conferencia);
        $movimento = PortadorPeriodoService::movimento($this->resource);
        $ret = [
            'codportadorperiodo' => $this->codportadorperiodo,
            'codportador' => $this->codportador,
            'portador' => $this->Portador->portador,
            'tipo' => $this->Portador->tipo,
            'codfilial' => $this->Portador->codfilial,
            'filial' => optional($this->Portador->Filial)->filial,
            'ehGaveta' => $gaveta,
            'descricao' => PortadorPeriodoService::descricao($this->resource),
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'corrente' => empty($this->fim),
            'aberto' => empty($this->fechamento),
            'fechamento' => $this->fechamento,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'conferencia' => $this->conferencia,
            'saldoinicial' => $saldos ? (float) $this->saldoinicial : null,
            'movimento' => $saldos ? $movimento : null,
            // fechado: o gravado; aberto: inicial + o que caiu ate' agora
            'saldofinal' => $saldos
                ? (empty($this->fechamento) ? round((float) $this->saldoinicial + $movimento, 2) : (float) $this->saldofinal)
                : null,
            'observacoes' => $this->observacoes,
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => optional($this->UsuarioCriacao)->usuario,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
        ];
        if ($this->comLancamentos && $saldos) {
            $ret['lancamentos'] = PortadorMovimento::where('codportadorperiodo', $this->codportadorperiodo)
                ->with(['Pagamento' => fn ($q) => $q->with(PagamentoListaService::RELACOES)])
                ->orderBy('transacao')
                ->orderBy('codportadormovimento')
                ->get()
                ->map(function ($l) {
                    $pag = $l->Pagamento;
                    $origem = PagamentoListaService::origem($pag);
                    return [
                        'codportadormovimento' => $l->codportadormovimento,
                        'codpagamento' => $l->codpagamento,
                        'valor' => (float) $l->valor,
                        'transacao' => $l->transacao,
                        'inativo' => $l->inativo,
                        'meiodescricao' => PagamentoService::descricao($pag),
                        'origemdescricao' => PagamentoListaService::ORIGENS[$origem],
                        'documento' => PagamentoListaResource::documento($pag, $origem),
                        'estado' => $pag->estado,
                    ];
                })->all();
        }
        return $ret;
    }
}
