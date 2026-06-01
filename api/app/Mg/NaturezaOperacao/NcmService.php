<?php

namespace Mg\NaturezaOperacao;

use Mg\MgService;

class NcmService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Ncm::query();

        if (!empty($filter['codncm'])) {
            $qry->where('codncm', $filter['codncm']);
        }

        if (array_key_exists('codncmpai', $filter ?? []) && $filter['codncmpai'] !== null && $filter['codncmpai'] !== '') {
            $qry->where('codncmpai', $filter['codncmpai']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['ncm'])) {
            $qry->where('ncm', 'ilike', preg_replace('/\D/', '', $filter['ncm']) . '%');
        }

        if (!empty($filter['descricao'])) {
            $qry->palavras('descricao', $filter['descricao']);
        }

        if (empty($sort)) {
            $qry->orderBy('ncm');
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $busca = trim($params['busca'] ?? $params['ncm'] ?? $params['descricao'] ?? '');
        $qry = Ncm::query();

        if ($busca !== '') {
            $digitos = preg_replace('/\D/', '', $busca);
            if ($digitos !== '' && $digitos === $busca) {
                $qry->where('ncm', 'ilike', $digitos . '%');
            } else {
                $qry->palavras('descricao', $busca);
            }
        }

        // Só folhas servem como NCM de produto (nós com filhos são agrupadores).
        $qry->whereNotIn('codncm', function ($sub) {
            $sub->select('codncmpai')->from('tblncm')->whereNotNull('codncmpai');
        });

        $qry->ativo()->orderBy('ncm')->take(30);

        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => self::formataNcm($item->ncm) . ' — ' . $item->descricao,
                'value' => $item->codncm,
                'id' => $item->codncm,
                'ncm' => $item->ncm,
            ];
        }
        return $ret;
    }

    /**
     * Filhos de um nó da árvore NCM. $id null = raízes (codncmpai null).
     * Cada nó vem com `lazy` true se possui filhos (é agrupador).
     */
    public static function arvoreFilhos(?int $id = null): array
    {
        $qry = Ncm::query();
        if ($id === null) {
            $qry->whereNull('codncmpai');
        } else {
            $qry->where('codncmpai', $id);
        }
        $itens = $qry->orderBy('ncm')->get();

        $ids = $itens->pluck('codncm')->all();
        $paisComFilhos = [];
        if (!empty($ids)) {
            $paisComFilhos = array_flip(
                Ncm::whereIn('codncmpai', $ids)->distinct()->pluck('codncmpai')->all(),
            );
        }

        $ret = [];
        foreach ($itens as $n) {
            $ret[] = [
                'key' => "ncm-{$n->codncm}",
                'codigo' => $n->codncm,
                'ncm' => $n->ncm,
                'descricao' => $n->descricao,
                'inativo' => $n->inativo,
                'lazy' => isset($paisComFilhos[$n->codncm]),
            ];
        }
        return $ret;
    }

    public static function formataNcm(?string $ncm): string
    {
        $d = preg_replace('/\D/', '', (string) $ncm);
        if (strlen($d) === 8) {
            return substr($d, 0, 4) . '.' . substr($d, 4, 2) . '.' . substr($d, 6, 2);
        }
        return (string) $ncm;
    }
}
