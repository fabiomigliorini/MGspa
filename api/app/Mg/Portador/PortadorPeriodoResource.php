<?php

namespace Mg\Portador;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Illuminate\Support\Collection;
use Mg\Caixa\CaixaItem;
use Mg\Caixa\CaixaItemService;
use Mg\Caixa\CaixaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaResource;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;

// Periodo do portador (tela /portador/{cod}/{codperiodo}; doc-4, redefinicao
// do dinheiro). Situacao aberto/pendente/fechado; com `lancamentos`: as linhas
// do movimento como extrato (pagamento, ajuste, transferencia, item), o
// resumo por origem e, na especie, as contagens e a diferenca (com os itens do
// caixa e os precos que ja' passaram pelo portador).
class PortadorPeriodoResource extends Resource
{
    // origem da linha: as do pagamento (V, T, A) e as do movimento
    const ORIGEM_AJUSTE = 'J';
    const ORIGEM_TRANSFERENCIA = 'X';
    const ORIGEM_ITEM = 'I';
    const ORIGEM_MAQUINETA = 'M';

    // o resumo, na ordem do formulario de papel
    const RESUMO = [
        PagamentoListaService::ORIGEM_VENDA => 'Vendas',
        PagamentoListaService::ORIGEM_TITULO => 'Títulos e vales',
        self::ORIGEM_ITEM => 'Itens do caixa',
        self::ORIGEM_MAQUINETA => 'Maquinetas de parceiros',
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
            'usuariocriacao' => $this->usuariocriacao,
            'alteracao' => $this->alteracao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
            'usuarioalteracao' => $this->usuarioalteracao,
        ];
        if (!$this->comLancamentos) {
            return $ret;
        }
        $ret['lancamentos'] = $this->lancamentos();
        $ret['resumo'] = $this->resumo($ret['lancamentos']);
        if ($caixa) {
            $anterior = PortadorPeriodoService::anterior($this->resource);
            $ret['contagem'] = $this->contagem($anterior);
            $ret['pendentes'] = PortadorLancamentoService::pendentes($this->resource);
            $ret['itens'] = $this->itens($anterior);
            $ret['maquinetas'] = CaixaItemService::ativos(CaixaItem::MODO_MAQUINETA)
                ->map(fn (CaixaItem $i) => ['codcaixaitem' => $i->codcaixaitem, 'item' => $i->item])
                ->values()->all();
        }
        return $ret;
    }

    // o contado (cedulas, moedas e itens) no inicio e no fim e a diferenca
    // para o saldo inicial e final (a inicial so' confere); `anterior`: a
    // contagem final do periodo anterior, para copiar na inicial
    private function contagem(?PortadorPeriodo $anterior): array
    {
        $ret = [];
        foreach (['inicial' => 'saldoinicial', 'final' => 'saldofinal'] as $momento => $saldo) {
            $contado = PortadorPeriodoService::contado($this->resource, $momento);
            $ret[$momento] = [
                'contagem' => static::objeto($this->{"contagem{$momento}"}),
                'itens' => static::objeto($this->{"contagemitens{$momento}"}),
                'contado' => $contado,
                'diferenca' => $contado === null ? null : round($contado - (float) $this->$saldo, 2),
            ];
        }
        $ret['anterior'] = $anterior && ($anterior->contagemfinal !== null || $anterior->contagemitensfinal !== null) ? [
            'contagem' => static::objeto($anterior->contagemfinal),
            'itens' => static::objeto($anterior->contagemitensfinal),
        ] : null;
        return $ret;
    }

    // os itens que contam como cedula (a maquineta de parceiro nao se conta)
    // para lancar (todos os ativos) e contar (`contar`: os que estao
    // no portador, de um dia para o outro ate' zerar), cada um com os precos (e
    // descricoes) que ja' passaram: a contagem final do anterior, as deste
    // periodo e as entradas e saidas dele que valem
    private function itens(?PortadorPeriodo $anterior): array
    {
        $fontes = [$this->contagemitensinicial, $this->contagemitensfinal];
        if ($anterior) {
            $fontes[] = $anterior->contagemitensfinal;
        }
        $linhas = [];
        foreach ($fontes as $contagem) {
            foreach ($contagem ?? [] as $cod => $ls) {
                $linhas[(int) $cod] = array_merge($linhas[(int) $cod] ?? [], $ls);
            }
        }
        PortadorMovimento::where('codportadorperiodo', $this->codportadorperiodo)
            ->where('tipo', PortadorMovimento::TIPO_ITEM)
            ->where('estado', '<>', PortadorMovimento::ESTADO_CANCELADO)
            ->get(['codcaixaitem', 'itens'])
            ->each(function ($m) use (&$linhas) {
                $linhas[$m->codcaixaitem] = array_merge($linhas[$m->codcaixaitem] ?? [], $m->itens ?? []);
            });
        $itens = CaixaItemService::ativos(CaixaItem::MODO_CEDULA)
            ->concat(CaixaItem::whereIn('codcaixaitem', array_keys($linhas))
                ->where('modo', CaixaItem::MODO_CEDULA)->get())
            ->unique('codcaixaitem')
            ->sortBy('item');
        return $itens->map(fn (CaixaItem $i) => [
            'codcaixaitem' => $i->codcaixaitem,
            'item' => $i->item,
            'inativo' => $i->inativo,
            'contar' => !empty($linhas[$i->codcaixaitem]),
            'linhas' => array_map(
                fn ($l) => ['preco' => $l['preco'], 'descricao' => $l['descricao']],
                CaixaItemService::linhas(array_map(fn ($l) => array_merge($l, ['quantidade' => 1]), $linhas[$i->codcaixaitem] ?? []))
            ),
        ])->values()->all();
    }

    // {cedula: quantidade} como objeto: o resource reindexa array de chave
    // numerica ({"50": 1} viraria [1])
    private static function objeto(?array $contagem): ?object
    {
        return $contagem === null ? null : (object) $contagem;
    }

    // as linhas do periodo como extrato: o fato, a origem em texto, o detalhe,
    // o valor e o saldo corrente (das que valem). No caixa do PDV (a gaveta
    // dele, livre), as acoes da linha sao so' as do caixa: cancelar a
    // transferencia a confirmar e o bordero da maquineta e anexar a foto
    private function lancamentos(): array
    {
        $linhas = PortadorMovimento::where('codportadorperiodo', $this->codportadorperiodo)
            ->with([
                'Pagamento' => fn ($q) => $q->with(PagamentoListaService::RELACOES),
                'Par.Portador:codportador,portador,tipo',
                'CaixaItem:codcaixaitem,item',
                'UsuarioCriacao:codusuario,usuario',
                'UsuarioCancelamento:codusuario,usuario',
            ])
            ->orderBy('transacao')
            ->orderBy('codportadormovimento')
            ->get();
        $portador = $this->Portador;
        $saldo = (float) $this->saldoinicial;
        $mutavel = !$this->fechado();
        $pdv = PortadorAutorizador::livre() == $portador->codportador;
        $operador = !$pdv && PortadorAutorizador::pode($portador->codportador, PortadorUsuario::PAPEL_OPERADOR);
        return $linhas->map(function (PortadorMovimento $l) use (&$saldo, $portador, $mutavel, $operador, $pdv) {
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
                'usuariocriacao' => $l->usuariocriacao,
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
                case PortadorMovimento::TIPO_ITEM:
                    return array_merge($ret, [
                        'origem' => static::ORIGEM_ITEM,
                        'texto' => ($l->valor < 0 ? 'Saída: ' : 'Entrada: ') . optional($l->CaixaItem)->item,
                        'detalhe' => static::detalheItem($l),
                        'codcaixaitem' => $l->codcaixaitem,
                        'itens' => $l->itens,
                        'podeCancelar' => $valendo && $mutavel && $operador,
                    ]);
                case PortadorMovimento::TIPO_MAQUINETA:
                    $fotos = PortadorLancamentoService::fotos($l->codportadormovimento);
                    return array_merge($ret, [
                        'origem' => static::ORIGEM_MAQUINETA,
                        'texto' => ($l->valor < 0 ? 'Devolução: ' : 'Borderô: ') . optional($l->CaixaItem)->item,
                        'detalhe' => $l->observacoes,
                        'codcaixaitem' => $l->codcaixaitem,
                        'fotos' => $fotos,
                        'semBordero' => $valendo && empty($fotos),
                        'podeAnexar' => $valendo && ($operador || $pdv),
                        'podeCancelar' => $valendo && $mutavel && ($operador || $pdv),
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
                        'podeConfirmar' => !$pdv && PortadorLancamentoService::podeConfirmar($l),
                        'podeCancelar' => $valendo && $mutavel && ($pdv
                            ? PortadorLancamentoService::cancelaNoPdv($l)
                            : PortadorLancamentoService::podeCancelar($l)),
                    ]);
            }
            $pag = $l->Pagamento;
            $origem = PagamentoListaService::origem($pag);
            return array_merge($ret, [
                'origem' => $origem,
                'estado' => $pag->estado,
                // a linha leva ao negocio; sem ele, ao pagamento
                'codnegocio' => $pag->codnegocio,
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

    // "10 × 10,00 Claro pré · 5 × 20,00 · observacao"
    public static function detalheItem(PortadorMovimento $l): string
    {
        $partes = static::partesLinhas($l->itens ?? []);
        if ($l->observacoes) {
            $partes[] = $l->observacoes;
        }
        return implode(' · ', $partes);
    }

    // "10 × 10,00 Claro pré · 5 × 20,00"
    public static function textoLinhas(array $linhas): string
    {
        return implode(' · ', static::partesLinhas($linhas));
    }

    private static function partesLinhas(array $linhas): array
    {
        return array_map(
            fn ($i) => trim($i['quantidade'] . ' × ' . formataNumero($i['preco']) . ' ' . ($i['descricao'] ?? '')),
            $linhas
        );
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

    // "Venda 123456 · João", "Baixa de 3 titulos · José", "Tarifa · manutencao"
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
