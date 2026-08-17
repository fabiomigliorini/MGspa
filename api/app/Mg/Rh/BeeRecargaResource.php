<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class BeeRecargaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        $filial = $this->getRelationValue('Filial');
        $titulo = $this->getRelationValue('Titulo');

        // Um item por VÍNCULO — o card mostra exatamente o que foi gravado
        // (a planilha é que agrupa por CPF). Vem pré-carregado em lote pelo
        // index(); no uso single-resource cai no fallback de uma consulta.
        $ret['colaboradores'] = $this->resource->colaboradores
            ?? BeeRecargaService::linhasDoLote($this->codbeerecarga);

        // `codfilial` já vem de parent::toArray() — é a tab a que o lote pertence.
        // Nome da filial e da empresa são só rótulo do cabeçalho do card.
        $ret['filial'] = $filial ? $filial->filial : null;
        $ret['codempresa'] = $filial ? (int) $filial->codempresa : null;
        $ret['empresa'] = $filial && $filial->getRelationValue('Empresa')
            ? $filial->getRelationValue('Empresa')->empresa
            : null;

        $ret['titulo'] = $titulo ? $titulo->numero : null;
        $ret['titulo_estornado'] = $titulo ? $titulo->estornado : null;

        // De onde o dinheiro sai. Mora no título (tblbeerecarga não guarda
        // codportador de propósito — seria a mesma informação em dois lugares).
        $portador = $titulo ? $titulo->getRelationValue('Portador') : null;
        $ret['portador'] = $portador ? $portador->portador : null;

        return $ret;
    }
}
