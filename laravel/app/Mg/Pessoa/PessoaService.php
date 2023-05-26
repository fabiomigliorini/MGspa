<?php

namespace Mg\Pessoa;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use DB;

use Mg\MgService;
use Mg\Cidade\Estado;
use Mg\Cidade\Cidade;
use Mg\GrupoEconomico\GrupoEconomicoService;
use Mg\Pessoa\GrupoClienteService;
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

    public static function importar ($codfilial, $uf, $cnpj, $cpf, $ie)
    {
        $retReceita = null;

        // Se veio um CNPJ, consulta a receita com esse CNPJ
        if (!empty($cnpj)) {
            $cnpj = numeroLimpo($cnpj);
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
            $retReceita = static::buscarReceitaWs($cnpj);
            if ($retReceita->status() != 200 || $retReceita['status'] == "ERROR") {
                throw new \Exception($retReceita['message'], 1);
	        }
            $uf = $retReceita['uf']??null;
        }

        // Consulta o CNPJ / CPF ou IE na Sefaz
        $retSefaz = null;
        $retIes = [];
        if (!empty($cnpj) || !empty($cpf) || (!empty($ie))) {
            $filial = Filial::findOrFail($codfilial);
            if (empty($uf)) {
                $uf = $filial->Pessoa->Cidade->Estado->sigla;
            }

            try {

                $retSefaz = NFePHPService::sefazCadastro($filial, $uf, $cnpj, $cpf, $ie);
                switch ($retSefaz->infCons->cStat) {
                    case '259': // Rejeição: CNPJ da consulta não cadastrado como contribuinte na UF
                    case '264': // Rejeicao: CPF da consulta nao cadastrado como contribuinte na UF
                        break;
                    case '111': // Consulta cadastro com uma ocorrência
                        $retIes = [$retSefaz->infCons->infCad];
                        break;
                    case '112': // Consulta cadastro com mais de uma ocorrencia
                        $retIes = $retSefaz->infCons->infCad;
                        break;
                    default:
                        break;
                }

            } catch (\Exception $e) {

                $message = $e->getMessage();
                if (substr($message, 0, 45) != "Servico [NfeConsultaCadastro] indisponivel UF") {
                    throw new \Exception($e->getMessage());
                }
                
            }

            if (isset($retIes[0])) {
                $cnpj = $retIes[0]->CNPJ??'';
                $cpf = $retIes[0]->CPF??'';
            }
        }

        // Caso a consulta da sefaz tenha retornado um CNPJ
        // Faz a consulta na receita para esse CNPJ
        if (!empty($cnpj) && empty($retReceita)) {
            $retReceita = static::buscarReceitaWs($cnpj);
        }

        // Se nao estiver vazio o CNPJ/CPF, tenta descobrir Grupo Economico/Cliente de outros cadastros
        // do mesmo CNPJ/CPF
        if (!empty($cnpj)) {
            $grupo = GrupoEconomicoService::buscarPeloCnpjCpf(false, $cnpj);
            $grupocliente = GrupoClienteService::buscarPeloCnpjCpfGrupoCliente(false, $cnpj);

        } elseif (!empty($cpf)) {
            $grupo = GrupoEconomicoService::buscarPeloCnpjCpf(true, $cpf);
            $grupocliente = GrupoClienteService::buscarPeloCnpjCpfGrupoCliente(true, $cpf);
        }
        $codgrupoeconomico = @$grupo->codgrupoeconomico;
        $codgrupocliente = @$grupocliente->codgrupocliente;

        // Percorre todas as inscricoes da sefaz, criando uma tblPessoa para cada
        $retPessoas = [];
        foreach ($retIes as $retIe) {

            // Verifica se combinacao CPF/CNPJ/IE ja esta cadastrada
            $pessoa = static::buscarPorCnpjIe($retIe->CNPJ??$retIe->CPF, $retIe->IE);
            if ($pessoa == null) {
                if ($retIe->cSit == 0) {
                    continue;
                }
                $pessoa = new Pessoa();
                $pessoa->fantasia = $retIe->xFant??$retIe->xNome;
                $pessoa->ie = $retIe->IE;
            }

            // Vincula ao GrupoEonomico/Cliente buscado anteriormente
            $pessoa->codgrupocliente = $codgrupocliente;
            $pessoa->codgrupoeconomico = $codgrupoeconomico;

            // Marca se fisica ou juridica
            if (isset($retIe->CNPJ)) {
                $pessoa->fisica = false;
                $pessoa->cnpj = $retIe->CNPJ;
            } else {
                $pessoa->fisica = true;
                $pessoa->cnpj = $retIe->CPF;
            }

            // Se IE Ativa / Inativa
            if ($retIe->cSit == 1) {
                $pessoa->inativo = null;
            } elseif (empty($pessoa->inativo)) {
                $pessoa->inativo = Carbon::now();
            }

            // Vinclua razao social
            $pessoa->pessoa = $retIe->xNome;
            $pessoa->notafiscal = 0;

            // Se veio somente um endereco, forca como array de enderecos pra simplificar logica
            if (!is_array($retIe->ender)) {
                $retIe->ender = [$retIe->ender];
            }

            // Descobre o codigo da cidade
            $estado = Estado::firstWhere(['sigla' => $retIe->UF]);
            $cidade = Cidade::where(
                'codestado', $estado->codestado
            )->where(
                'cidade', 'ilike', trim(removeAcentos($retIe->ender[0]->xMun))
            )->first();
            $pessoa->codcidade = $cidade->codcidade;

            // salva e acumula no array de pessoas criadas
            $pessoa->save();
            $retPessoas[] = $pessoa->fresh();

            // cria os enderecos vinculados a pessoa
            foreach ($retIe->ender as $endIe) {
                PessoaEnderecoService::createOrUpdate([
                    'codpessoa' => $pessoa->codpessoa,
                    'endereco' => $endIe->xLgr,
                    'numero' => $endIe->nro,
                    'complemento' => substr(trim($endIe->xCpl??null), 0, 50),
                    'bairro' => $endIe->xBairro,
                    'codcidade' => $cidade->codcidade,
                    'cep'   => numeroLimpo($endIe->CEP)
                ]);
            }
        }

        // caso nao tenha achado nenhuma IE na sefaz
        // cria a pessoa com base no array da receita ws
        if (sizeof($retPessoas) == 0 && !empty($retReceita)) {
            // dd($retReceita->json());

            // verifica se a pessoa ja existe
            $pessoa = static::buscarPorCnpjIe(numeroLimpo($retReceita['cnpj']), null);
            if ($pessoa == null) {
                $pessoa = new Pessoa();
                $pessoa->fantasia = $retReceita['fantasia'];
                if (empty($pessoa->fantasia)) {
                    $pessoa->fantasia = $retReceita['nome'];
                }
            }

            // Vincula ao GrupoEonomico/Cliente buscado anteriormente
            $pessoa->codgrupocliente = $codgrupocliente;
            $pessoa->codgrupoeconomico = $codgrupoeconomico;

            // vincula CNPJ
            $pessoa->fisica = false;
            $pessoa->cnpj = numeroLimpo($retReceita['cnpj']);
            $pessoa->pessoa = $retReceita['nome'];
            $pessoa->notafiscal = 0;

            // Se CNPJ Ativa / Inativa
            if ($retReceita['situacao'] == 'ATIVA') {
                $pessoa->inativo = null;
            } elseif (empty($pessoa->inativo)) {
                $pessoa->inativo = Carbon::now();
            }

            // Descobre o codigo da cidade
            $estado = Estado::firstWhere(['sigla' => $retReceita['uf']]);
            $cidade = Cidade::where(
                'codestado', $estado->codestado
            )->where(
                'cidade', 'ilike', removeAcentos($retReceita['municipio'])
            )->first();
            $pessoa->codcidade = $cidade->codcidade;

            // salva e acumula no array de pessoas criadas
            $pessoa->save();
            $retPessoas[] = $pessoa->fresh();

        }

        // Sempre cria o endereco, email e telefone retornado pela receita
        // porque a sefaz nao retorna essas informacoes na api dela
        if (!empty($retReceita)) {
            foreach ($retPessoas as $pessoa) {

                // Endereco
                PessoaEnderecoService::createOrUpdate([
                    'codpessoa' => $pessoa->codpessoa,
                    'endereco' => $retReceita['logradouro'],
                    'numero' => $retReceita['numero'],
                    'bairro' => $retReceita['bairro'],
                    'complemento' => substr(trim($retReceita['complemento']), 0, 50),
                    'codcidade' => $pessoa->codcidade,
                    'cep'   => numeroLimpo($retReceita['cep'])
                ]);

                // Email
                if (!empty($retReceita['email'])) {
                    PessoaEmailService::createOrUpdate([
                        'codpessoa' => $pessoa->codpessoa,
                        'email' => $retReceita['email']
                    ]);
                }

                // Telefone (Pode retornar string com vario separado por /)
                // Ex. "(66) 3532-7678 / (66) 99999-9999"
                $strtel = $retReceita['telefone'];
                $tels = explode("/", $strtel);
                $telefones = [];
                foreach ($tels as $tel) {
                    $tel = (int) numeroLimpo($tel);
                    if (empty($tel)) {
                        continue;
                    }
                    $telefones[] = PessoaTelefoneService::createOrUpdate([
                        'codpessoa' => $pessoa->codpessoa,
                        'ddd'       => substr($tel, 0, 2),
                        'telefone'  => substr($tel, 2)
                    ]);
                }
            }
        }

        // Atualiza campos legados de email/telefone/endereco
        foreach ($retPessoas as $pessoa) {
            static::atualizaCamposLegado($pessoa);
        }

        // retorna todas as pessoas criadas/atualizadas
        return $retPessoas;

     }


     public static function atualizaCamposLegado(Pessoa $pessoa)
    {
        $data = [];

        $i = 0;
        foreach ($pessoa->PessoaTelefoneS()->orderBy('ordem')->whereNull('inativo')->limit(3)->get() as $tel) {
            $i++;
            $data["telefone{$i}"] = "({$tel->ddd}) {$tel->telefone}";
        }
        if (empty($data["telefone"])) {
            $data['telefone'] = '(66) 9999-9999';
        }

        $i = 0;
        foreach ($pessoa->PessoaEmails()->orderBy('ordem')->whereNull('inativo')->limit(3)->get() as $email) {
            $i++;
            switch ($i) {
                case 1:
                    $data["email"] = $email->email;
                    break;

                case 2:
                    $data["emailnfe"] = $email->email;
                    break;

                case 3:
                    $data["emailcobranca"] = $email->email;
                    break;
            }
        }
        if (empty($data["email"])) {
            $data['email'] = 'nfe@mgpapelaria.com.br';
        }

        //TODO: adicionar complemento
        if ($endereco = PessoaEndereco::where('codpessoa', $pessoa->codpessoa)->whereNull('inativo')->orderBy('ordem')->first()) {
            $data['endereco'] = $endereco->endereco;
            $data['numero'] = $endereco->numero;
            $data['complemento'] = $endereco->complemento;
            $data['bairro'] = $endereco->bairro;
            $data['codcidade'] = $endereco->codcidade;
            $data['cep'] = $endereco->cep;
            $data['enderecocobranca'] = $endereco->endereco;
            $data['numerocobranca'] = $endereco->numero;
            $data['complementocobranca'] = $endereco->complemento;
            $data['bairrocobranca'] = $endereco->bairro;
            $data['codcidadecobranca'] = $endereco->codcidade;
            $data['cepcobranca'] = $endereco->cep;
        } else {
            $data['endereco'] = 'Nao Informado';
            $data['numero'] = 'S/N';
            $data['complemento'] = Null;
            $data['bairro'] = 'Nao Informado';
            $data['codcidade'] = $pessoa->codcidade;
            $data['cep'] = '78550000';
            $data['enderecocobranca'] = 'Nao Informado';
            $data['numerocobranca'] = 'S/N';
            $data['complementocobranca'] = Null;
            $data['bairrocobranca'] = 'Nao Informado';
            $data['codcidadecobranca'] = $pessoa->codcidade;
            $data['cepcobranca'] = '78550000';
        }

        return PessoaService::update($pessoa, $data);
    }

    public static function buscarReceitaWs ($cnpj)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer' . env('RECEITA_WS_TOKEN')
        ])->get('https://receitaws.com.br/v1/cnpj/'. $cnpj);

        return $response;
    }

}
