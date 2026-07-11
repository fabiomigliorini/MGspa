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

        if ($this->relationLoaded('ContratoPagamentoS')) {
            $ret['ContratoPagamentoS'] = ContratoPagamentoResource::collection($this->ContratoPagamentoS);
        }

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
        }

        return $ret;
    }

    /**
     * Tipo derivado (a coluna `tipo` deixou de existir):
     *   - BARTER: contrato marcado como barter (flag) OU com pagamento
     *             forma=BARTER (settlement em insumos). O flag permite declarar
     *             barter sem exigir parcela; `bartercount` fica como fallback
     *             p/ contratos legados que só têm a parcela barter.
     *   - FIXO:   quantidade definida e já fixada por completo
     *   - FIXAR:  o resto (fixa aos poucos, ou volume em aberto)
     * Depende de `bartercount` (withCount) e `fixado` (withSum) — ambos
     * resolvidos em ContratoService::pesquisar.
     */
    protected function tipoDerivado(bool $emaberto): string
    {
        if ((bool) $this->barter || (int) $this->bartercount > 0) {
            return 'BARTER';
        }
        if (!$emaberto && (float) $this->fixado >= (float) $this->quantidade) {
            return 'FIXO';
        }
        return 'FIXAR';
    }
}
