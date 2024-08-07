<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use DB;

use Mg\MgService;
use Mg\Cidade\Cidade;
use Mg\NFePHP\NFePHPService;
use Mg\Filial\Filial;
use Illuminate\Support\Facades\Http;

class GrupoClienteService
{
    /**
     * Busca Autocomplete Quasar
     */
    public static function autocomplete($params)
    {
        $qry = Pessoa::query();

        $qry->select('codpessoa', 'pessoa', 'fantasia', 'cnpj', 'inativo', 'fisica', 'ie');

        if (!empty($params['codpessoa'])) {
            $qry->where('codpessoa', $params['codpessoa']);
        } else if (isset($params['pessoa'])) {
            $nome = $params['pessoa'];
            $qry->where(function ($q) use ($nome) {
                $q->palavras('pessoa', $nome);
            });
            $qry->orWhere(function ($q) use ($nome) {
                $q->palavras('fantasia', $nome);
            });
            $num = preg_replace('/\D/', '', $nome);
            if ($num == $nome) {
                $qry->orWhere('cnpj', $num);
            }
        }

        $qry->orderBy('fantasia', 'asc');
        $ret = $qry->limit(100)->get();
        
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

    public static function buscarPorCnpjIe ($cnpj, $ie)
    {
        $qry = Pessoa::where('cnpj', $cnpj);
        $ie = (int) numeroLimpo($ie);
        if (!empty($ie)) {
            $qry = $qry->where(DB::raw("cast(regexp_replace(ie, '[^0-9]+', '', 'g') as numeric)"), $ie);
        } else {
            $qry = $qry->whereNull('ie');
        }
        return $qry->first();
    }

    public static function podeVenderAPrazo(Pessoa $pessoa, $valorAvaliar = 0)
	{
        // se nao esta vendendo a prazo
        if ($valorAvaliar <= 0) {
            return true;
        }

		// se esta com o credito marcado como bloqueado
		if ($pessoa->creditobloqueado) {
            return false;
        }

		// se tem valor limite definido
        if (!empty($pessoa->credito)) {
            // busca no banco total dos titulos
    		$saldo = $pessoa->TituloS()->sum('saldo');
    		$creditototal = $saldo + $valorAvaliar;
            if ($creditototal > ($pessoa->credito * 1.05)) {
                return false;
            }
        }

        // Tolerancia de Atraso baseado no primeiro titulo
        $titulo = $pessoa->TituloS()->where('saldo', '>', 0)->orderBy('vencimento', 'asc')->first();
        if ($titulo->vencimento->isPast()) {
            if ($titulo->vencimento->diffInDays() > $pessoa->toleranciaatraso) {
                return false;
            }
        }

		return true;
	}

    public static function create ($data)
    {

     
        if (empty($data['ordem'])) {
            $data['ordem'] = GrupoCliente::where('codpessoa', $data['codpessoa'])->max('ordem') + 1;
        }

        $grupo = new GrupoCliente($data);
        $grupo->save();

        return $grupo->refresh();

    }

    public static function createOrUpdate ($data)
    {
    
        $grupo = GrupoCliente::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')->orderBy('ordem')
            ->first();

        if ($grupo) {
            return static::update($grupo, $data);
        } else {
            return static::create($data);
        }
    }

    public static function update ($pessoa, $data)
    {
        
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }

    
    public static function delete ($pessoa)
    {
        return $pessoa->delete();
    }
  

    public static function buscarPeloCnpjCpfGrupoCliente(bool $fisica, string $cnpj)
    {
        $cnpj = trim(numeroLimpo($cnpj));
        if ($fisica) {
            $pessoa = Pessoa::where('cnpj', $cnpj)
                ->where('fisica', $fisica)
                ->whereNotNull('codgrupocliente')
                ->orderBy('alteracao', 'desc')
                ->first();
        } else {
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
            $raiz = substr($cnpj, 0, 8);
            $pessoa = Pessoa::whereRaw("substring(trim(to_char(cnpj, '00000000000000')), 1, 8) ilike '{$raiz}%'")
                ->where('fisica', $fisica)
                ->whereNotNull('codgrupocliente')
                ->orderBy('alteracao', 'desc')
                ->first();
        }
        if ($pessoa) {
            return $pessoa->GrupoCliente;
        }
        return null;
    }

}
