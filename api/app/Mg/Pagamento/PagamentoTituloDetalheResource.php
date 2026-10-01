<?php

namespace Mg\Pagamento;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

// Detalhe do recebimento/pagamento de titulos com as linhas de movimento
// (M6 doc-3). operacao: CR recebeu, DB pagou, CP compensacao (sem dinheiro).
class PagamentoTituloDetalheResource extends Resource
{
    public static function operacao(Pagamento $pag): string
    {
        if (!empty($pag->codportadordestino)) {
            return 'CR';
        }
        if (!empty($pag->codportadororigem)) {
            return 'DB';
        }
        $valor = PagamentoTituloService::valor($pag);
        if ($valor < 0) {
            return 'CR';
        }
        return ($valor > 0) ? 'DB' : 'CP';
    }

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

        $portador = $this->portadorDoPagamento();
        return [
            'codpagamento'              => (int) $this->codpagamento,
            'codliquidacaotituloantigo' => $this->codliquidacaotituloantigo,
            'codpessoa'                 => (int) $this->codpessoa,
            'fantasia'                  => optional($this->Pessoa)->fantasia,
            'codportador'               => $portador->codportador ?? null,
            'portador'                  => $portador->portador ?? null,
            'meio'                      => $this->meio,
            'meiodescricao'             => PagamentoService::MEIOS[$this->meio] ?? null,
            'estado'                    => $this->estado,
            'estadodescricao'           => PagamentoService::ESTADOS[$this->estado] ?? null,
            'lancamento'                => $this->lancamento,
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
            'operacao'                  => static::operacao($this->resource),
            // baixa feita pelo banco (boleto): nao se edita nem estorna no contas
            'baixabanco'                => collect($this->MovimentoTituloS)->contains(fn($m) => !empty($m->codtituloboleto) || !empty($m->codboletoretorno)),
            'recebimento'               => PagamentoTituloService::temRecebimento($this->resource),
            'pagamento'                 => PagamentoTituloService::temPagamento($this->resource),
            'movimentos'                => $movimentos,
        ];
    }
}
