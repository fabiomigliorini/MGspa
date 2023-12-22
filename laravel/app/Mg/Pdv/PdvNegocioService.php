<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;

// use DB;

class PdvNegocioService
{

    public static function negocio($data)
    {
        $negocio = Negocio::firstOrNew(['uuid' => $data['id']]);
        if ($negocio->codnegociostatus == 2) {
            throw new \Exception("Tentando atualizar um negocio Fechado {$negocio->codnegocio}!", 1);
        }
        if ($negocio->codnegociostatus == 3) {
            throw new \Exception("Tentando atualizar um negocio Cancelado {$negocio->codnegocio}!", 1);
        }

        $negocio->fill($data);
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->entrega = false;
        $negocio->save();

        foreach ($data['itens'] as $item) {
            $npb = NegocioProdutoBarra::firstOrNew(['uuid' => $item['id']]);
            if (!empty($npb->codnegocio) && $npb->codnegocio != $negocio->codnegocio) {
                throw new \Exception("Tentando atualizar um item de outro negocio {$npb->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $npb->fill($item);
            $npb->codnegocio = $negocio->codnegocio;
            $npb->save();
        }
        return $negocio;
    }
}
