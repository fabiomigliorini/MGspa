<?php

namespace Mg\Certidao;

use Illuminate\Http\Resources\Json\JsonResource;

class CertidaoEmissorResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
