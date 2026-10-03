<?php

namespace Mg\Caixa;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaResource;

// Sessao da gaveta na tela do caixa (M13 doc-3; nasceu no M9): a mesma no
// PDV e no contas, com contagens, dinheiro do sistema, itens, ajustes,
// avulsos e transferencias. Fechar e' a conferencia (sem etapa as cegas).
class SessaoResource extends Resource
{
    public function toArray($request)
    {
        $codfilial = optional($this->Portador)->codfilial;
        return array_merge([
            'codportadorperiodo' => $this->codportadorperiodo,
            'codportador' => $this->codportador,
            'portador' => optional($this->Portador)->portador,
            'codfilial' => $codfilial,
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'aberta' => $this->aberto(),
            'usuarioabertura' => optional($this->UsuarioAbertura)->usuario,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'saldoinicial' => $this->saldoinicial,
            'saldofinal' => $this->saldofinal,
            'contagemabertura' => $this->contageminicial ?? (object) [],
            'contagemfechamento' => $this->contagemfinal ?? (object) [],
            'observacoes' => $this->observacoes,
            'podeOperar' => CaixaService::podeOperar($this->Portador),
            'podeReabrir' => !empty($this->fechamento) && ConferenciaAutorizador::pode($codfilial),
            'transferencias' => TransferenciaResource::collection($this->transferencias()),
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => optional($this->UsuarioCriacao)->usuario,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
        ], CaixaService::painel($this->resource));
    }

    // as da sessao (pela janela dela) e as a confirmar da gaveta
    private function transferencias()
    {
        $cod = $this->codportador;
        return Pagamento::with(array_merge(PagamentoListaService::RELACOES, [
            'PortadorOrigem.Filial:codfilial,filial',
            'PortadorDestino.Filial:codfilial,filial',
        ]))
            ->whereNull('codnegocio')
            ->whereNotNull('codportadororigem')
            ->whereNotNull('codportadordestino')
            ->where(fn ($w) => $w->where('codportadororigem', $cod)->orWhere('codportadordestino', $cod))
            ->where(function ($w) {
                $w->whereExists(fn ($e) => $e->selectRaw('1')->from('tblportadormovimento as pm')
                    ->whereColumn('pm.codpagamento', 'tblpagamento.codpagamento')
                    ->where('pm.codportadorperiodo', $this->codportadorperiodo));
                if ($this->aberto()) {
                    $w->orWhere('estado', PagamentoService::ESTADO_PENDENTE);
                }
            })
            ->orderBy('transacao', 'desc')
            ->get();
    }
}
