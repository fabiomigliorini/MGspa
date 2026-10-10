<?php

namespace Mg\Auditoria;

/**
 * Auditoria (TASK-204): registra o que mudou num registro qualquer. Ninguem
 * confere aqui: o livro de ocorrencias (TASK-205) amarra as auditorias que o
 * gerente precisa ver (tblocorrenciaauditoria, N:N).
 */
class AuditoriaService
{
    const TIPO_DATA_ALTERADA = 1;
    const TIPO_DATA_CANCELAMENTO_ALTERADA = 2;
    const TIPO_CORRIGIDO_CONFERENCIA = 3;
    const TIPO_REGISTRO_INDEVIDO = 4;
    const TIPO_INCLUIDO_CONFERENCIA = 5;

    // PDV monitorado (TASK-205)
    const TIPO_ITEM_EXCLUIDO = 6;
    const TIPO_QUANTIDADE_ALTERADA = 7;
    const TIPO_PRECO_CADASTRO = 8;
    const TIPO_VALE_EXCLUIDO = 9;
    const TIPO_PAGAMENTO_APAGADO = 10;
    const TIPO_PARCELA_APAGADA = 11;
    const TIPO_NEGOCIO_CANCELADO = 12;
    const TIPO_ESTORNADO = 13;
    // o pagamento integrado mudou de venda (venda cancelada: fica orfao; o
    // orfao amarrado numa venda): o codnegocio antes e depois (TASK-188)
    const TIPO_AMARRACAO_VENDA = 14;

    const TIPOS = [
        self::TIPO_DATA_ALTERADA => 'Data alterada',
        self::TIPO_DATA_CANCELAMENTO_ALTERADA => 'Data do cancelamento alterada',
        self::TIPO_CORRIGIDO_CONFERENCIA => 'Corrigido na conferência',
        self::TIPO_REGISTRO_INDEVIDO => 'Registro indevido',
        self::TIPO_INCLUIDO_CONFERENCIA => 'Incluído na conferência',
        self::TIPO_ITEM_EXCLUIDO => 'Item excluído',
        self::TIPO_QUANTIDADE_ALTERADA => 'Quantidade alterada',
        self::TIPO_PRECO_CADASTRO => 'Preço diferente do cadastro',
        self::TIPO_VALE_EXCLUIDO => 'Vale compras excluído',
        self::TIPO_PAGAMENTO_APAGADO => 'Pagamento apagado',
        self::TIPO_PARCELA_APAGADA => 'Parcela apagada',
        self::TIPO_NEGOCIO_CANCELADO => 'Negócio cancelado',
        self::TIPO_ESTORNADO => 'Estornado',
        self::TIPO_AMARRACAO_VENDA => 'Amarração com a venda alterada',
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
