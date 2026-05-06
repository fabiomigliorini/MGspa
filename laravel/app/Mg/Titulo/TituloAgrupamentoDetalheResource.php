<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloAgrupamentoDetalheResource extends Resource
{
    public function toArray($request)
    {
        $debito = (float)$this->debito;
        $credito = (float)$this->credito;
        $valor = $debito - $credito;
        $operacao = ($valor < 0) ? 'CR' : 'DB';

        $titulosGerados = collect($this->TituloS ?? [])->map(function ($t) {
            $valorT = (float)$t->debito - (float)$t->credito;
            return [
                'codtitulo'   => (int)$t->codtitulo,
                'numero'      => $t->numero,
                'vencimento'  => $t->vencimento,
                'codpessoa'   => (int)$t->codpessoa,
                'fantasia'    => optional($t->Pessoa)->fantasia,
                'codfilial'   => (int)$t->codfilial,
                'filial'      => optional($t->Filial)->filial,
                'codportador' => $t->codportador,
                'portador'    => optional($t->Portador)->portador,
                'gerencial'   => (bool)$t->gerencial,
                'boleto'      => (bool)$t->boleto,
                'nossonumero' => $t->nossonumero,
                'valor'       => abs($valorT),
                'operacao'    => $valorT < 0 ? 'CR' : 'DB',
            ];
        })->all();

        $titulosBaixados = collect($this->MovimentoTituloS ?? [])
            ->filter(function ($m) {
                if (optional($m->TipoMovimentoTitulo)->estorno) return false;
                if (!$m->Titulo) return false;
                // exclui os títulos gerados pelo próprio agrupamento
                return $m->Titulo->codtituloagrupamento !== $this->codtituloagrupamento;
            })
            ->values()
            ->map(function ($m) {
                $valorM = (float)$m->debito - (float)$m->credito;
                return [
                    'codmovimentotitulo'  => (int)$m->codmovimentotitulo,
                    'codtipomovimentotitulo' => (int)$m->codtipomovimentotitulo,
                    'tipomovimentotitulo' => optional($m->TipoMovimentoTitulo)->tipomovimentotitulo,
                    'transacao'           => $m->transacao,
                    'valor'               => abs($valorM),
                    'operacao'            => $valorM < 0 ? 'CR' : 'DB',
                    'titulo' => $m->Titulo ? [
                        'codtitulo'   => (int)$m->Titulo->codtitulo,
                        'numero'      => $m->Titulo->numero,
                        'vencimento'  => $m->Titulo->vencimento,
                        'codpessoa'   => (int)$m->Titulo->codpessoa,
                        'fantasia'    => optional($m->Titulo->Pessoa)->fantasia,
                        'codfilial'   => (int)$m->Titulo->codfilial,
                        'filial'      => optional($m->Titulo->Filial)->filial,
                        'gerencial'   => (bool)$m->Titulo->gerencial,
                        'boleto'      => (bool)$m->Titulo->boleto,
                        'nossonumero' => $m->Titulo->nossonumero,
                        'codportador' => $m->Titulo->codportador,
                        'portador'    => optional($m->Titulo->Portador)->portador,
                    ] : null,
                ];
            })
            ->all();

        return [
            'codtituloagrupamento' => (int)$this->codtituloagrupamento,
            'codpessoa'            => (int)$this->codpessoa,
            'fantasia'             => optional($this->Pessoa)->fantasia,
            'emissao'              => $this->emissao,
            'cancelamento'         => $this->cancelamento,
            'criacao'              => $this->criacao,
            'alteracao'            => $this->alteracao,
            'observacao'           => $this->observacao,
            'codusuariocriacao'    => $this->codusuariocriacao,
            'usuariocriacao'       => optional($this->UsuarioCriacao)->usuario,
            'usuarioalteracao'     => optional($this->UsuarioAlteracao)->usuario,
            'debito'               => $debito,
            'credito'              => $credito,
            'valor'                => abs($valor),
            'operacao'             => $operacao,
            'titulos_gerados'      => $titulosGerados,
            'titulos_baixados'     => $titulosBaixados,
        ];
    }
}
