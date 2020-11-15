<?php

namespace Mg\Lio;

use Illuminate\Support\Facades\Storage;

class LioJsonService
{
    public static function carregarOrder ($id)
    {
        $diretorio = static::diretorio($id);
        $arquivo = "order/{$diretorio}/{$id}.json";
        $json = Storage::disk('lio')->get($arquivo);
        return json_decode($json);
    }

    public static function carregarPagamento ($id)
    {
        $diretorio = static::diretorio($id);
        $arquivo = "pagamento/{$diretorio}/{$id}.json";
        $json = Storage::disk('lio')->get($arquivo);
        return json_decode($json);
    }

    public static function diretorio ($id)
    {
        return str_replace('-', '/', $id);
    }

    public static function salvar ($jsonOrder, $jsonPagamento)
    {
        $obj = json_decode($jsonOrder);
        $id = $obj->id;

        $diretorio = static::diretorio($id);

        $arquivo = "order/{$diretorio}/{$id}.json";
        Storage::disk('lio')->put($arquivo, $jsonOrder);

        $arquivo = "pagamento/{$diretorio}/{$id}.json";
        Storage::disk('lio')->put($arquivo, $jsonPagamento);

        return $id;
    }

    public static function salvarCallback ($content)
    {
        $file = 'callback/' . date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('lio')->put($file, $content);
    }

}
