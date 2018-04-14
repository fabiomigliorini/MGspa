<?php

namespace Mg\Pessoa;

use Mg\MgRepository;

class PessoaRepository extends MgRepository
{
    /**
     * Busca Autocomplete Quasar
     */
    public static function autocomplete ($params)
    {
        $qry = Pessoa::query();
        $qry->select('codpessoa', 'pessoa', 'fantasia', 'cnpj', 'inativo', 'fisica');

        foreach (explode(' ', $params['pessoa']) as $palavra) {
            if (!empty($palavra)) {
                $qry->whereRaw("(tblpessoa.pessoa ilike '%{$palavra}%' or tblpessoa.fantasia ilike '%{$palavra}%')");
            }
        }

        $numero = (int) preg_replace('/[^0-9]/', '', $params['pessoa']);
		if ($numero > 0) {
            $qry->orWhere('codpessoa', $numero)->orWhere('cnpj', $numero);
		}

        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->fantasia,
                'value' => $item->fantasia,
                'id' => $item->codpessoa,
                'sublabel' => $item->pessoa,
                'stamp' => $item->codpessoa . '</br>' . $item->cnpj
            ];
        }

        return $ret;
    }


    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = Pessoa::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['filial'])) {
            $qry->palavras('filial', $filter['filial']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }


}
