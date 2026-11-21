<?php

namespace Mg\Maquineta;

use Illuminate\Http\Resources\Json\JsonResource;
use Mg\Saurus\SaurusService;

class MaquinetaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        // o PDV Saurus (saurus_pdv) leva as chaves da Saurus: não sai na resposta
        unset($ret['filial'], $ret['pessoa'], $ret['saurus_pdv']);
        $ret['filial'] = $this->Filial->filial ?? null;
        $ret['adquirente'] = $this->Pessoa->fantasia ?? null;
        // SafraPay: tem aparelho pareado (tblsauruspinpad ativo no PDV Saurus dela)?
        $ret['pareada'] = $this->integracao === Maquineta::INTEGRACAO_SAURUS
            ? (bool) SaurusService::pinpadAtivo($this->SaurusPdv)
            : null;
        return $ret;
    }
}
