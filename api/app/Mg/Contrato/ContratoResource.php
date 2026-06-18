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
            $ret['pessoa_nf'],
            $ret['cultura'],
            $ret['safra'],
            $ret['filial'],
            $ret['portador'],
            $ret['corretora'],
            $ret['cooperativa'],
            $ret['natureza_operacao'],
            $ret['contrato_fixacao_s'],
            $ret['contrato_pagamento_s'],
            $ret['embarque_contrato_s'],
        );

        // auditoria (quem criou/alterou)
        $ret['usuariocriacao'] = $this->usuariocriacao;
        $ret['usuarioalteracao'] = $this->usuarioalteracao;

        // Reconciliacao fisica em KG (unidade de trabalho) com sacas derivadas.
        // O contrato negocia em sacas (quantidade) + R$/saca; o embarque grava
        // kg (rateio bruto-tara). Ponte: pesosaca da cultura (default 60).
        // carregadokg vem do withSum (ContratoService); demais sao derivados.
        $pesosaca = (float) ($this->Cultura->pesosaca ?? 60) ?: 60;
        $carregadokg = (float) $this->carregadokg; // soma kg dos embarques
        $contratadokg = (float) $this->quantidade * $pesosaca;
        $ret['semlimite'] = (bool) $this->semlimite;
        $ret['pesosaca'] = $pesosaca;
        $ret['carregadokg'] = $carregadokg;
        $ret['contratadokg'] = $contratadokg;
        $ret['carregadosc'] = $pesosaca > 0 ? round($carregadokg / $pesosaca, 2) : 0.0;
        // saldo so faz sentido em contrato com teto; sem limite -> null.
        $ret['saldokg'] = $this->semlimite ? null : max(0, $contratadokg - $carregadokg);

        // relações em PascalCase (whenLoaded — chaves ausentes somem do JSON)
        $ret['Pessoa'] = $this->whenLoaded('Pessoa');
        $ret['PessoaNf'] = $this->whenLoaded('PessoaNf');
        $ret['Cultura'] = $this->whenLoaded('Cultura');
        $ret['Safra'] = $this->whenLoaded('Safra');
        $ret['Filial'] = $this->whenLoaded('Filial');
        $ret['Portador'] = $this->whenLoaded('Portador');
        $ret['Corretora'] = $this->whenLoaded('Corretora');
        $ret['Cooperativa'] = $this->whenLoaded('Cooperativa');
        $ret['NaturezaOperacao'] = $this->whenLoaded('NaturezaOperacao');

        // Fixações: injeta o contrato em cada filha p/ o resource calcular o
        // preço líquido (deduções) com a cultura/isenção/funrural do contrato.
        if ($this->relationLoaded('ContratoFixacaoS')) {
            $this->ContratoFixacaoS->each(fn ($f) => $f->setRelation('Contrato', $this->resource));
            $ret['ContratoFixacaoS'] = ContratoFixacaoResource::collection($this->ContratoFixacaoS);
        }

        if ($this->relationLoaded('ContratoPagamentoS')) {
            $ret['ContratoPagamentoS'] = ContratoPagamentoResource::collection($this->ContratoPagamentoS);
        }

        if ($this->relationLoaded('EmbarqueContratoS')) {
            $ret['EmbarqueContratoS'] = $this->EmbarqueContratoS->map(function ($e) use ($pesosaca) {
                $kg = (float) $e->quantidade; // tblembarquecontrato.quantidade e KG
                return [
                    'codembarquecontrato' => (int) $e->codembarquecontrato,
                    'codembarque' => (int) $e->codembarque,
                    'codcontrato' => (int) $e->codcontrato,
                    'quantidadekg' => $kg,
                    'quantidadesc' => $pesosaca > 0 ? round($kg / $pesosaca, 2) : 0.0,
                    'numeronf' => $e->numeronf,
                    'chavenf' => $e->chavenf,
                    'valornf' => $e->valornf !== null ? (float) $e->valornf : null,
                    'Embarque' => $e->relationLoaded('Embarque') ? $e->Embarque : null,
                ];
            });
        }

        // Cálculo do líquido (motor fiscal) — só no detalhe (fixações carregadas),
        // pra não disparar N+1 na listagem.
        if ($this->relationLoaded('ContratoFixacaoS')) {
            $ret['calculo'] = ContratoCalculoService::calcularDoContrato($this->resource);
        }

        return $ret;
    }
}
