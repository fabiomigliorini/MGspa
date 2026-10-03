<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Mg\Banco\Banco;
use Mg\Conferencia\ConferenciaAutorizador;
use OfxParser\Parser;
use RuntimeException;

class PortadorService
{
    const CAIXA = 100;
    const FOLHA = 202018;

    public static function listar(array $filtros)
    {
        $q = Portador::with(['Banco', 'Filial']);

        if (!empty($filtros['codportador'])) {
            $q->where('codportador', $filtros['codportador']);
        }

        if (!empty($filtros['portador'])) {
            $q->palavras('portador', $filtros['portador']);
        }

        if (!empty($filtros['tipo'])) {
            $q->where('tipo', $filtros['tipo']);
        }

        if (!empty($filtros['codbanco'])) {
            $q->where('codbanco', $filtros['codbanco']);
        }

        if (!empty($filtros['codfilial'])) {
            $q->where('codfilial', $filtros['codfilial']);
        }

        if (array_key_exists('emiteboleto', $filtros) && $filtros['emiteboleto'] !== null && $filtros['emiteboleto'] !== '') {
            $q->where('emiteboleto', filter_var($filtros['emiteboleto'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('inativo', $filtros) && $filtros['inativo'] !== null && $filtros['inativo'] !== '') {
            if ($filtros['inativo'] === true || $filtros['inativo'] === 'true' || $filtros['inativo'] === 1 || $filtros['inativo'] === '1') {
                $q->whereNotNull('inativo');
            } else {
                $q->whereNull('inativo');
            }
        }

        $q->orderBy('portador');

        return $q->paginate(25);
    }

    // painel /portador (doc-4): os portadores por filial com o saldo gravado
    // (so' da especie nesta fase, R3), a situacao do caixa (toda especie) e
    // as transferencias a confirmar. Gerente ve as filiais dele; Financeiro
    // e Admin, todas (R15). `bloqueio`: o caixa nao aceita transferencia agora
    public static function painel(?int $codfilial, bool $inativos): array
    {
        $filiais = ConferenciaAutorizador::filiais();
        $portadores = Portador::with('Filial:codfilial,filial')
            ->when(!$inativos, fn ($q) => $q->whereNull('inativo'))
            ->when($codfilial, fn ($q) => $q->where('codfilial', $codfilial))
            ->when($filiais !== null, fn ($q) => $q->whereIn('codfilial', $filiais ?: [0]))
            ->orderBy('codfilial')
            ->orderByRaw("position(tipo in 'EBACO')")
            ->orderBy('portador')
            ->get();
        $gavetas = DB::table('tblpdv')->whereNotNull('codportador')->distinct()->pluck('codportador')
            ->map(fn ($c) => (int) $c)->all();
        $caixas = $portadores->filter(fn ($p) => $p->ehCaixa())->pluck('codportador')->all();
        $sessoes = PortadorPeriodo::whereIn('codportador', $caixas ?: [0])
            ->whereRaw('codportadorperiodo in (
                select distinct on (codportador) codportadorperiodo
                from tblportadorperiodo
                order by codportador, inicio desc, codportadorperiodo desc
            )')
            ->with(['UsuarioAbertura:codusuario,usuario', 'UsuarioFechamento:codusuario,usuario'])
            ->get()
            ->keyBy('codportador');
        $pendentes = DB::select("
            select codportadororigem, codportadordestino, total
            from tblpagamento
            where estado = 'P'
            and codnegocio is null
            and codportadororigem is not null
            and codportadordestino is not null
        ");
        $somar = function ($coluna, $codportador) use ($pendentes) {
            $doPortador = array_filter($pendentes, fn ($p) => $p->$coluna == $codportador);
            return [
                'quantidade' => count($doPortador),
                'valor' => round(array_sum(array_map(fn ($p) => (float) $p->total, $doPortador)), 2),
            ];
        };
        return $portadores->map(function (Portador $p) use ($gavetas, $sessoes, $somar) {
            $gaveta = $p->ehCaixa() && in_array($p->codportador, $gavetas);
            $sessao = $p->ehCaixa() ? $sessoes->get($p->codportador) : null;
            return [
                'codportador' => $p->codportador,
                'portador' => $p->portador,
                'tipo' => $p->tipo,
                'codfilial' => $p->codfilial,
                'filial' => optional($p->Filial)->filial,
                'inativo' => $p->inativo,
                'ehGaveta' => $gaveta,
                'ehCaixa' => $p->ehCaixa(),
                'saldo' => $p->tipo == Portador::TIPO_ESPECIE ? (float) $p->saldo : null,
                'sessao' => $sessao ? [
                    'codportadorperiodo' => $sessao->codportadorperiodo,
                    'aberta' => $sessao->aberto(),
                    'inicio' => $sessao->inicio,
                    'fim' => $sessao->fim,
                    'usuarioabertura' => optional($sessao->UsuarioAbertura)->usuario,
                    'usuariofechamento' => optional($sessao->UsuarioFechamento)->usuario,
                ] : null,
                'bloqueio' => $p->ehCaixa() && !($sessao && $sessao->aberto()) ? 'Caixa não aberto' : null,
                'chegando' => $somar('codportadordestino', $p->codportador),
                'saindo' => $somar('codportadororigem', $p->codportador),
            ];
        })->values()->all();
    }

    public static function criar(array $dados): Portador
    {
        $portador = Portador::create($dados);
        $portador->load(['Banco', 'Filial']);
        return $portador;
    }

    public static function atualizar(Portador $portador, array $dados): Portador
    {
        $portador->fill($dados);
        $portador->save();
        $portador->refresh();
        $portador->load(['Banco', 'Filial']);
        return $portador;
    }

    public static function inativar(Portador $portador): Portador
    {
        $portador->inativo = Carbon::now();
        $portador->save();
        $portador->refresh();
        $portador->load(['Banco', 'Filial']);
        return $portador;
    }

    public static function ativar(Portador $portador): Portador
    {
        $portador->inativo = null;
        $portador->save();
        $portador->refresh();
        $portador->load(['Banco', 'Filial']);
        return $portador;
    }

    public static function excluir(Portador $portador): void
    {
        try {
            $portador->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23503') {
                throw new RuntimeException('Portador em uso, não pode ser excluído. Inative ao invés de excluir.');
            }
            throw $e;
        }
    }

    public static function buscarPortadorOfx($routingNumber, $accountNumber)
    {

        // Localiza o Banco
        $banco = Banco::where([
            'numerobanco' => $routingNumber
        ])->firstOrFail();

        // Banco do Brasil - PJ
        // Ex: 13387-6
        if ($pos = strpos($accountNumber, '-')) {
            $conta = substr($accountNumber, 0, $pos);
            $contadigito = substr($accountNumber, $pos + 1);
            if ($portador = Portador::where([
                'codbanco' => $banco->codbanco,
                'conta' => $conta,
                'contadigito' => $contadigito,
            ])->first()) {
                return $portador;
            };
        }

        // Bradesco - PF
        // Ex: 953/195
        if ($pos = strpos($accountNumber, '/')) {
            $agencia = substr($accountNumber, 0, $pos);
            $conta = substr($accountNumber, $pos + 1);
            if ($portador = Portador::where([
                'codbanco' => $banco->codbanco,
                'agencia' => $agencia,
                'conta' => $conta,
            ])->first()) {
                return $portador;
            };
        }

        // considera quem o $accountNumber é somente a conta
        $conta = preg_replace("/[^0-9]/", "", $accountNumber);
        if ($portador = Portador::where([
            'codbanco' => $banco->codbanco,
            'conta' => $conta,
        ])->first()) {
            return $portador;
        };

        throw new \Exception("Não localizado nenhum portador para a conta '{$accountNumber}'!", 1);
    }

    public static function importarOfx ($ofxString)
    {
        // Carrega o arquivo
        $ofxParser = new Parser();
        $ofx = $ofxParser->loadFromString($ofxString);

        // Localiza o Portador
        $bankAccount = reset($ofx->bankAccounts);
        $portador = static::buscarPortadorOfx($bankAccount->routingNumber, $bankAccount->accountNumber);

        // Get the statement transactions for the account
        $transactions = $bankAccount->statement->transactions;
        $resultado = self::salvaTransacoes($transactions, $portador);
        self::salvaSaldos($transactions, $bankAccount, $portador);

        return $resultado;
    }

    private static function salvaTransacoes($transactions, $portador)
    {
        $registros = 0;
        $falhas = 0;
        foreach ($transactions as $transaction) {
            // determina tipo do movimento
            $tipo = ExtratoBancarioTipoMovimento::firstOrNew([
                'trntype' => $transaction->type,
            ]);
            if (empty($tipo->codextratobancariotipomovimento)) {
                $tipo->tipo = $transaction->type;
                $tipo->sigla = substr($transaction->type, 0, 3);
                $tipo->save();
            }

            // verifica se o registro já existe
            $mov = ExtratoBancario::firstOrNew([
                'codportador' => $portador->codportador,
                'fitid' => $transaction->uniqueId,
            ]);
            $mov->codextratobancariotipomovimento = $tipo->codextratobancariotipomovimento;
            $mov->transacao = $transaction->date;
            $mov->valor =  $transaction->amount;
            $mov->numero =  $transaction->checkNumber;
            $mov->observacoes =  $transaction->memo;
            if (!$mov->save()) {
                $falhas++;
            };
            $registros++;
        }

        return [
            'codportador' => $portador->codportador,
            'portador' => $portador->portador,
            'registros' => $registros,
            'falhas' => $falhas,
        ];
    }

    private static function salvaSaldos($transactions, $bankAccount, $portador)
    {
        usort($transactions, function($a, $b) {
            return $b->date <=> $a->date;
        });

        // Saldo final e data
        $saldoFinal = (float)$bankAccount->balance;
        $totalPorDia = array();

        foreach ($transactions as $transaction) {
            $dateKey = $transaction->date->format("Y-m-d");

            if(!isset($totalPorDia[$dateKey])){
                $totalPorDia[$dateKey] = 0;
            }
            $totalPorDia[$dateKey] += $transaction->amount;
        }

        //$saldos = array();
        $proximoSaldo = $saldoFinal;
        foreach ($totalPorDia as $dia => $valor) {
            //$saldos[$dia] = $proximoSaldo;
            $saldo = PortadorSaldo::firstOrNew([
                'codportador' => $portador->codportador,
                'dia' => $dia,
            ]);
            $saldo->saldobancario = $proximoSaldo;
            $saldo->save();

            $proximoSaldo -= $valor;
        }

        //dd($saldos);
    }

    public static function listaMovimentacoes($codportador, $dataInicial, $dataFinal){
        $extratosPage = ExtratoBancario::where('codportador', '=', $codportador)
            ->whereBetween('transacao', [$dataInicial, $dataFinal])
            ->orderBy('transacao', 'asc')->get();

        return $extratosPage;
    }

    public static function listaSaldosPortador($codportador, $dataInicial, $dataFinal)
    {
        $saldoAnterior = PortadorSaldo::select(['codportadorsaldo', 'dia', 'saldobancario'])->where('codportador', $codportador)
            ->where('dia', '<', $dataInicial)
            ->orderByDesc('dia')
            ->first();

        $saldos = PortadorSaldo::select(['codportadorsaldo', 'dia', 'saldobancario'])->where('codportador', '=', $codportador)
                    ->whereBetween('dia', [$dataInicial, $dataFinal])
                    ->orderBy('dia', 'asc')->get();

        return [
            'saldos' => $saldos,
            'saldoAnterior' => $saldoAnterior
        ];
    }

    public static function getIntervaloTotalExtratos(){
        //TODO Where provisório porque tem uns valores errados na tabela. Ex ano que começa com 00
        $sql = '
            SELECT
                MIN(transacao)::date AS primeira_data,
                MAX(transacao)::date AS ultima_data
            FROM tblextratobancario
            WHERE EXTRACT(YEAR FROM transacao) >= 1000
        ';

        $data = DB::select($sql);
        return $data[0];
    }
}
