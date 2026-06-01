<?php

namespace Mg\NaturezaOperacao;

use Illuminate\Http\Request;
use Mg\MgController;

class CestController extends MgController
{
    public function autocompletar(Request $request)
    {
        $busca = trim($request->get('busca', ''));
        $qry = Cest::query();

        if (!empty($request->codncm)) {
            $qry->where('codncm', $request->codncm);
        }

        if ($busca !== '') {
            $digitos = preg_replace('/\D/', '', $busca);
            if ($digitos !== '' && $digitos === $busca) {
                $qry->where('cest', 'ilike', $digitos . '%');
            } else {
                $qry->where('descricao', 'ilike', "%{$busca}%");
            }
        }

        $itens = $qry->orderBy('cest')->take(30)->get();
        $ret = [];
        foreach ($itens as $item) {
            $ret[] = [
                'label' => self::formataCest($item->cest) . ' — ' . $item->descricao,
                'value' => $item->codcest,
                'id' => $item->codcest,
                'cest' => $item->cest,
            ];
        }
        return response()->json($ret, 200);
    }

    private static function formataCest(?string $cest): string
    {
        $d = preg_replace('/\D/', '', (string) $cest);
        if (strlen($d) === 7) {
            return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 2);
        }
        return (string) $cest;
    }
}
