<?php

namespace Mg\StoneConnect;

use Illuminate\Support\Facades\Storage;

class WebhookJsonService
{
    public static function salvarPosApplication ($content)
    {
        $file = 'pos-application/' . date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('stone')->put($file, $content);
        return $file;
    }

    public static function salvarPreTransactionStatus ($content)
    {
        $file = 'pre-transaction-status/' . date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('stone')->put($file, $content);
        return $file;
    }

    public static function salvarProcessedTransaction ($content)
    {
        $file = 'processed-transaction/' . date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('stone')->put($file, $content);
        return $file;
    }

    public static function salvarPrintNoteStatus ($content)
    {
        $file = 'print-note-status/' . date('Y/m/d/H-i-s') . '-' . uniqid() . '.json';
        Storage::disk('stone')->put($file, $content);
        return $file;
    }
}
