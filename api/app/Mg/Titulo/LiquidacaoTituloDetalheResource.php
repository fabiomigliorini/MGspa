<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class LiquidacaoTituloDetalheResource extends Resource
{
    public function toArray($request)
    {
        $valor = (float)$this->valor;
        $operacao = ($valor < 0) ? 'CR' : 'DB';

        $movimentos = collect($this->MovimentoTituloS ?? [])
            ->filter(fn($m) => !$m->ehEstorno())
            ->values()
            ->map(function ($m) {
                $principal = (float)$m->principal;
                return [
                    'codmovimentotitulo'     => (int)$m->codmovimentotitulo,
                    'codtitulo'              => (int)$m->codtitulo,
                    'codtipomovimentotitulo' => (int)$m->codtipomovimentotitulo,
                    'tipomovimentotitulo'    => optional($m->TipoMovimentoTitulo)->tipomovimentotitulo,
                    'transacao'              => $m->transacao,
                    'principal'              => abs($principal),
                    'juros'                  => (float)$m->juros,
                    'multa'                  => (float)$m->multa,
                    'desconto'               => (float)$m->desconto,
                    'total'                  => abs((float)$m->total),
                    'operacao'               => $principal < 0 ? 'CR' : 'DB',
                    'titulo' => $m->Titulo ? [
                        'codtitulo'   => (int)$m->Titulo->codtitulo,
                        'numero'      => $m->Titulo->numero,
                        'fatura'      => $m->Titulo->fatura,
                        'vencimento'  => $m->Titulo->vencimento,
                        'gerencial'   => (bool)$m->Titulo->gerencial,
                        'boleto'      => (bool)$m->Titulo->boleto,
                        'nossonumero' => $m->Titulo->nossonumero,
                        'codpessoa'   => (int)$m->Titulo->codpessoa,
                        'fantasia'    => optional($m->Titulo->Pessoa)->fantasia,
                        'codfilial'   => (int)$m->Titulo->codfilial,
                        'filial'      => optional($m->Titulo->Filial)->filial,
                        'codportador' => $m->Titulo->codportador,
                        'portador'    => optional($m->Titulo->Portador)->portador,
                    ] : null,
                ];
            })
            ->all();

        return [
            'codliquidacaotitulo' => (int)$this->codliquidacaotitulo,
            'codpessoa'           => (int)$this->codpessoa,
            'fantasia'            => optional($this->Pessoa)->fantasia,
            'codportador'         => $this->codportador,
            'portador'            => optional($this->Portador)->portador,
            'transacao'           => $this->transacao,
            'criacao'             => $this->criacao,
            'alteracao'           => $this->alteracao,
            'estornado'           => $this->estornado,
            'codperiodo'          => $this->codperiodo,
            'observacao'          => $this->observacao,
            'codusuariocriacao'   => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'valor'               => abs($valor),
            'operacao'            => $operacao,
            'recebimento'         => LiquidacaoTituloService::temRecebimento($this->resource),
            'pagamento'           => LiquidacaoTituloService::temPagamento($this->resource),
            'movimentos'          => $movimentos,
        ];
    }
}
