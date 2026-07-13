<?php

namespace Mg\Contrato;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class ContratoResource extends Resource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // remove relações em snake_case que o parent injeta (Laravel serializa
        // relação carregada em snake por padrão) — reexpostas em PascalCase abaixo.
        unset(
            $ret['pessoa'],
            $ret['cultura'],
            $ret['safra'],
            $ret['filial'],
            $ret['portador'],
            $ret['corretora'],
            $ret['cooperativa'],
            $ret['contrato_fixacao_s'],
            $ret['contrato_pagamento_s'],
            $ret['contrato_nota_s'],
            $ret['movimento_grao_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // Reconciliacao fisica em KG (unidade de trabalho) com sacas derivadas.
        // O contrato negocia em sacas (quantidade) + R$/saca; o embarque grava
        // kg (rateio bruto-tara). Ponte: pesosaca da cultura (default 60).
        // carregadokg vem do withSum (ContratoService); demais sao derivados.
        $pesosaca = (float) ($this->Cultura->pesosaca ?? 60) ?: 60;
        $carregadokg = (float) $this->carregadokg; // entregue (SUM liquido no extrato)
        // quantidade NULL = volume em aberto (leva o saldo do silo; sem teto).
        $emaberto = $this->quantidade === null;
        $contratadokg = (float) $this->quantidade * $pesosaca;
        $ret['operacao'] = $this->operacao;
        $ret['volumeemaberto'] = $emaberto;
        $ret['tipo'] = $this->tipoDerivado($emaberto);
        $ret['pesosaca'] = $pesosaca;
        $ret['carregadokg'] = $carregadokg;
        $ret['valornf'] = (float) $this->valornf; // R$ das NFs por contrato (0 ate emitir NFe)
        $ret['contratadokg'] = $contratadokg;
        $ret['carregadosc'] = $pesosaca > 0 ? round($carregadokg / $pesosaca, 2) : 0.0;
        // saldo so faz sentido em contrato com teto; volume em aberto -> null.
        $ret['saldokg'] = $emaberto ? null : max(0, $contratadokg - $carregadokg);

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Pessoa'] = $this->whenLoaded('Pessoa');
        $ret['Cultura'] = $this->whenLoaded('Cultura');
        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Filial'] = $this->whenLoaded('Filial');
        $ret['Portador'] = $this->whenLoaded('Portador');
        $ret['Corretora'] = $this->whenLoaded('Corretora');
        $ret['Cooperativa'] = $this->whenLoaded('Cooperativa');

        // Plano de emissão de NF (operação triangular).
        if ($this->relationLoaded('ContratoNotaS')) {
            $ret['ContratoNotaS'] = ContratoNotaResource::collection($this->ContratoNotaS);
        }

        // Fixações (com Moeda + travas de câmbio carregadas em WITH_DETALHE).
        if ($this->relationLoaded('ContratoFixacaoS')) {
            $ret['ContratoFixacaoS'] = ContratoFixacaoResource::collection($this->ContratoFixacaoS);
        }

        // Recebimentos agora vivem POR FIXAÇÃO (dentro de cada ContratoFixacaoResource).

        // Entregas no extrato (cada carga que moveu este contrato).
        if ($this->relationLoaded('MovimentoGraoS')) {
            $ret['MovimentoGraoS'] = $this->MovimentoGraoS
                ->filter(fn ($m) => $m->inativo === null)
                ->map(function ($m) use ($pesosaca) {
                    $kg = (float) $m->liquido;
                    return [
                        'codmovimentograo' => (int) $m->codmovimentograo,
                        'codcarga' => $m->codcarga !== null ? (int) $m->codcarga : null,
                        'codcontrato' => (int) $m->codcontrato,
                        'manual' => (bool) $m->manual,
                        'data' => $m->data,
                        'quantidadekg' => $kg,
                        'quantidadesc' => $pesosaca > 0 ? round($kg / $pesosaca, 2) : 0.0,
                        'observacao' => $m->observacao,
                        'Carga' => $m->relationLoaded('Carga') ? $m->Carga : null,
                    ];
                })
                ->values();
        }

        // Cálculo do líquido (motor fiscal) — só no detalhe (fixações carregadas),
        // pra não disparar N+1 na listagem.
        if ($this->relationLoaded('ContratoFixacaoS')) {
            $ret['calculo'] = ContratoCalculoService::calcularDoContrato($this->resource);
            $ret['fixadoresumo'] = $this->fixadoResumo();
        }

        return $ret;
    }

    /**
     * Resumo do "fixado" p/ o KPI, POR MOEDA de liquidação (câmbio travado migra
     * o valor de US$ pra R$). Buckets:
     *   - R$ firme: fixações BRL (cheias) + parte TRAVADA das US$ (em R$).
     *   - US$/€ a converter: o saldo ainda sem câmbio de cada moeda estrangeira.
     * Cada bucket traz total, sacas e PREÇO MÉDIO por saca (na moeda do bucket).
     */
    protected function fixadoResumo(): array
    {
        $fixacoes = $this->ContratoFixacaoS->whereNull('inativo');

        $firmeTotal = 0.0;
        $firmeSacas = 0.0;
        $estrangeiras = []; // iso => ['total' => saldomoeda, 'sacas' => sacas flutuantes]

        foreach ($fixacoes as $f) {
            $iso = $f->Moeda->iso ?? 'BRL';
            $preco = (float) $f->preco;
            $totalbrl = (float) $f->totalbrl;

            if ($iso === 'BRL') {
                $firmeTotal += $totalbrl;
                $firmeSacas += (float) $f->quantidade;
                continue;
            }
            // Estrangeira: a parte travada já é R$ firme; o saldo fica a converter.
            $sacasTravadas = $preco > 0 ? ((float) $f->totalmoeda - (float) $f->saldomoeda) / $preco : 0;
            $firmeTotal += $totalbrl;
            $firmeSacas += $sacasTravadas;

            $saldo = (float) $f->saldomoeda;
            if ($saldo > 0.005) {
                $estrangeiras[$iso] ??= ['total' => 0.0, 'sacas' => 0.0];
                $estrangeiras[$iso]['total'] += $saldo;
                $estrangeiras[$iso]['sacas'] += $preco > 0 ? $saldo / $preco : 0;
            }
        }

        $bucket = fn ($iso, $firme, $total, $sacas) => [
            'iso' => $iso,
            'firme' => $firme,
            'total' => round($total, 2),
            'sacas' => round($sacas, 0),
            'precomedio' => $sacas > 0 ? round($total / $sacas, 2) : 0,
        ];

        $buckets = [];
        if ($firmeTotal > 0.005 || $firmeSacas > 0) {
            $buckets[] = $bucket('BRL', true, $firmeTotal, $firmeSacas);
        }
        foreach ($estrangeiras as $iso => $b) {
            $buckets[] = $bucket($iso, false, $b['total'], $b['sacas']);
        }
        return $buckets;
    }

    /**
     * Tipo derivado (a coluna `tipo` deixou de existir):
     *   - BARTER: contrato marcado como barter (flag tblcontrato.barter).
     *   - FIXO:   quantidade definida e já fixada por completo
     *   - FIXAR:  o resto (fixa aos poucos, ou volume em aberto)
     * Depende de `fixado` (withSum) resolvido em ContratoService::pesquisar.
     */
    protected function tipoDerivado(bool $emaberto): string
    {
        if ((bool) $this->barter) {
            return 'BARTER';
        }
        if (!$emaberto && (float) $this->fixado >= (float) $this->quantidade) {
            return 'FIXO';
        }
        return 'FIXAR';
    }
}
