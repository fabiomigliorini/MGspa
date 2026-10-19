<?php

namespace Mg\Auditoria;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Ocorrencia\OcorrenciaService;

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
    // venda fechada reaberta pelo gerente e o que muda dentro dela (TASK-30)
    const TIPO_NEGOCIO_REABERTO = 15;
    const TIPO_PAGAMENTO_CANCELADO_REABERTURA = 16;
    const TIPO_PAGAMENTO_REATIVADO = 17;
    const TIPO_PARCELA_INATIVADA_REABERTURA = 18;
    const TIPO_PARCELA_REATIVADA = 19;
    // todo fechamento, inclusive o da reaberta (TASK-209)
    const TIPO_NEGOCIO_FECHADO = 20;
    // o pagamento foi para outro portador do mesmo tipo (TASK-236)
    const TIPO_PORTADOR_ALTERADO = 21;
    // PDV ou usuario da venda; vendedor, filial, local de estoque, natureza ou
    // pessoa da venda fechada (TASK-29)
    const TIPO_NEGOCIO_ALTERADO = 22;

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
        self::TIPO_NEGOCIO_REABERTO => 'Negócio reaberto',
        self::TIPO_PAGAMENTO_CANCELADO_REABERTURA => 'Pagamento cancelado na reabertura',
        self::TIPO_PAGAMENTO_REATIVADO => 'Pagamento reativado',
        self::TIPO_PARCELA_INATIVADA_REABERTURA => 'Parcela inativada na reabertura',
        self::TIPO_PARCELA_REATIVADA => 'Parcela reativada',
        self::TIPO_NEGOCIO_FECHADO => 'Negócio fechado',
        self::TIPO_PORTADOR_ALTERADO => 'Portador alterado',
        self::TIPO_NEGOCIO_ALTERADO => 'Negócio alterado',
    ];

    // o historico do registro traz tambem a auditoria destes filhos (TASK-209);
    // chave do filho e FK para o pai pela convencao: tblpagamento.codpagamento,
    // tblpagamento.codnegocio
    const FILHOS = [
        'tblnegocio' => [
            'tblnegocioprodutobarra',
            'tblpagamento',
            'tblnegocioparcela',
            'tblnegociovale',
        ],
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

    // Historico de um registro (TASK-209, MgHistorico): as auditorias dele e
    // dos filhos, e as ocorrencias que o citam. O filho apagado nao tem mais
    // linha: a auditoria dele entra pela ocorrencia que a amarrou.
    public static function historico(string $tabela, int $codigo): array
    {
        $where = ["(a.tabela = :tabela and a.codigo = {$codigo})"];
        $fk = 'cod' . substr($tabela, 3);
        foreach (static::FILHOS[$tabela] ?? [] as $filho) {
            $pk = 'cod' . substr($filho, 3);
            $where[] = "(a.tabela = '{$filho}' and a.codigo in (select {$pk} from {$filho} where {$fk} = {$codigo}))";
        }
        $codauditorias = array_column(DB::select(
            'select a.codauditoria from tblauditoria a where ' . implode(' or ', $where),
            ['tabela' => $tabela]
        ), 'codauditoria');

        $ocorrencias = OcorrenciaService::historico($tabela, $codigo, $codauditorias);
        foreach ($ocorrencias as $oc) {
            $codauditorias = array_merge($codauditorias, array_column($oc['auditorias'], 'codauditoria'));
        }

        $auditorias = [];
        if (!empty($codauditorias)) {
            $auditorias = DB::select('
                select a.codauditoria, a.tabela, a.codigo, a.tipo, a.antes, a.depois,
                    a.justificativa, a.criacao, u.usuario
                from tblauditoria a
                left join tblusuario u on (u.codusuario = a.codusuariocriacao)
                where a.codauditoria in (' . implode(',', array_map('intval', array_unique($codauditorias))) . ')
                order by a.criacao desc, a.codauditoria desc
            ');
        }
        return [
            'auditorias' => array_map(fn ($a) => static::linha($a), $auditorias),
            'ocorrencias' => $ocorrencias,
        ];
    }

    // a auditoria como o front le (sql com u.usuario de quem fez)
    public static function linha($a): array
    {
        return [
            'codauditoria' => (int) $a->codauditoria,
            'tabela' => $a->tabela,
            'codigo' => (int) $a->codigo,
            'tipo' => (int) $a->tipo,
            'tipodescricao' => static::TIPOS[$a->tipo] ?? $a->tipo,
            'antes' => $a->antes ? json_decode($a->antes, true) : null,
            'depois' => $a->depois ? json_decode($a->depois, true) : null,
            'justificativa' => $a->justificativa,
            'usuario' => $a->usuario,
            'criacao' => Carbon::parse($a->criacao)->toIso8601String(),
        ];
    }
}
