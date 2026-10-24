<?php

namespace Mg;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Coluna array do Postgres (varchar[], text[]) <-> lista do PHP. Lista vazia grava nula.
 */
class PgArray implements CastsAttributes
{
    // '{6599,"Maria da Silva","O\"Brien"}': o elemento vem entre aspas quando tem espaco,
    // virgula, aspas ou barra, e as aspas e a barra dentro dele vem escapadas com barra
    public function get($model, string $key, $value, array $attributes): array
    {
        if ($value === null) {
            return [];
        }
        preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"|([^,{}]+)/', $value, $elementos, PREG_SET_ORDER);
        return array_map(
            fn ($e) => isset($e[2]) ? $e[2] : preg_replace('/\\\\(.)/', '$1', $e[1]),
            $elementos
        );
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (empty($value)) {
            return null;
        }
        return '{' . implode(',', array_map(
            fn ($e) => '"' . addcslashes((string) $e, '"\\') . '"',
            $value
        )) . '}';
    }
}
