<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class ColaboradorRubricaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);

        // Catálogo (tblrubrica) — null quando é rubrica avulsa "Outros"
        $ret['rubrica'] = $this->Rubrica ? new RubricaResource($this->Rubrica) : null;

        return $ret;
    }
}
