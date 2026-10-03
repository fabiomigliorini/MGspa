<?php

namespace Mg\Portador;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Mg\Caixa\CaixaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaAutorizador;
use Mg\Usuario\Autorizador;

// Periodo do portador (M12 doc-3; tela /portador/{cod}/{codperiodo} do doc-4).
// Saldos gravados (R13), sempre visiveis (R16). Com `lancamentos`: as linhas
// do razao como extrato (texto da origem, saldo corrente), o resumo por
// origem e, na gaveta, o contado x sistema e os itens da sessao.
class PortadorPeriodoResource extends Resource
{
    // R9: as origens do resumo, na ordem do formulario de papel
    const RESUMO = [
        PagamentoListaService::ORIGEM_VENDA => 'Vendas',
        PagamentoListaService::ORIGEM_TITULO => 'Títulos',
        PagamentoListaService::ORIGEM_TRANSFERENCIA => 'Transferências',
        PagamentoListaService::ORIGEM_AVULSO => 'Avulsos e ajustes',
        PagamentoListaService::ORIGEM_ITEM => 'Itens do caixa',
    ];

    public bool $comLancamentos = false;

    public function comLancamentos(): self
    {
        $this->comLancamentos = true;
        return $this;
    }

    // R14: os periodos afetados por um movimento, completos
    public static function lista(Collection $periodos): array
    {
        return $periodos->map(fn ($p) => (new static($p->fresh()))->comLancamentos()->resolve())->all();
    }

    public function toArray($request)
    {
        $gaveta = $this->Portador->ehGaveta();
        $caixa = $this->Portador->ehCaixa();
        $ret = [
            'codportadorperiodo' => $this->codportadorperiodo,
            'codportador' => $this->codportador,
            'portador' => $this->Portador->portador,
            'tipo' => $this->Portador->tipo,
            'codfilial' => $this->Portador->codfilial,
            'filial' => optional($this->Portador->Filial)->filial,
            'ehGaveta' => $gaveta,
            'ehCaixa' => $caixa,
            'descricao' => PortadorPeriodoService::descricao($this->resource),
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'corrente' => empty($this->fim),
            'aberto' => empty($this->fechamento),
            'fechamento' => $this->fechamento,
            'usuarioabertura' => optional($this->UsuarioAbertura)->usuario,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'saldoinicial' => (float) $this->saldoinicial,
            'movimento' => round((float) $this->saldofinal - (float) $this->saldoinicial, 2),
            'saldofinal' => (float) $this->saldofinal,
            'observacoes' => $this->observacoes,
            'criacao' => $this->criacao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'usuariocriacao' => optional($this->UsuarioCriacao)->usuario,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => optional($this->UsuarioAlteracao)->usuario,
        ];
        if (!$this->comLancamentos) {
            return $ret;
        }
        $ret['lancamentos'] = $this->lancamentos($caixa);
        $ret['resumo'] = $this->resumo($ret['lancamentos']);
        if ($caixa) {
            $ret['contagem'] = $this->contagem();
        }
        if ($gaveta) {
            $ret['itens'] = CaixaService::itens($this->resource);
        }
        return $ret;
    }

    // o dinheiro contado na abertura e no fechamento e a diferenca para o
    // saldo inicial e final (so' aparece; o ajuste e' lancamento)
    private function contagem(): array
    {
        $ret = [];
        foreach (['inicial' => 'saldoinicial', 'final' => 'saldofinal'] as $momento => $saldo) {
            $contado = PortadorPeriodoService::contado($this->resource, $momento);
            $ret[$momento] = [
                'contagem' => static::objeto($this->{"contagem{$momento}"}),
                'contado' => $contado,
                'diferenca' => $contado === null ? 0.0 : round($contado - (float) $this->$saldo, 2),
            ];
        }
        return $ret;
    }

    // {cedula: quantidade} como objeto: o resource reindexa array de chave
    // numerica ({"50": 1} viraria [1])
    private static function objeto(?array $contagem): ?object
    {
        return $contagem === null ? null : (object) $contagem;
    }

