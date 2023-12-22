<?php

namespace Mg\GrupoEconomico;

use Carbon\Carbon;
use DB;

use Mg\MgService;
use Mg\Cidade\Cidade;
use Mg\NFePHP\NFePHPService;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Illuminate\Support\Facades\Http;
class GrupoEconomicoService
{

    public static function index($pesquisa)
    {
        $grupos = GrupoEconomico::orderBy('grupoeconomico', 'asc')
        ->where('grupoeconomico', 'ilike', $pesquisa)->paginate(25);

        return $grupos;
    }

    public static function create ($data)
    {

        $pessoa = new GrupoEconomico($data);
        $pessoa->save();
        return $pessoa->refresh();
        
    }

    public static function update ($pessoa, $data)
    {
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }

    
    public static function delete ($grupo)
    {
        $pessoasGrupo = Pessoa::where('codgrupoeconomico', $grupo->codgrupoeconomico)->get();

        foreach ($pessoasGrupo as $peg) {
           $peg->codgrupoeconomico = null;
            $peg->update();
        }

        return $grupo->delete();
    }

    public static function buscarPeloCnpjCpf(bool $fisica, string $cnpj)
    {
        $cnpj = trim(numeroLimpo($cnpj));
        if ($fisica) {
            $pessoa = Pessoa::where('cnpj', $cnpj)
                ->where('fisica', $fisica)
                ->whereNotNull('codgrupoeconomico')
                ->orderBy('alteracao', 'desc')
                ->first();
        } else {
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
            $raiz = substr($cnpj, 0, 8);
            $pessoa = Pessoa::whereRaw("substring(trim(to_char(cnpj, '00000000000000')), 1, 8) ilike '{$raiz}%'")
                ->where('fisica', $fisica)
                ->whereNotNull('codgrupoeconomico')
                ->orderBy('alteracao', 'desc')
                ->first();
        }
        if ($pessoa) {
            return $pessoa->GrupoEconomico;
        }
        return null;
    }


    public static function removerDoGrupo($pessoa) 
    {

        $pessoa->update(['codgrupoeconomico' => null]);
        return $pessoa->refresh();

    }

    public static function inativar(GrupoEconomico $grupo)
    {
        $grupo->update(['inativo' => Carbon::now()]);
        return $grupo->refresh();
    }

    public static function ativar($grupo)
    {
        $grupo->inativo = null;
        $grupo->update();
        return $grupo;
    }

}
