<?php

namespace App\Mg\Usuario\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UsuarioResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        //dd($this->collection);
        return [
            'usuario' => $this->collection->usuario
        ];
    }
}
