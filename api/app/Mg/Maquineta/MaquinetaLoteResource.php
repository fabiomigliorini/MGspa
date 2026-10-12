<?php

namespace Mg\Maquineta;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\Conferencia\ConferenciaPagamentoResource;
use Mg\Portador\LancamentoDataService;

// Periodo da maquineta (TASK-188 M9.8; no banco, lote). Sem `comLancamentos`,
// so' o cabecalho (as abas da tela); com ele, o sistema no formato do
// relatorio da maquineta (modalidade -> bandeira), as fotos e os lancamentos. A visao e' aberta: o sistema vem em qualquer situacao.
class MaquinetaLoteResource extends Resource
{
    public bool $comLancamentos = false;
    public ?float $total = null;
    public ?bool $temFoto = null;

    public function comLancamentos(): self
    {
        $this->comLancamentos = true;
        return $this;
    }

    // as abas: total do sistema e se tem foto, calculados de uma vez para
    // todos os periodos
    public static function lista($lotes, array $totais, array $comFoto)
    {
        return $lotes->map(function ($l) use ($totais, $comFoto) {
            $r = new static($l);
            $r->total = $totais[$l->codmaquinetalote] ?? 0.0;
            $r->temFoto = isset($comFoto[$l->codmaquinetalote]);
            return $r;
        });
    }

    public function toArray($request)
    {
        $ret = [
            'codmaquinetalote' => $this->codmaquinetalote,
            'codmaquineta' => $this->codmaquineta,
            'abertura' => $this->abertura,
            'fim' => $this->fim,
            'fechamento' => $this->fechamento,
            'situacao' => $this->situacao(),
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'quantidadeinformada' => $this->quantidadeinformada,
            'totalinformado' => $this->totalinformado,
            'quantidadesistema' => $this->quantidadesistema,
            'totalsistema' => $this->totalsistema,
            'observacoes' => $this->observacoes,
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => $this->usuariocriacao,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => $this->usuarioalteracao,
        ];
        if (!$this->comLancamentos) {
            $ret['total'] = $this->total;
            $ret['semBordero'] = !$this->aberto() && $this->temFoto === false;
            return $ret;
        }
        $sistema = MaquinetaLoteService::sistema($this->codmaquinetalote);
        $fotos = MaquinetaLoteService::fotos($this->resource);
        $ret['sistema'] = $sistema;
        $ret['total'] = $sistema['total'];
        $ret['fotos'] = $fotos;
        $ret['semBordero'] = !$this->aberto() && empty($fotos);
        $ret['anterior'] = optional(MaquinetaLoteService::anterior($this->resource))->only(['codmaquinetalote', 'fechamento']);
        $pagamentos = MaquinetaLoteService::pagamentos($this->codmaquinetalote);
        $ret['lancamentos'] = ConferenciaPagamentoResource::collection($pagamentos);
        // a ultima data alterada (TASK-204) da venda e do cancelamento:
        // {codpagamento: {de, usuario, justificativa}}
        $cods = $pagamentos->pluck('codpagamento')->all();
        $ret['datasAlteradas'] = (object) LancamentoDataService::alteracoesPagamento($cods);
        $ret['cancelamentosAlterados'] = (object) LancamentoDataService::alteracoesPagamento($cods, 'cancelamento');
        return $ret;
    }
}
