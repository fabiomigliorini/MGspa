<?php

namespace Mg\Imagem;

use Illuminate\Support\Facades\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use RuntimeException;

/**
 * Substituto do antigo `App\Libraries\SlimImageCropper\Slim` usado pelo
 * ImagemController/ImagemService. Mantém a mesma API pública (`getImages`,
 * `saveFile`) para não precisar alterar callers.
 *
 * Funcionamento: o frontend usa o widget Slim Image Cropper (JS) que envia
 * o resultado serializado como JSON no campo `slim` (ou `slim[]`). Cada item
 * tem o shape:
 *     {
 *       "input":  { "name": "...", "type": "...", "size": 123, "image": "data:image/jpeg;base64,..." },
 *       "output": { "name": "...", "type": "image/jpeg", "image": "data:image/jpeg;base64,...", "data": "data:..." }
 *     }
 *
 * Substituímos a lib (que não existia mais) por Intervention\Image apenas para
 * a parte de write/encode quando necessário. O parse do payload do widget é feito
 * em PHP puro.
 */
class SlimAdapter
{
    public static function getImages(?string $field = 'slim'): array
    {
        $raw = $_POST[$field] ?? Request::input($field);
        if (empty($raw)) {
            return [];
        }
        if (is_string($raw)) {
            $raw = [$raw];
        }

        $result = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $decoded = json_decode($item, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                $result[] = $decoded;
            } elseif (is_array($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Salva o conteúdo de uma imagem (data URL base64 ou raw base64) num arquivo.
     */
    public static function saveFile(string $dataUrl, string $filename, string $directory, bool $minified = false): bool
    {
        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException("Não foi possível criar diretório de imagens: {$directory}");
            }
        }

        // Strip "data:image/...;base64," prefix if present
        if (preg_match('/^data:image\/\w+;base64,/', $dataUrl)) {
            $dataUrl = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
        }
        $binary = base64_decode($dataUrl, true);
        if ($binary === false) {
            throw new RuntimeException('Imagem inválida (base64 decode falhou).');
        }

        $path = rtrim($directory, '/') . '/' . $filename;

        // Re-encode via Intervention para normalizar o formato (JPEG por padrão)
        // e aplicar minify opcional (qualidade 75).
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($binary);
            $quality = $minified ? 60 : 90;
            $encoded = $image->toJpeg($quality);
            return (bool) file_put_contents($path, $encoded->toString());
        } catch (\Throwable $e) {
            // Fallback: salva binário cru se Intervention falhar
            return (bool) file_put_contents($path, $binary);
        }
    }
}
