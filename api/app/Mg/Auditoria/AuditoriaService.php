<?php

namespace Mg\Auditoria;

/**
 * Auditoria (TASK-204): registra o que mudou num registro qualquer. Ninguem
 * confere; o livro de ocorrencias (TASK-205) aponta para ela.
 */
class AuditoriaService
{
    const TIPO_DATA_ALTERADA = 1;
    const TIPO_DATA_CANCELAMENTO_ALTERADA = 2;
    const TIPO_CORRIGIDO_CONFERENCIA = 3;
    const TIPO_REGISTRO_INDEVIDO = 4;
    const TIPO_INCLUIDO_CONFERENCIA = 5;

    const TIPOS = [
        self::TIPO_DATA_ALTERADA => 'Data alterada',
        self::TIPO_DATA_CANCELAMENTO_ALTERADA => 'Data do cancelamento alterada',
        self::TIPO_CORRIGIDO_CONFERENCIA => 'Corrigido na conferência',
        self::TIPO_REGISTRO_INDEVIDO => 'Registro indevido',
        self::TIPO_INCLUIDO_CONFERENCIA => 'Incluído na conferência',
    ];

    // `antes` nulo: o registro nasceu (fica o `depois` com o que foi
    // preenchido). Com os dois, so' os campos que mudaram.
    public static function registrar(
        string $tabela,
        int $codigo,
        int $tipo,
        ?array $antes,
        ?array $depois,
        ?string $justificativa = null
    ): Auditoria {
        if ($antes !== null && $depois !== null) {
            $mudou = array_filter(
                array_keys($antes + $depois),
                fn ($c) => !static::igual($antes[$c] ?? null, $depois[$c] ?? null)
            );
            $antes = array_intersect_key($antes, array_flip($mudou));
            $depois = array_intersect_key($depois, array_flip($mudou));
        } elseif ($depois !== null) {
            $depois = array_filter($depois, fn ($v) => $v !== null);
        }
        $justificativa = trim($justificativa ?? '');
        return Auditoria::create([
            'tabela' => $tabela,
            'codigo' => $codigo,
            'tipo' => $tipo,
            'antes' => $antes,
            'depois' => $depois,
            'justificativa' => $justificativa === '' ? null : mb_substr($justificativa, 0, 300),
        ]);
    }

    // numero compara como numero ("10.00" e 10); null so' e' igual a null
    private static function igual($a, $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a == (float) $b;
        }
        return (string) $a === (string) $b;
    }

    // a ultima auditoria de cada registro: [codigo => Auditoria]
    public static function ultimas(string $tabela, array $codigos, int $tipo): array
    {
        if (empty($codigos)) {
            return [];
        }
        return Auditoria::with('UsuarioCriacao:codusuario,usuario')
            ->where('tabela', $tabela)
            ->whereIn('codigo', array_values(array_unique($codigos)))
            ->where('tipo', $tipo)
            ->orderBy('codauditoria')
            ->get()
            ->keyBy('codigo')
            ->all();
    }
}
