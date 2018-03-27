<?php

namespace App\Mg\Pessoa\Repositories;

use App\Mg\Pessoa\Models\Pessoa;

class PessoaRepository
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

}
