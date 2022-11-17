<?php

namespace Mg\PagarMe;

use Illuminate\Support\Facades\Storage;

class WebhookJsonService
{
    public static function salvar ($content)
    {
        $file = date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('pagar-me')->put($file, $content);
        return $file;
    }

}
