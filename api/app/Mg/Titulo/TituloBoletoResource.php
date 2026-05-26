<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class TituloBoletoResource extends Resource
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
        $ret['Portador'] = null;
        if (!empty($this->codportador)) {
            unset($ret['portador']);
            $ret['Portador'] = $this->Portador->only([
                'codportador',
                'portador',
                'codbanco',
                'agencia',
                'agenciadigito',
                'conta',
                'contadigito',
            ]);
        }
        return $ret;
    }
}
