<?php

namespace Mg\Cheque;

use Carbon\Carbon;
use Mg\MgService;
use Mg\Portador\Portador;
use RuntimeException;

class ChequeRepasseService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = ChequeRepasse::query()
            ->with('Portador')
            ->withCount('ChequeRepasseChequeS');

        if (!empty($filter['codchequerepasse'])) {
            $qry->where('codchequerepasse', $filter['codchequerepasse']);
        }

        if (!empty($filter['codportador'])) {
            $qry->where('codportador', $filter['codportador']);
        }

        if (!empty($filter['data_de'])) {
            $qry->where('data', '>=', $filter['data_de']);
        }
        if (!empty($filter['data_ate'])) {
            $qry->where('data', '<=', $filter['data_ate']);
        }

        if (array_key_exists('inativo', $filter ?? []) && $filter['inativo'] !== null && $filter['inativo'] !== '') {
            if (in_array($filter['inativo'], [true, 'true', 1, '1'], true)) {
                $qry->whereNotNull('inativo');
            } else {
                $qry->whereNull('inativo');
            }
        }

        if (empty($sort)) {
            $qry->orderBy('criacao', 'desc');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function carregarComCheques(int $codchequerepasse): ChequeRepasse
    {
        return ChequeRepasse::with([
            'Portador',
            'ChequeRepasseChequeS.Cheque.Banco',
            'ChequeRepasseChequeS.Cheque.Pessoa',
            'ChequeRepasseChequeS.Cheque.ChequeEmitenteS',
        ])->findOrFail($codchequerepasse);
    }

    /**
     * Cheques disponíveis para repasse (status "À Repassar"), filtrados por vencimento.
     */
    public static function chequesParaRepassar(array $filtros)
    {
        $qry = Cheque::query()
            ->with(['Banco', 'Pessoa', 'ChequeEmitenteS'])
            ->where('indstatus', ChequeService::INDSTATUS_AREPASSAR);

        if (!empty($filtros['vencimento_de'])) {
            $qry->where('vencimento', '>=', $filtros['vencimento_de']);
        }
        if (!empty($filtros['vencimento_ate'])) {
            $qry->where('vencimento', '<=', $filtros['vencimento_ate']);
        }
        if (!empty($filtros['codpessoa'])) {
            $qry->where('codpessoa', $filtros['codpessoa']);
        }

        return $qry->orderBy('vencimento', 'asc')->orderBy('valor', 'asc')->get();
    }

    /**
     * Cria o repasse, vincula os cheques selecionados e marca-os como "Repassado".
     */
    public static function criar(array $dados): ChequeRepasse
    {
        $codcheques = $dados['codcheques'] ?? [];
        if (empty($codcheques)) {
            throw new RuntimeException('Selecione ao menos um cheque para repassar.');
        }

        $portador = Portador::findOrFail($dados['codportador']);
        $destino = mb_substr($portador->portador, 0, 50);

        $repasse = new ChequeRepasse();
        $repasse->codportador = $portador->codportador;
        $repasse->data = $dados['data'];
        $repasse->observacoes = $dados['observacoes'] ?? null;
        $repasse->save();

        foreach ($codcheques as $codcheque) {
            $cheque = Cheque::findOrFail($codcheque);
            if ($cheque->indstatus != ChequeService::INDSTATUS_AREPASSAR) {
                throw new RuntimeException(
                    "O cheque #{$cheque->codcheque} não está mais 'À Repassar' e não pode ser repassado."
                );
            }

            $pivot = new ChequeRepasseCheque();
            $pivot->codchequerepasse = $repasse->codchequerepasse;
            $pivot->codcheque = $cheque->codcheque;
            $pivot->compensacao = $dados['data'];
            $pivot->save();

            $cheque->indstatus = ChequeService::INDSTATUS_REPASSADO;
            $cheque->repasse = $dados['data'];
            $cheque->destino = $destino;
            $cheque->save();
        }

        return self::carregarComCheques($repasse->codchequerepasse);
    }

    /**
     * Inativa o repasse e devolve ao status "À Repassar" os cheques que ainda
     * estavam apenas "Repassado" (não avançaram para devolução/cobrança/liquidação).
     */
    public static function inativarRepasse(ChequeRepasse $repasse): ChequeRepasse
    {
        $repasse->inativo = Carbon::now();
        $repasse->save();

        foreach ($repasse->ChequeRepasseChequeS as $pivot) {
            $cheque = $pivot->Cheque;
            if ($cheque && $cheque->indstatus == ChequeService::INDSTATUS_REPASSADO) {
                $cheque->indstatus = ChequeService::INDSTATUS_AREPASSAR;
                $cheque->repasse = null;
                $cheque->destino = null;
                $cheque->save();
            }
        }

        return self::carregarComCheques($repasse->codchequerepasse);
    }

    /**
     * Marca um cheque do repasse como devolvido: cria a devolução (vinculada ao
     * pivot repasse-cheque) e seta o cheque como "Devolvido" (status 3).
     */
    public static function devolverCheque(ChequeRepasseCheque $pivot, array $dados): ChequeRepasseCheque
    {
        $cheque = $pivot->Cheque;
        if ($cheque->indstatus != ChequeService::INDSTATUS_REPASSADO) {
            throw new RuntimeException(
                "O cheque #{$cheque->codcheque} não está 'Repassado' e não pode ser devolvido."
            );
        }

        $motivo = ChequeMotivoDevolucao::findOrFail($dados['codchequemotivodevolucao']);

        $dev = new ChequeDevolucao();
        $dev->codchequerepassecheque = $pivot->codchequerepassecheque;
        $dev->codchequemotivodevolucao = $motivo->codchequemotivodevolucao;
        $dev->data = $dados['data'];
        $dev->observacoes = $dados['observacoes'] ?? null;
        $dev->save();

        $cheque->indstatus = ChequeService::INDSTATUS_DEVOLVIDO;
        $cheque->devolucao = $dados['data'];
        $cheque->motivodevolucao = mb_substr(
            $motivo->numero . ' - ' . $motivo->chequemotivodevolucao,
            0,
            50
        );
        $cheque->save();

        return $pivot->fresh(['Cheque']);
    }

    /**
     * Reativa o repasse e remarca como "Repassado" os cheques que voltaram a "À Repassar".
     */
    public static function reativarRepasse(ChequeRepasse $repasse): ChequeRepasse
    {
        $destino = mb_substr($repasse->Portador->portador ?? '', 0, 50);

        $repasse->inativo = null;
        $repasse->save();

        foreach ($repasse->ChequeRepasseChequeS as $pivot) {
            $cheque = $pivot->Cheque;
            if ($cheque && $cheque->indstatus == ChequeService::INDSTATUS_AREPASSAR) {
                $cheque->indstatus = ChequeService::INDSTATUS_REPASSADO;
                $cheque->repasse = $repasse->data;
                $cheque->destino = $destino;
                $cheque->save();
            }
        }

        return self::carregarComCheques($repasse->codchequerepasse);
    }
}