    // as linhas do razao do periodo como extrato: o fato, a origem em texto,
    // o meio, o valor e o saldo corrente (sem as inativas)
    private function lancamentos(bool $caixa): array
    {
        $linhas = PortadorMovimento::where('codportadorperiodo', $this->codportadorperiodo)
            ->with(['Pagamento' => fn ($q) => $q->with(array_merge(PagamentoListaService::RELACOES, [
                'PortadorMovimentoS:codportadormovimento,codpagamento,codportador,codportadorperiodo,inativo',
            ]))])
            ->orderBy('transacao')
            ->orderBy('codportadormovimento')
            ->get();
        $saldo = (float) $this->saldoinicial;
        $codusuario = Auth::user()->codusuario ?? null;
        $financeiro = Autorizador::pode(['Financeiro']);
        $mutavel = !PortadorPeriodoService::imutavel($this->resource);
        return $linhas->map(function (PortadorMovimento $l) use (&$saldo, $caixa, $codusuario, $financeiro, $mutavel) {
            $pag = $l->Pagamento;
            $origem = PagamentoListaService::origem($pag);
            $ativa = empty($l->inativo);
            if ($ativa) {
                $saldo = round($saldo + (float) $l->valor, 2);
            }
            $transferencia = $ativa && $origem == PagamentoListaService::ORIGEM_TRANSFERENCIA;
            $avulso = $ativa
                && $origem == PagamentoListaService::ORIGEM_AVULSO
                && !empty($pag->motivo)
                && $pag->estado != PagamentoService::ESTADO_CANCELADO;
            return [
                'codportadormovimento' => $l->codportadormovimento,
                'codpagamento' => $l->codpagamento,
                'valor' => (float) $l->valor,
                'transacao' => $l->transacao,
                'inativo' => $l->inativo,
                'saldo' => $ativa ? $saldo : null,
                'estado' => $pag->estado,
                'origem' => $origem,
                'texto' => static::texto($pag, $origem, (float) $l->valor),
                'meiodescricao' => PagamentoService::descricao($pag),
                'origemdescricao' => PagamentoListaService::ORIGENS[$origem],
                'documento' => PagamentoListaResource::documento($pag, $origem),
                'contraparte' => $origem == PagamentoListaService::ORIGEM_TRANSFERENCIA
                    ? static::contraparte($pag, $l)
                    : null,
                'podeConfirmar' => $transferencia && TransferenciaAutorizador::podeConfirmar($pag),
                'podeCancelar' => $transferencia && TransferenciaAutorizador::podeCancelar($pag),
                // caixa: so' quem lancou, com o caixa nao fechado; demais: Financeiro
                'podeCancelarAvulso' => $avulso && $mutavel && ($caixa
                    ? $pag->codusuariocriacao == $codusuario
                    : $financeiro),
            ];
        })->all();
    }

    // o outro lado da transferencia: o portador e o periodo onde o valor caiu
    // (a linha ativa; cancelada, a ultima)
    private static function contraparte(Pagamento $pag, PortadorMovimento $linha): ?array
    {
        $outra = $pag->PortadorMovimentoS
            ->where('codportador', '!=', $linha->codportador)
            ->sortBy(fn ($m) => [empty($m->inativo) ? 0 : 1, -$m->codportadormovimento])
            ->first();
        if (!$outra) {
            return null;
        }
        $portador = $outra->codportador == $pag->codportadordestino ? $pag->PortadorDestino : $pag->PortadorOrigem;
        return [
            'codportador' => $outra->codportador,
            'portador' => optional($portador)->portador,
            'codportadorperiodo' => $outra->codportadorperiodo,
        ];
    }

    // R10: "Venda 123456 · João", "Sangria → Cofre Centro", "Baixa de 3
    // titulos · José", "Ajuste de caixa", "Item: Chips de celular"
    public static function texto(Pagamento $pag, string $origem, float $valor): string
    {
        $pessoa = optional(PagamentoListaService::pessoa($pag))->fantasia;
        $comPessoa = fn ($t) => $pessoa ? "{$t} · {$pessoa}" : $t;
        switch ($origem) {
            case PagamentoListaService::ORIGEM_VENDA:
                return $comPessoa('Venda ' . formataCodigo($pag->codnegocio));
            case PagamentoListaService::ORIGEM_TITULO:
                $numeros = collect($pag->MovimentoTituloS)
                    ->filter(fn ($m) => !$m->ehEstorno())
                    ->map(fn ($m) => optional($m->Titulo)->numero)
                    ->filter()->unique()->values();
                if ($numeros->isEmpty()) {
                    return $comPessoa(!empty($pag->codperiodocolaboradoracerto) ? 'Acerto RH' : 'Títulos');
                }
                return $comPessoa($numeros->count() == 1
                    ? 'Título ' . $numeros->first()
                    : 'Baixa de ' . $numeros->count() . ' títulos');
            case PagamentoListaService::ORIGEM_TRANSFERENCIA:
                if ($pag->meio == PagamentoService::MEIO_DEPOSITO) {
                    $tipo = 'Depósito';
                } elseif ($pag->PortadorOrigem->ehGaveta()) {
                    $tipo = 'Sangria';
                } elseif ($pag->PortadorDestino->ehGaveta()) {
                    $tipo = 'Suprimento';
                } else {
                    $tipo = 'Transferência';
                }
                return $valor < 0
                    ? "{$tipo} → {$pag->PortadorDestino->portador}"
                    : "{$tipo} ← {$pag->PortadorOrigem->portador}";
            case PagamentoListaService::ORIGEM_ITEM:
                return 'Item: ' . optional(optional($pag->CaixaItemLancamento)->CaixaItem)->item;
        }
        $motivo = PagamentoService::MOTIVOS[$pag->motivo] ?? 'Avulso';
        return $pag->observacoes ? "{$motivo} · {$pag->observacoes}" : $motivo;
    }

    // R9: entradas e saidas das linhas ativas por origem
    private function resumo(array $lancamentos): array
    {
        $ret = [];
        foreach (static::RESUMO as $origem => $descricao) {
            $linhas = array_filter($lancamentos, fn ($l) => $l['origem'] == $origem && empty($l['inativo']));
            if (empty($linhas)) {
                continue;
            }
            $ret[] = [
                'origem' => $origem,
                'descricao' => $descricao,
                'entrada' => round(array_sum(array_map(fn ($l) => max($l['valor'], 0), $linhas)), 2),
                'saida' => round(array_sum(array_map(fn ($l) => max(-$l['valor'], 0), $linhas)), 2),
                'quantidade' => count($linhas),
            ];
        }
        return $ret;
    }
}
