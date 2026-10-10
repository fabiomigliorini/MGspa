<?php

namespace Mg\Pdv;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PdvResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // Chave Extrangeira
        $ret['filial'] = @$this->Filial->filial;
        $ret['setor'] = $this->Setor?->setor ?? null;
        $ret['portador'] = $this->Portador?->portador ?? null;

        // Configuracao: o PDV usa offline, sem consultar o IndexedDB
        $ret['estoquelocal'] = $this->EstoqueLocal?->estoquelocal ?? null;
        $ret['naturezaoperacao'] = $this->NaturezaOperacao?->naturezaoperacao ?? null;
        $ret['codoperacao'] = $this->NaturezaOperacao?->codoperacao ?? null;
        $ret['venda'] = $this->NaturezaOperacao?->venda ?? null;
        $ret['maquineta'] = $this->Maquineta?->apelido ?? null;
        $ret['portadorpix'] = $this->PortadorPix?->portador ?? null;

        return $ret;
    }
}
