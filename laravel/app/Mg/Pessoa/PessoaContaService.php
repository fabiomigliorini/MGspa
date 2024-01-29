<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use DB;

use Mg\MgService;
use Mg\Cidade\Cidade;
use Mg\NFePHP\NFePHPService;
use Mg\Filial\Filial;
use Illuminate\Support\Facades\Http;
class PessoaContaService
{

    public static function create ($data)
    {

        $pessoa = new PessoaConta($data);
        $pessoa->save();
        return $pessoa->refresh();
        
    }

    public static function update ($pessoa, $data)
    {
        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }



    public static function createOrUpdate ($data)
    {
    
        $conta = PessoaConta::where('codpessoa', $data['codpessoa'])
            ->whereNull('inativo')->orderBy('alteracao')
            ->first();

        if ($conta) {
            return static::update($conta, $data);
        } else {
            return static::create($data);
        }
    }
    
    public static function delete ($pessoa)
    {
        return $pessoa->delete();
    }

    public static function ativar($pessoaConta)
    {
        $pessoaConta->inativo = null;
        $pessoaConta->update();
        return $pessoaConta;
    }

    public static function inativar($pessoaConta, $date = null)
    {
        if (empty($date)) {
            $date = Carbon::now();
        }
        $pessoaConta->inativo = $date;
        $pessoaConta->update();
        return $pessoaConta;
    }
  

}
