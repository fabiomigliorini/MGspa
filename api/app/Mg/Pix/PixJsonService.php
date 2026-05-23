<?php

namespace Mg\Pix;

use Illuminate\Support\Facades\Storage;

class PixJsonService
{
    public static function salvar ($content)
    {
        $file = date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('pix')->put($file, $content);
        return $file;
    }

    public static function carregar ($file)
    {
        $content = Storage::disk('pix')->get($file);
        $content = json_decode($content);
        return $content;
    }
}
