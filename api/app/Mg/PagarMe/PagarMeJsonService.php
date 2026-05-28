<?php

namespace Mg\PagarMe;

use Illuminate\Support\Facades\Storage;

class PagarMeJsonService
{
    public static function salvar ($content)
    {
        $file = date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('pagar-me')->put($file, $content);
        return $file;
    }

    public static function carregar ($file)
    {
        $content = Storage::disk('pagar-me')->get($file);
        $content = json_decode($content);
        return $content;
    }
}
