<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use DB;

use Mg\MgService;
use Mg\Cidade\Cidade;
use Mg\NFePHP\NFePHPService;
use Mg\Filial\Filial;

class PessoaService
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
        $pessoa = new Pessoa($data);
        $pessoa->save();
        return $pessoa->refresh();
    }

    public static function update (Pessoa $pessoa, $data)
    {
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }

    public static function delete (Pessoa $pessoa)
    {
        return $pessoa->delete();
    }

    public static function ativar (Pessoa $pessoa)
    {
        $pessoa->update(['inativo' => null]);
        return $pessoa->refresh();
    }

    public static function inativar (Pessoa $pessoa)
    {
        $pessoa->update(['inativo' => Carbon::now()]);
        return $pessoa->refresh();
    }

    public static function importarReceitaWs ($cnpj)
    {

        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => "https://receitaws.com.br/v1/cnpj/{$cnpj}",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer " . env('RECEITA_WS_TOKEN')
          ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        dd($response);
        // CODIGO IMPORTAR E SALVAR UMA PESSOA
        // $pessoa = new Pessoa($data);
        $pessoa = Pessoa::findOrFail(1);
        return $pessoa->refresh();
    }

    public static function importarSefaz ($uf, $cnpj, $cpf, $ie)
    {
        $filial = Filial::findOrFail(101);
        $data = NFePHPService::sefazCadastro($filial, $uf, $cnpj, $cpf, $ie);
        // dd($data->infCons->infCad->IE);
        dd($data);
        return $pessoa->refresh();
    }

}
