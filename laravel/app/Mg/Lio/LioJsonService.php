<?php

namespace Mg\Lio;

use Illuminate\Support\Facades\Storage;

class LioJsonService
{
    public static function carregarOrder ($id)
    {
        $arquivo = "order/{$id}.json";
        $json = Storage::disk('lio')->get($arquivo);
        return json_decode($json);
    }

    public static function carregarPagamento ($id)
    {
        $arquivo = "pagamento/{$id}.json";
        $json = Storage::disk('lio')->get($arquivo);
        return json_decode($json);
    }

    public static function salvar ($jsonOrder, $jsonPagamento)
    {
        $obj = json_decode($jsonOrder);
        $id = $obj->id;

        $arquivo = "order/{$id}.json";
        Storage::disk('lio')->put($arquivo, $jsonOrder);

        $arquivo = "pagamento/{$id}.json";
        Storage::disk('lio')->put($arquivo, $jsonPagamento);

        return $id;
    }

}
