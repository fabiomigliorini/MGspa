<?php

namespace Mg\Pagamento;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

// Linha da listagem unica de pagamentos (M6.1 doc-3)
class PagamentoListaResource extends Resource
{
    // o que o pagamento quitou, numa linha: venda n, titulos, de -> para
    public static function documento(Pagamento $pag, string $origem): ?string
    {
        switch ($origem) {
            case PagamentoListaService::ORIGEM_VENDA:
                return 'Venda ' . formataCodigo($pag->codnegocio);
            case PagamentoListaService::ORIGEM_TITULO:
                $numeros = collect($pag->MovimentoTituloS)
                    ->filter(fn($m) => !$m->ehEstorno())
                    ->map(fn($m) => optional($m->Titulo)->numero)
                    ->filter()->unique()->values();
                if ($numeros->isEmpty()) {
                    return !empty($pag->codperiodocolaboradoracerto) ? 'Acerto RH' : null;
                }
                $texto = $numeros->take(2)->implode(', ');
                return ($numeros->count() > 2) ? $texto . ' +' . ($numeros->count() - 2) : $texto;
            case PagamentoListaService::ORIGEM_TRANSFERENCIA:
                return optional($pag->PortadorOrigem)->portador . ' → ' . optional($pag->PortadorDestino)->portador;
        }
        return PagamentoService::MOTIVOS[$pag->motivo] ?? 'Sem documento';
    }

    public function toArray($request)
    {
        $origem = PagamentoListaService::origem($this->resource);
        $portador = $this->portadorDoPagamento();
        $pessoa = PagamentoListaService::pessoa($this->resource);
        return [
            'codpagamento'              => (int) $this->codpagamento,
            'codliquidacaotituloantigo' => $this->codliquidacaotituloantigo,
            'transacao'                 => $this->transacao,
            'criacao'                   => $this->criacao,
            'estado'                    => $this->estado,
            'estadodescricao'           => PagamentoService::ESTADOS[$this->estado] ?? null,
            'origem'                    => $origem,
            'origemdescricao'           => PagamentoListaService::ORIGENS[$origem],
            'documento'                 => static::documento($this->resource, $origem),
            'codnegocio'                => $this->codnegocio,
            'codperiodocolaboradoracerto' => $this->codperiodocolaboradoracerto,
            'codpessoa'                 => $pessoa->codpessoa ?? null,
            'fantasia'                  => $pessoa->fantasia ?? null,
            'meio'                      => $this->meio,
            'meiodescricao'             => PagamentoService::descricao($this->resource),
            'parcelas'                  => $this->parcelas,
            'codmaquineta'              => $this->codmaquineta,
            'maquineta'                 => optional($this->Maquineta)->apelido,
            'codportador'               => $portador->codportador ?? null,
            'portador'                  => $portador->portador ?? null,
            'total'                     => (float) $this->total,
            'valortroco'                => $this->valortroco,
            'operacao'                  => PagamentoListaService::operacao($this->resource),
            'codpdv'                    => $this->codpdv,
            'pdv'                       => optional($this->Pdv)->apelido,
            'codusuariocriacao'         => $this->codusuariocriacao,
            'usuariocriacao'            => optional($this->UsuarioCriacao)->usuario,
            'observacoes'               => $this->observacoes,
        ];
    }
}
