<?php

namespace Mg\Cheque;

use Mg\Banco\Banco;
use Mg\Cheque\Cmc7\Cmc7;
use Mg\MgService;
use Mg\Pessoa\Pessoa;
use RuntimeException;

class ChequeService extends MgService
{
    public const INDSTATUS_AREPASSAR = 1;
    public const INDSTATUS_REPASSADO = 2;
    public const INDSTATUS_DEVOLVIDO = 3;
    public const INDSTATUS_EMCOBRANCA = 4;
    public const INDSTATUS_LIQUIDADO = 5;

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Cheque::query()->with(['Banco', 'Pessoa', 'ChequeEmitenteS']);

        if (!empty($filter['codcheque'])) {
            $qry->where('codcheque', $filter['codcheque']);
        }

        if (!empty($filter['codbanco'])) {
            $qry->where('codbanco', $filter['codbanco']);
        }

        if (!empty($filter['agencia'])) {
            $qry->where('agencia', $filter['agencia']);
        }

        if (!empty($filter['numero'])) {
            $qry->where('numero', 'ilike', "%{$filter['numero']}%");
        }

        if (!empty($filter['codpessoa'])) {
            $qry->where('codpessoa', $filter['codpessoa']);
        }

        if (!empty($filter['emitente'])) {
            $palavras = preg_split('/\s+/', trim($filter['emitente']));
            foreach ($palavras as $palavra) {
                $qry->where(function ($q) use ($palavra) {
                    $q->where('emitente', 'ilike', "%{$palavra}%")
                        ->orWhereHas('ChequeEmitenteS', function ($q2) use ($palavra) {
                            $q2->where('emitente', 'ilike', "%{$palavra}%");
                        });
                });
            }
        }

        if (!empty($filter['valor_de'])) {
            $qry->where('valor', '>=', $filter['valor_de']);
        }
        if (!empty($filter['valor_ate'])) {
            $qry->where('valor', '<=', $filter['valor_ate']);
        }

        if (!empty($filter['indstatus'])) {
            $qry->where('indstatus', $filter['indstatus']);
        }

        if (!empty($filter['vencimento_de'])) {
            $qry->where('vencimento', '>=', $filter['vencimento_de']);
        }
        if (!empty($filter['vencimento_ate'])) {
            $qry->where('vencimento', '<=', $filter['vencimento_ate']);
        }

        if (empty($sort)) {
            $qry->orderBy('vencimento', 'desc')
                ->orderBy('valor', 'asc')
                ->orderBy('criacao', 'desc');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /**
     * Preenche codbanco/agencia/contacorrente/numero a partir do CMC7.
     */
    public static function parseCmc7(Cheque $cheque): void
    {
        $cmc7n = new Cmc7($cheque->cmc7);

        $banco = Banco::where('numerobanco', $cmc7n->banco())->first();
        if (empty($banco)) {
            throw new RuntimeException("Banco número {$cmc7n->banco()} não cadastrado.");
        }

        $cheque->codbanco = $banco->codbanco;
        $cheque->agencia = $cmc7n->agencia();
        $cheque->contacorrente = $cmc7n->contacorrente();
        $cheque->numero = $cmc7n->numero();
    }

    public static function criar(array $dados): Cheque
    {
        $cheque = new Cheque();
        $cheque->fill($dados);
        self::parseCmc7($cheque);
        $cheque->save();

        self::sincronizarEmitentes($cheque, $dados['emitentes'] ?? []);

        return $cheque->load(['Banco', 'Pessoa', 'ChequeEmitenteS']);
    }

    public static function atualizar(Cheque $cheque, array $dados): Cheque
    {
        $cheque->fill($dados);
        self::parseCmc7($cheque);
        $cheque->save();

        self::sincronizarEmitentes($cheque, $dados['emitentes'] ?? []);

        return $cheque->load(['Banco', 'Pessoa', 'ChequeEmitenteS']);
    }

    /**
     * Cria/atualiza/remove os emitentes filhos para refletir a lista enviada.
     */
    public static function sincronizarEmitentes(Cheque $cheque, array $emitentes): void
    {
        $mantidos = [];
        foreach ($emitentes as $dados) {
            if (empty($dados['cnpj']) && empty($dados['emitente'])) {
                continue;
            }
            if (!empty($dados['codchequeemitente'])) {
                $emit = ChequeEmitente::findOrFail($dados['codchequeemitente']);
            } else {
                $emit = new ChequeEmitente();
            }
            $emit->codcheque = $cheque->codcheque;
            $emit->cnpj = preg_replace('/[^0-9]/', '', $dados['cnpj'] ?? '');
            $emit->emitente = $dados['emitente'] ?? null;
            $emit->save();
            $mantidos[] = $emit->codchequeemitente;
        }

        ChequeEmitente::where('codcheque', $cheque->codcheque)
            ->whereNotIn('codchequeemitente', $mantidos ?: [0])
            ->delete();
    }

    /**
     * Valida o CMC7, devolve dados do banco e o último cheque do mesmo emitente.
     */
    public static function consultaCmc7(string $cmc7): array
    {
        $existente = Cheque::where('cmc7', $cmc7)->first();
        if (!empty($existente)) {
            return [
                'valido' => false,
                'error' => 'Já existe um cadastro com esse CMC7. #' . $existente->codcheque,
            ];
        }

        $cmc7n = new Cmc7($cmc7);

        $ultimo = [
            'codpessoa' => null,
            'emitentes' => [],
        ];

        $retorno = Cheque::where('codbanco', function ($q) use ($cmc7n) {
            $q->select('codbanco')->from('tblbanco')->where('numerobanco', $cmc7n->banco())->limit(1);
        })
            ->where('agencia', $cmc7n->agencia())
            ->where('contacorrente', $cmc7n->contacorrente())
            ->orderBy('criacao', 'desc')
            ->first();

        if (!empty($retorno)) {
            $ultimo['codpessoa'] = $retorno->codpessoa;
            foreach ($retorno->ChequeEmitenteS as $emit) {
                $ultimo['emitentes'][] = [
                    'cnpj' => $emit->cnpj,
                    'emitente' => $emit->emitente,
                ];
                if (empty($ultimo['codpessoa'])) {
                    if ($pessoa = Pessoa::where('cnpj', $emit->cnpj)->first()) {
                        $ultimo['codpessoa'] = $pessoa->codpessoa;
                    }
                }
            }
        }

        $banco = Banco::where('numerobanco', $cmc7n->banco())->first();

        return [
            'valido' => $cmc7n->valido(),
            'error' => $cmc7n->valido() ? null : 'CMC7 Inválido',
            'banco' => $banco ? $banco->banco : (string) $cmc7n->banco(),
            'agencia' => $cmc7n->agencia(),
            'contacorrente' => $cmc7n->contacorrente(),
            'numero' => $cmc7n->numero(),
            'ultimo' => $ultimo,
        ];
    }

    public static function consultaEmitente(string $cnpj): array
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        $pessoa = Pessoa::where('cnpj', $cnpj)->first();
        return [
            'codpessoa' => $pessoa ? $pessoa->codpessoa : null,
            'pessoa' => $pessoa ? $pessoa->pessoa : null,
        ];
    }
}
