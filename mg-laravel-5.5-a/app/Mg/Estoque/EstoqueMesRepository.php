<?php
namespace Mg\Estoque;
use Mg\MgRepository;
use Carbon\Carbon;

class EstoqueMesRepository extends MgRepository
{
    public static function buscaOuCria($codprodutovariacao, $codestoquelocal, $fiscal, $data)
    {
        $es = EstoqueSaldoRepository::buscaOuCria($codprodutovariacao, $codestoquelocal, $fiscal);
        $mes = Carbon::today();
        $mes->day = 1;
        $mes->month = $data->month;
        $mes->year = $data->year;

        // Se for fiscal cria somente um mês por ano, dezembro, até 2016
        if ($fiscal && $mes->year <= 2016) {
            $mes->month = 12;
        }

        $em = EstoqueMes::where('codestoquesaldo', $es->codestoquesaldo)->where('mes', $mes)->first();
        if ($em == false)
        {
            $em = new EstoqueMes();
            $em->codestoquesaldo = $es->codestoquesaldo;
            $em->mes = $mes;
            $em->save();
        }
        return $em;
    }

    public Static function buscaProximos($codestoquesaldo, $mes, $qtd = 7)
    {
        $ems = EstoqueMes::where('codestoquesaldo', $codestoquesaldo)
               ->where('mes', '>', $mes)
               ->orderBy('mes', 'asc')
               ->take($qtd)
               ->get();

        return $ems;
    }

    public Static function buscaAnteriores($codestoquesaldo, $mes, $qtd = 7)
    {
        $ems = EstoqueMes::where('codestoquesaldo', $codestoquesaldo)
               ->where('mes', '<', $mes)
               ->orderBy('mes', 'desc')
               ->take($qtd)
               ->get();

        return $ems->reverse();
    }

}
