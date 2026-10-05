<?php

namespace Mg\Pagamento;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Portador\PortadorPeriodoService;

// Detalhe de um pagamento (M6.1 doc-3): documento (venda, titulos com as
// linhas de movimento, transferencia, avulso), meio, maquineta, portadores,
// estado e o pagamento original/contrarios. operacao: CR entrou, DB saiu,
// TR transferencia, CP compensacao (sem dinheiro).
class PagamentoDetalheResource extends Resource
{
    public function toArray($request)
    {
        $movimentos = collect($this->MovimentoTituloS ?? [])
            ->filter(fn($m) => !$m->ehEstorno())
            ->values()
            ->map(function ($m) {
                $principal = (float) $m->principal;
                return [
                    'codmovimentotitulo'     => (int) $m->codmovimentotitulo,
                    'codtitulo'              => (int) $m->codtitulo,
                    'codtipomovimentotitulo' => (int) $m->codtipomovimentotitulo,
                    'tipomovimentotitulo'    => optional($m->TipoMovimentoTitulo)->tipomovimentotitulo,
                    'transacao'              => $m->transacao,
                    'principal'              => abs($principal),
                    'juros'                  => (float) $m->juros,
                    'multa'                  => (float) $m->multa,
                    'desconto'               => (float) $m->desconto,
                    'total'                  => abs((float) $m->total),
                    'operacao'               => $principal < 0 ? 'CR' : 'DB',
                    'titulo' => $m->Titulo ? [
                        'codtitulo'   => (int) $m->Titulo->codtitulo,
                        'numero'      => $m->Titulo->numero,
                        'fatura'      => $m->Titulo->fatura,
                        'vencimento'  => $m->Titulo->vencimento,
                        'gerencial'   => (bool) $m->Titulo->gerencial,
                        'boleto'      => (bool) $m->Titulo->boleto,
                        'nossonumero' => $m->Titulo->nossonumero,
                        'codpessoa'   => (int) $m->Titulo->codpessoa,
                        'fantasia'    => optional($m->Titulo->Pessoa)->fantasia,
                        'codfilial'   => (int) $m->Titulo->codfilial,
                        'filial'      => optional($m->Titulo->Filial)->filial,
                        'codportador' => $m->Titulo->codportador,
                        'portador'    => optional($m->Titulo->Portador)->portador,
                    ] : null,
                ];
            })
            ->all();

        $origem = PagamentoListaService::origem($this->resource);
        $portador = $this->portadorDoPagamento();
        $pessoa = PagamentoListaService::pessoa($this->resource);
        $temTitulo = !empty($movimentos);
        $baixabanco = collect($this->MovimentoTituloS)->contains(fn($m) => !empty($m->codtituloboleto) || !empty($m->codboletoretorno));
        return [
            'codpagamento'              => (int) $this->codpagamento,
            'codliquidacaotituloantigo' => $this->codliquidacaotituloantigo,
            'origem'                    => $origem,
            'origemdescricao'           => PagamentoListaService::ORIGENS[$origem],
            'documento'                 => PagamentoListaResource::documento($this->resource, $origem),
            'codnegocio'                => $this->codnegocio,
            'codpessoa'                 => $pessoa->codpessoa ?? null,
            'fantasia'                  => $pessoa->fantasia ?? null,
            'codportador'               => $portador->codportador ?? null,
            'portador'                  => $portador->portador ?? null,
            'codportadororigem'         => $this->codportadororigem,
            'portadororigem'            => optional($this->PortadorOrigem)->portador,
            'codportadordestino'        => $this->codportadordestino,
            'portadordestino'           => optional($this->PortadorDestino)->portador,
            'meio'                      => $this->meio,
            'meiodescricao'             => PagamentoService::descricao($this->resource),
            'codmaquineta'              => $this->codmaquineta,
            'maquineta'                 => optional($this->Maquineta)->apelido,
            'bandeira'                  => $this->bandeira,
            'bandeiradescricao'         => PagamentoService::BANDEIRAS[$this->bandeira] ?? null,
            'autorizacao'               => $this->autorizacao,
            'nsu'                       => $this->nsu,
            'parcelas'                  => $this->parcelas,
            'cmc7'                      => $this->cmc7,
            'chequevencimento'          => $this->chequevencimento,
            'chequeemitente'            => $this->chequeemitente,
            'codpixcob'                 => $this->codpixcob,
            'codpagarmepedido'          => $this->codpagarmepedido,
            'codsauruspedido'           => $this->codsauruspedido,
            'codpdv'                    => $this->codpdv,
            'pdv'                       => optional($this->Pdv)->apelido,
            'codfilial'                 => $this->codfilial,
            'filial'                    => optional($this->Filial)->filial,
            'motivo'                    => $this->motivo,
            'motivodescricao'           => PagamentoService::MOTIVOS[$this->motivo] ?? null,
            'estado'                    => $this->estado,
            'estadodescricao'           => PagamentoService::ESTADOS[$this->estado] ?? null,
            'transacao'                 => $this->transacao,
            'efetivacao'                => $this->efetivacao,
            'criacao'                   => $this->criacao,
            'alteracao'                 => $this->alteracao,
            'cancelamento'              => $this->cancelamento,
            'justificativa'             => $this->justificativa,
            'codperiodocolaboradoracerto' => $this->codperiodocolaboradoracerto,
            'observacoes'               => $this->observacoes,
            'codusuariocriacao'         => $this->codusuariocriacao,
            'codusuarioalteracao'       => $this->codusuarioalteracao,
            'codusuariocancelamento'    => $this->codusuariocancelamento,
            'principal'                 => (float) $this->principal,
            'juros'                     => (float) $this->juros,
            'multa'                     => (float) $this->multa,
            'desconto'                  => (float) $this->desconto,
            'total'                     => (float) $this->total,
            'valortroco'                => $this->valortroco,
            'operacao'                  => PagamentoListaService::operacao($this->resource),
            'codpagamentoorigem'        => $this->codpagamentoorigem,
            'pagamentoorigem'           => $this->PagamentoOrigem ? [
                'codpagamento' => (int) $this->PagamentoOrigem->codpagamento,
                'meiodescricao' => PagamentoService::MEIOS[$this->PagamentoOrigem->meio] ?? null,
                'total' => (float) $this->PagamentoOrigem->total,
                'transacao' => $this->PagamentoOrigem->transacao,
                'codnegocio' => $this->PagamentoOrigem->codnegocio,
            ] : null,
            'contrarios'                => collect($this->PagamentoContrarioS)->map(fn($c) => [
                'codpagamento' => (int) $c->codpagamento,
                'estado' => $c->estado,
                'total' => (float) $c->total,
                'transacao' => $c->transacao,
            ])->values()->all(),
            // o que a tela pode fazer: estornar e editar so' baixa de titulo
            // feita a mao (venda pelo negocio, acerto pelo acerto, boleto
            // pelo banco)
            'estornavel'                => $temTitulo && $this->estado != PagamentoService::ESTADO_CANCELADO
                && empty($this->codnegocio) && empty($this->codperiodocolaboradoracerto) && !$baixabanco,
            'baixabanco'                => $baixabanco,
            'recebimento'               => PagamentoTituloService::temRecebimento($this->resource),
            'pagamento'                 => PagamentoTituloService::temPagamento($this->resource),
            'movimentos'                => $movimentos,
            // razao (M10 doc-3): o que caiu em cada portador; inativas =
            // desfeitas por estorno, cancelamento ou correcao
            'razao'                     => collect($this->PortadorMovimentoS)
                ->sortBy('codportadormovimento')
                ->values()
                ->map(fn($r) => [
                    'codportadormovimento' => (int) $r->codportadormovimento,
                    'codportador'          => (int) $r->codportador,
                    'portador'             => optional($r->Portador)->portador,
                    'valor'                => (float) $r->valor,
                    'transacao'            => $r->transacao,
                    'parcela'              => $r->parcela,
                    'codportadorperiodo'   => (int) $r->codportadorperiodo,
                    'periodo'              => $r->PortadorPeriodo ? PortadorPeriodoService::descricao($r->PortadorPeriodo) : null,
                    'inativo'              => $r->inativo,
                ])
                ->all(),
        ];
    }
}
