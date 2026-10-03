<?php

namespace Mg\Portador;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Collection;
use Mg\Caixa\CaixaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;

// Periodo do portador (tela /portador/{cod}/{codperiodo}; doc-4, redefinicao
// do dinheiro). Situacao aberto/pendente/fechado; com `lancamentos`: as linhas
// do movimento como extrato (pagamento, ajuste, transferencia), o resumo por
// origem e, na especie, as contagens e a diferenca.
class PortadorPeriodoResource extends Resource
{
    // origem da linha: as do pagamento (V, T, I, A) e as do movimento
    const ORIGEM_AJUSTE = 'J';
    const ORIGEM_TRANSFERENCIA = 'X';

    // o resumo, na ordem do formulario de papel
    const RESUMO = [
        PagamentoListaService::ORIGEM_VENDA => 'Vendas',
        PagamentoListaService::ORIGEM_TITULO => 'Títulos e vales',
        PagamentoListaService::ORIGEM_ITEM => 'Itens do caixa',
        self::ORIGEM_TRANSFERENCIA => 'Transferências',
        self::ORIGEM_AJUSTE => 'Ajustes',
        PagamentoListaService::ORIGEM_AVULSO => 'Taxas, tarifas e rendimentos',
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

    public static function situacao(PortadorPeriodo $p): string
    {
        return $p->fechado() ? 'fechado' : ($p->aberto() ? 'aberto' : 'pendente');
    }

    public function toArray($request)
    {
        $caixa = $this->Portador->ehCaixa();
        $ret = [
            'codportadorperiodo' => $this->codportadorperiodo,
            'codportador' => $this->codportador,
            'portador' => $this->Portador->portador,
            'tipo' => $this->Portador->tipo,
            'codfilial' => $this->Portador->codfilial,
            'filial' => optional($this->Portador->Filial)->filial,
            'ehGaveta' => $this->Portador->ehGaveta(),
            'ehCaixa' => $caixa,
            'descricao' => PortadorPeriodoService::descricao($this->resource),
            'situacao' => static::situacao($this->resource),
            'inicio' => $this->inicio,
            'fim' => $this->fim,
            'corrente' => empty($this->fim),
            // nao fechado: aceita lancamento
            'aberto' => !$this->fechado(),
            'fechamento' => $this->fechamento,
            'usuarioabertura' => optional($this->UsuarioAbertura)->usuario,
            'usuariofechamento' => optional($this->UsuarioFechamento)->usuario,
            'saldoinicial' => (float) $this->saldoinicial,
            'movimento' => round((float) $this->saldofinal - (float) $this->saldoinicial, 2),
            'saldofinal' => (float) $this->saldofinal,
            'diferenca' => $this->diferenca,
            'tolerancia' => (float) $this->Portador->tolerancia,
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
        $ret['lancamentos'] = $this->lancamentos();
        $ret['resumo'] = $this->resumo($ret['lancamentos']);
        if ($caixa) {
            $ret['contagem'] = $this->contagem();
            $ret['pendentes'] = PortadorLancamentoService::pendentes($this->resource);
        }
        return $ret;
    }

    // o dinheiro contado (cedulas e moedas) no inicio e no fim e a diferenca
    // para o saldo inicial e final (a inicial so' confere)
    private function contagem(): array
    {
        $ret = [];
        foreach (['inicial' => 'saldoinicial', 'final' => 'saldofinal'] as $momento => $saldo) {
            $contado = PortadorPeriodoService::contado($this->resource, $momento);
            $ret[$momento] = [
                'contagem' => static::objeto($this->{"contagem{$momento}"}),
                'contado' => $contado,
                'diferenca' => $contado === null ? null : round($contado - (float) $this->$saldo, 2),
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

    // as linhas do periodo como extrato: o fato, a origem em texto, o detalhe,
    // o valor e o saldo corrente (das que valem)
    private function lancamentos(): array
    {
        $linhas = PortadorMovimento::where('codportadorperiodo', $this->codportadorperiodo)
            ->with([
                'Pagamento' => fn ($q) => $q->with(PagamentoListaService::RELACOES),
                'Par.Portador:codportador,portador,tipo',
                'UsuarioCriacao:codusuario,usuario',
                'UsuarioCancelamento:codusuario,usuario',
            ])
            ->orderBy('transacao')
            ->orderBy('codportadormovimento')
            ->get();
        $portador = $this->Portador;
        $saldo = (float) $this->saldoinicial;
        $mutavel = !$this->fechado();
        $operador = PortadorAutorizador::pode($portador->codportador, PortadorUsuario::PAPEL_OPERADOR);
        return $linhas->map(function (PortadorMovimento $l) use (&$saldo, $portador, $mutavel, $operador) {
            $l->setRelation('Portador', $portador);
            $valendo = $l->valendo();
            if ($valendo) {
                $saldo = round($saldo + (float) $l->valor, 2);
            }
            $ret = [
                'codportadormovimento' => $l->codportadormovimento,
                'tipo' => $l->tipo,
                'codpagamento' => $l->codpagamento,
                'valor' => (float) $l->valor,
                'transacao' => $l->transacao,
                'cancelado' => !$valendo,
                'saldo' => $valendo ? $saldo : null,
                'estado' => $l->estado,
                'observacoes' => $l->observacoes,
                'justificativa' => $l->justificativa,
                'usuariocriacao' => optional($l->UsuarioCriacao)->usuario,
                'usuariocancelamento' => optional($l->UsuarioCancelamento)->usuario,
                'contraparte' => null,
                'podeConfirmar' => false,
                'podeCancelar' => false,
            ];
            switch ($l->tipo) {
                case PortadorMovimento::TIPO_AJUSTE:
                    return array_merge($ret, [
                        'origem' => static::ORIGEM_AJUSTE,
                        'texto' => 'Ajuste',
                        'detalhe' => $l->observacoes,
                        'podeCancelar' => $valendo && $mutavel && $operador,
                    ]);
                case PortadorMovimento::TIPO_TRANSFERENCIA:
                    return array_merge($ret, [
                        'origem' => static::ORIGEM_TRANSFERENCIA,
                        'texto' => static::textoTransferencia($l),
                        'detalhe' => $l->observacoes,
                        'contraparte' => $l->Par ? [
                            'codportador' => $l->Par->codportador,
                            'portador' => optional($l->Par->Portador)->portador,
                            'codportadorperiodo' => $l->Par->codportadorperiodo,
                        ] : null,
                        'podeConfirmar' => PortadorLancamentoService::podeConfirmar($l),
                        'podeCancelar' => $valendo && PortadorLancamentoService::podeCancelar($l),
                    ]);
            }
            $pag = $l->Pagamento;
            $origem = PagamentoListaService::origem($pag);
            return array_merge($ret, [
                'origem' => $origem,
                'estado' => $pag->estado,
                'texto' => static::texto($pag, $origem),
                'detalhe' => PagamentoService::descricao($pag),
                'documento' => PagamentoListaResource::documento($pag, $origem),
                // taxa, tarifa, rendimento (banco)
                'podeCancelar' => $valendo && $mutavel && $operador
                    && $origem == PagamentoListaService::ORIGEM_AVULSO
                    && !empty($pag->motivo),
            ]);
        })->all();
    }

    // "Sangria → Cofre Centro", "Reforço ← Cofre", "Depósito → BB",
    // "Transferência ← Caixa Atacado"
    public static function textoTransferencia(PortadorMovimento $l): string
    {
        $saida = $l->valor < 0;
        $outro = optional($l->Par)->Portador;
        $origem = $saida ? $l->Portador : $outro;
        $destino = $saida ? $outro : $l->Portador;
        if ($origem && $destino && $origem->tipo == Portador::TIPO_ESPECIE && $destino->tipo == Portador::TIPO_BANCO) {
            $tipo = 'Depósito';
        } elseif ($origem && $origem->ehGaveta()) {
            $tipo = 'Sangria';
        } elseif ($destino && $destino->ehGaveta()) {
            $tipo = 'Reforço';
        } else {
            $tipo = 'Transferência';
        }
        $nome = optional($outro)->portador;
        return $saida ? "{$tipo} → {$nome}" : "{$tipo} ← {$nome}";
    }

    // "Venda 123456 · João", "Baixa de 3 titulos · José", "Item: Chips",
    // "Tarifa · manutencao"
    public static function texto(Pagamento $pag, string $origem): string
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
            case PagamentoListaService::ORIGEM_ITEM:
                return 'Item: ' . optional(optional($pag->CaixaItemLancamento)->CaixaItem)->item;
        }
        $motivo = PagamentoService::MOTIVOS[$pag->motivo] ?? 'Avulso';
        return $pag->observacoes ? "{$motivo} · {$pag->observacoes}" : $motivo;
    }

    // entradas e saidas das linhas que valem, por origem
    private function resumo(array $lancamentos): array
    {
        $ret = [];
        foreach (static::RESUMO as $origem => $descricao) {
            $linhas = array_filter($lancamentos, fn ($l) => $l['origem'] == $origem && !$l['cancelado']);
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
