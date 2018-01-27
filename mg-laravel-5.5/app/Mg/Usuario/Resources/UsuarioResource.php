<?php

namespace App\Mg\Usuario\Resources;

use Illuminate\Http\Resources\Json\Resource;

class UsuarioResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
        // return [
        //     'codusuario' => $this->codusuario,
        //     'usuario' => $this->usuario,
        // ];

    }

    public function with($request)
    {
        return [
            'foot' => 'bar'
        ];
    }
}
