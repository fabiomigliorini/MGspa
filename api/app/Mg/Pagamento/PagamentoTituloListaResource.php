<?php

namespace Mg\Pagamento;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

// Linha da listagem de Recebimentos e Pagamentos (M6 doc-3)
class PagamentoTituloListaResource extends Resource
{
    public function toArray($request)
    {
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
            'lancamento'                => $this->lancamento,
            'criacao'                   => $this->criacao,
            'cancelamento'              => $this->cancelamento,
            'codperiodocolaboradoracerto' => $this->codperiodocolaboradoracerto,
            'codusuariocriacao'         => $this->codusuariocriacao,
            'usuariocriacao'            => $this->usuariocriacao,
            'total'                     => (float) $this->total,
            'operacao'                  => PagamentoTituloDetalheResource::operacao($this->resource),
            'observacoes'               => $this->observacoes,
            'codpdv'                    => $this->codpdv,
            'pdv'                       => $this->codpdv ? optional($this->Pdv)->apelido : null,
        ];
    }
}
