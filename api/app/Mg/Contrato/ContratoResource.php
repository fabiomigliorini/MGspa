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
            $ret['EmbarqueContratoS'] = $this->EmbarqueContratoS->map(function ($e) {
                return [
                    'codembarquecontrato' => (int) $e->codembarquecontrato,
                    'codembarque' => (int) $e->codembarque,
                    'codcontrato' => (int) $e->codcontrato,
                    'quantidade' => (float) $e->quantidade,
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
