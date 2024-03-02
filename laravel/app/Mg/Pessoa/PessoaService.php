<?php

namespace Mg\Pessoa;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Mg\Cidade\Estado;
use Mg\Cidade\Cidade;
use Mg\Cidade\CidadeService;
use Mg\GrupoEconomico\GrupoEconomicoService;
use Mg\Pessoa\GrupoClienteService;
use Mg\NFePHP\NFePHPService;
use Mg\Filial\Filial;
use stdClass;

class PessoaService
{
    const NOTAFISCAL_TRATAMENTOPADRAO = 0;
    const NOTAFISCAL_SEMPRE = 1;
    const NOTAFISCAL_SOMENTE_FECHAMENTO = 2;
    const NOTAFISCAL_NUNCA = 9;

    const CONSUMIDOR = 1;

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


    public static function index(
        $limit,
        $offset,
        $codpessoa,
        $pessoa,
        $cnpj,
        $email,
        $fone,
        $codgrupoeconomico,
        $codcidade,
        $inativo,
        $codformapagamento,
        $codgrupocliente
    ) {

        $sql = '
            select p.* 
            from tblpessoa p 
            left join tblgrupoeconomico ge on (ge.codgrupoeconomico = p.codgrupoeconomico)
        ';

        $where = [];
        if ($pessoa) {
            $pessoa = str_replace(' ', '%', preg_replace('/\s\s+/', ' ', $pessoa));
            $where[] = '(p.pessoa || p.fantasia || coalesce(ge.grupoeconomico, \'\')) ilike :pessoa';
            $params['pessoa'] = "%{$pessoa}%";
        }

        if ($codgrupoeconomico) {
            $where[] = 'p.codgrupoeconomico = :codgrupoeconomico';
            $params['codgrupoeconomico'] = $codgrupoeconomico;
        }

        if (!empty($fone)) {
            $where[] = ' 
                p.codpessoa in (
                    select pt.codpessoa
                    from tblpessoatelefone pt
                    where cast(pt.telefone as varchar) ilike :fone
                )';
            $params['fone'] = "%{$fone}%";
        }

        if (!empty($email)) {
            $where[] = ' 
                p.codpessoa in (
                    select pe.codpessoa
                    from tblpessoaemail pe
                    where pe.email ilike :email
                )';
            $params['email'] = "%{$email}%";
        }

        if (!empty($cnpj)) {
            $cnpj = numerolimpo($cnpj);
            $where[] = 'to_char(p.cnpj, case when fisica then \'FM00000000000\' else \'FM00000000000000\' end) ilike :cnpj';
            $params['cnpj'] = "{$cnpj}%";
        }

        if (!empty($codpessoa)) {
            $where[] = 'p.codpessoa = :codpessoa';
            $params['codpessoa'] = $codpessoa;
        }

        if (!empty($codcidade)) {
            $where[] = 'p.codcidade = :codcidade';
            $params['codcidade'] = $codcidade;
        }

        if (!empty($codformapagamento)) {
            $where[] = 'p.codformapagamento = :codformapagamento';
            $params['codformapagamento'] = $codformapagamento;
        }

        if (!empty($codgrupocliente)) {
            $where[] = 'p.codgrupocliente = :codgrupocliente';
            $params['codgrupocliente'] = $codgrupocliente;
        }

        switch ($inativo) {
            case 'A':
                $where[] = 'p.inativo is null';
                break;

            case 'I':
                $where[] = 'p.inativo is not null';
                break;
        }

        if (sizeof($where) > 0) {
            $sql .= ' where ' . implode(' and ', $where);
        }

        $sql .= ' order by p.fantasia limit :limit offset :offset ';

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $regs = DB::select($sql, $params);
        $result = Pessoa::hydrate($regs);
        return $result;
    }


    public static function buscarPorCnpjIe($cnpj, $ie)
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

    public static function buscaCodGrupoEconomicoPelaRaizCnpj($cnpj)
    {
        $raiz = substr(str_pad(numeroLimpo($cnpj), 14, '0'), 0, 8);
        $sql = '
            select codgrupoeconomico 
            from tblpessoa
            where to_char(cnpj, \'FM00000000000000\') ilike :raiz
            and codgrupoeconomico is not null
            order by alteracao desc
            limit 1
        ';
        if ($ret = DB::selectOne($sql, ['raiz' => "{$raiz}%"])) {
            return $ret->codgrupoeconomico;
        }
        return null;
    }

    public static function buscaCodGrupoEconomicoPeloCpf($cpf)
    {
        $sql = '
            select codgrupoeconomico 
            from tblpessoa
            where to_char(cnpj, \'FM00000000000\') ilike :cpf
            and codgrupoeconomico is not null
            order by alteracao desc
            limit 1
        ';
        if ($ret = DB::selectOne($sql, ['cpf' => $cpf])) {
            return $ret->codgrupoeconomico;
        }
        return null;
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

    public static function verificaDuplicadoCnpjIe($cnpj, $ie)
    {
        $sql = "
        select count(*) as quant
        from tblpessoa p
        where p.cnpj = :cnpj 
        and regexp_replace(ie, '[^0-9]+', '', 'g')::numeric = regexp_replace(:ie, '[^0-9]+', '', 'g')::numeric
        ";

        $params['cnpj'] = $cnpj;
        $params['ie'] = $ie;

        $result = DB::select($sql, $params);

        return $result;
    }

    public static function createPelaSefazReceitaWs($data)
    {
        $dataPessoa = [
            'pessoa' => $data['pessoa'],
            'fantasia' => $data['fantasia'],
            'fisica' => $data['fisica'],
            'cnpj' => $data['cnpj'],
            'cliente' => $data['cliente'],
            'fornecedor' => $data['fornecedor'],
            'ie' => $data['ie'],
            'consumidor' => $data['consumidor'] ?? true,
            'creditobloqueado' => true,
            'vendedor' => false,
            'notafiscal' => 0,
        ];
        if ($dataPessoa['ie'] == 'OUTRA') {
            $dataPessoa['ie'] = $data['ieoutra'];
        }
        if ($data['fisica']) {
            $dataPessoa['codgrupoeconomico'] = static::buscaCodGrupoEconomicoPeloCpf($data['cnpj']);
        } else {
            $dataPessoa['codgrupoeconomico'] = static::buscaCodGrupoEconomicoPelaRaizCnpj($data['cnpj']);
        }
        $pessoa = static::create($dataPessoa);

        if (isset($data['sefaz']['ender'])) {

            // Se veio somente um endereco, forca como array de enderecos pra simplificar logica
            if (isset($data['sefaz']['ender']['xLgr'])) {
                $ends = [$data['sefaz']['ender']];
            } else {
                $ends = $data['sefaz']['ender'];
            }

            // adiciona todos enderecos que a sefaz mandou
            foreach ($ends as $end) {
                $cidade = CidadeService::buscaPeloNomeUf($end['xMun'], $data['sefaz']['UF']);
                if ($cidade) {
                    $end = PessoaEnderecoService::create([
                        'codpessoa' => $pessoa->codpessoa,
                        'cep' => numeroLimpo($end['CEP'] ?? '00000000'),
                        'endereco' => substr($end['xLgr'] ?? 'Nao Informado', 0, 100),
                        'numero' => substr($end['nro'] ?? 'S/N', 0, 10),
                        'complemento' => substr($end['xCpl'] ?? null, 0, 50),
                        'bairro' => substr($end['xBairro'] ?? 'Nao Informado', 0, 50),
                        'codcidade' => $cidade->codcidade,
                    ]);
                }
            }
        }

        if ($data['receitaWs']) {
            $rec = $data['receitaWs'];
            if ($rec['abertura']) {
                $pessoa->nascimento = Carbon::createFromFormat('d/m/Y', $rec['abertura']);
                $pessoa->save();
            }
            $cidade = CidadeService::buscaPeloNomeUf($rec['municipio'], $rec['uf']);
            if ($cidade) {
                $end = PessoaEnderecoService::create([
                    'codpessoa' => $pessoa->codpessoa,
                    'cep' => numeroLimpo($rec['cep'] ?? '00000000'),
                    'endereco' => substr($rec['logradouro'] ?? 'Nao Informado', 0, 100),
                    'numero' => substr($rec['numero'] ?? 'S/N', 0, 10),
                    'complemento' => substr($rec['complemento'] ?? null, 0, 50),
                    'bairro' => substr($rec['bairro'] ?? 'Nao Informado', 0, 50),
                    'codcidade' => $cidade->codcidade,
                ]);
            }
            if ($rec['email']) {
                PessoaEmailService::createOrUpdate([
                    'codpessoa' => $pessoa->codpessoa,
                    'email' => $rec['email'],
                ]);
            }
            if ($rec['telefone']) {
                // Telefone (Pode retornar string com vario separado por /)
                // Ex. "(66) 3532-7678 / (66) 99999-9999"
                $strtel = $rec['telefone'];
                $tels = explode("/", $strtel);
                foreach ($tels as $tel) {
                    $tel = (int) numeroLimpo($tel);
                    if (empty($tel)) {
                        continue;
                    }
                    PessoaTelefoneService::createOrUpdate([
                        'codpessoa' => $pessoa->codpessoa,
                        'ddd'       => substr($tel, 0, 2),
                        'telefone'  => substr($tel, 2)
                    ]);
                }
            }
        }
        return $pessoa;
    }


    public static function create($data)
    {
        $pessoa = new Pessoa($data);
        // $cnpjDuplicado = static::verificaDuplicadoCnpjIe($data['cnpj'] ?? null, $data['ie'] ?? null);
        // if ($cnpjDuplicado[0]->quant > 0) {
        //     throw new Exception("Cnpj/CPF /Ie já está cadastrado!", 1);
        // }
        $pessoa->save();
        return $pessoa->refresh();
    }

    public static function update(Pessoa $pessoa, $data)
    {

        if (!empty($data['creditobloqueado'])) {
            $creditobloqueado = filter_var($data['creditobloqueado'], FILTER_VALIDATE_BOOLEAN);
            $consumidor = filter_var($data['consumidor'], FILTER_VALIDATE_BOOLEAN);

            $pessoa->fill($data);
            $pessoa->creditobloqueado = $creditobloqueado;
            $pessoa->consumidor = $consumidor;
            $pessoa->save();
            return $pessoa;
        }
        if (!empty($data['cliente'])) {
            if (empty($data['codgrupoeconomico'])) {
                $pessoa->codgrupoeconomico = null;
            }
            $fisica = filter_var($data['fisica'], FILTER_VALIDATE_BOOLEAN);
            $cliente = filter_var($data['cliente'], FILTER_VALIDATE_BOOLEAN);
            $fornecedor = filter_var($data['fornecedor'], FILTER_VALIDATE_BOOLEAN);
            $vendedor = filter_var($data['vendedor'], FILTER_VALIDATE_BOOLEAN);

            $pessoa->fill($data);
            $pessoa->fisica = $fisica;
            $pessoa->cliente = $cliente;
            $pessoa->fornecedor = $fornecedor;
            $pessoa->vendedor = $vendedor;
            $pessoa->save();
            return $pessoa;
        }

        $pessoa->fill($data);
        $pessoa->save();
        return $pessoa;
    }

    public static function buscaSigla($codcidade)
    {
        $sql = "
        select c.codcidade, e.sigla
        from tblcidade c
        inner join tblestado e on (e.codestado = c.codestado)  where c.codcidade = :codcidade
        ";

        $params['codcidade'] = $codcidade;

        $ret = DB::select($sql, $params);

        return $ret;
    }

    public static function delete(Pessoa $pessoa)
    {
        return $pessoa->delete();
    }

    public static function ativar(Pessoa $pessoa)
    {
        $pessoa->update(['inativo' => null]);
        return $pessoa->refresh();
    }

    public static function inativar(Pessoa $pessoa)
    {
        $pessoa->update(['inativo' => Carbon::now()]);
        return $pessoa->refresh();
    }

    public static function importar($codfilial, $uf, $cnpj, $cpf, $ie)
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
            $uf = $retReceita['uf'] ?? null;
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
                $cnpj = $retIes[0]->CNPJ ?? '';
                $cpf = $retIes[0]->CPF ?? '';
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

        dd($retIes);

        foreach ($retIes as $retIe) {

            // Verifica se combinacao CPF/CNPJ/IE ja esta cadastrada
            $pessoa = static::buscarPorCnpjIe($retIe->CNPJ ?? $retIe->CPF, $retIe->IE);

            if ($pessoa == null) {
                if ($retIe->cSit == 0) {
                    continue;
                }
                $pessoa = new Pessoa();
                $pessoa->fantasia = substr($retIe->xFant ?? $retIe->xNome, 0, 50);
                $pessoa->ie = $retIe->IE;
            }

            // Vincula ao GrupoEonomico/Cliente buscado anteriormente
            $pessoa->codgrupocliente = $codgrupocliente;

            // Marca se fisica ou juridica
            if (isset($retIe->CNPJ)) {
                $pessoa->fisica = false;
                $pessoa->cnpj = $retIe->CNPJ;
                $pessoa->codgrupoeconomico = static::buscaCodGrupoEconomicoPelaRaizCnpj($retIe->CNPJ);
            } else {
                $pessoa->fisica = true;
                $pessoa->cnpj = $retIe->CPF;
                $pessoa->codgrupoeconomico = static::buscaCodGrupoEconomicoPeloCpf($retIe->CNPJ);
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
            if (isset($retIe->ender[0]->cMun)) {
                $cidade = Cidade::where('codigooficial', $retIe->ender[0]->cMun)->first();
                $estado = $cidade->Estado;
            } else {
                $estado = Estado::firstWhere(['sigla' => $retIe->UF]);
                $cidade = Cidade::where('codestado', $estado->codestado)
                    ->where('cidade', 'ilike', trim(removeAcentos($retIe->ender[0]->xMun)))
                    ->first();
            }
            $pessoa->codcidade = $cidade->codcidade;

            // salva e acumula no array de pessoas criadas
            $pessoa->save();
            $retPessoas[] = $pessoa->fresh();

            // cria os enderecos vinculados a pessoa
            foreach ($retIe->ender as $endIe) {
                PessoaEnderecoService::createOrUpdate([
                    'codpessoa' => $pessoa->codpessoa,
                    'endereco' => $endIe->xLgr,
                    'numero' => @$endIe->nro,
                    'complemento' => substr(trim($endIe->xCpl ?? null), 0, 50),
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
                $pessoa->fantasia = substr($retReceita['fantasia'], 0, 50);
                if (empty($pessoa->fantasia)) {
                    $pessoa->fantasia = substr($retReceita['nome'], 0, 50);
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
                'codestado',
                $estado->codestado
            )->where(
                'cidade',
                'ilike',
                removeAcentos($retReceita['municipio'])
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
        foreach ($pessoa->PessoaEmails()->whereNull('inativo')->orderBy('nfe', 'desc')->orderBy('ordem')->limit(3)->get() as $email) {
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
        if ($endereco = PessoaEndereco::where('codpessoa', $pessoa->codpessoa)->whereNull('inativo')->orderBy('nfe', 'desc')->orderBy('ordem')->first()) {
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

    public static function buscarReceitaWs($cnpj)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer' . env('RECEITA_WS_TOKEN')
        ])->get('https://receitaws.com.br/v1/cnpj/' . $cnpj);

        return $response;
    }


    public static function verificaIeSefaz($codfilial, $uf, $cnpj, $cpf, $ie)
    {

        $retReceita = null;

        // Se veio um CNPJ, consulta a receita com esse CNPJ
        if (!empty($cnpj)) {
            $cnpj = numeroLimpo($cnpj);
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
            $retReceita = static::buscarReceitaWs($cnpj);
            // if ($retReceita->status() != 200 || $retReceita['status'] == "ERROR") {
            // throw new \Exception($retReceita['message'], 1);
            // }
            $uf = $retReceita['uf'] ?? null;
        }

        // Consulta o CNPJ / CPF ou IE na Sefaz
        $retSefaz = null;
        $retIes = [];
        $filial = Filial::findOrFail($codfilial);
        if (empty($uf)) {
            $uf = $filial->Pessoa->Cidade->Estado->sigla;
        }

        // tenta consultar a sefaz
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
        } catch (Exception $e) {
            $message = $e->getMessage();
            // ignora se o erro foi Indisponibilidade para a UF
            if (substr($message, 0, 45) != "Servico [NfeConsultaCadastro] indisponivel UF") {
                throw new Exception($e->getMessage());
            }
        }

        if (isset($retIes[0])) {
            $cnpj = $retIes[0]->CNPJ ?? '';
            $cpf = $retIes[0]->CPF ?? '';
        }

        // Caso a consulta da sefaz tenha retornado um CNPJ
        // Faz a consulta na receita para esse CNPJ
        if (!empty($cnpj) && empty($retReceita)) {
            $retReceita = static::buscarReceitaWs($cnpj);
        }

        $resultReceita = null;

        if ($retReceita !== null) {
            $resultReceita = json_decode($retReceita);
        }

        return [
            'retReceita' => $resultReceita,
            'retSefaz' => $retIes
        ];
    }

    public static function aniversarios($tipo)
    {
        if (!$tipo) {
            $tipo = 'todos';
        }

        $sql = '
        with aniversarios as (
            select 
                date_part(\'month\', p.nascimento) as mes, 
                date_part(\'day\', p.nascimento) as dia, 
                date_part(\'year\', now()) - date_part(\'year\', p.nascimento) as idade,
                \'Idade\' as tipo,
                p.pessoa, 
                p.codpessoa,
                p.nascimento as data
            from tblpessoa p
            where p.nascimento is not null
        ';

        switch ($tipo) {
            case 'cliente':
                $sql .= ' and p.codpessoa not in ( --filtro colaborador
                    select c.codpessoa
                    from tblcolaborador c
                    where c.rescisao is null
                ) and p.cliente )';
                break;
            case 'fornecedor':
                $sql .= ' and p.fornecedor = true )';
                break;

            case 'colaborador':
                $sql .= ' and p.codpessoa in ( --filtro colaborador
                select c.codpessoa
                from tblcolaborador c
                where c.rescisao is null
                )';
        }

        switch ($tipo) {
            case 'colaborador':
            case 'todos':
                $sql .= '
                union all
                select 
                    date_part(\'month\', c.contratacao) as mes, 
                    date_part(\'day\', c.contratacao) as dia, 
                    date_part(\'year\', now()) - date_part(\'year\', c.contratacao) as idade,
                    \'Empresa\' as tipo,
                    p.pessoa,
                    c.codpessoa, 
                    c.contratacao as data
                from tblcolaborador c
                inner join tblpessoa p on (p.codpessoa = c.codpessoa)
                where c.rescisao is null
                and date_part(\'year\', c.contratacao) < date_part(\'year\', CURRENT_DATE) -- ate aqui somente se Todos ou Colaborador
                )';
                break;
        }

        $sql .= '
        select * 
        from aniversarios 
        order by mes, dia
        ';

        $result = DB::select($sql);

        return $result;
    }


    public static function aniversariosColaboradores()
    {

        $sql = '
        with anivB as (
            with anivA as (
                        select 
                            date_part(\'month\', p.nascimento) as mes, 
                            date_part(\'day\', p.nascimento) as dia, 
                            date_part(\'year\', :data::date) - date_part(\'year\', p.nascimento) as idade,
                            \'Idade\' as tipo,
                            p.pessoa, 
                            p.codpessoa,
                            p.nascimento as data
                        from tblpessoa p
                        where p.nascimento is not null
                        and p.codpessoa in (
                            select c.codpessoa
                            from tblcolaborador c
                            where c.rescisao is null
                        )
                        --and p.nascimento 
                        union all 
                        select 
                            date_part(\'month\', c.contratacao) as mes, 
                            date_part(\'day\', c.contratacao) as dia, 
                            date_part(\'year\', :data::date) - date_part(\'year\', c.contratacao) as idade,
                            \'Empresa\' as tipo,
                            p.pessoa,
                            c.codpessoa, 
                            c.contratacao as data
                        from tblcolaborador c
                        inner join tblpessoa p on (p.codpessoa = c.codpessoa)
                        where c.rescisao is null
                        and date_part(\'year\', c.contratacao) < date_part(\'year\', :data::date) -- ate aqui somente se Todos ou Colaborador
            )
            select *
                ,
                to_date(
                    case when (date_part(\'month\', :data::date) = 12) then 
                        case when (a.mes = 1) then 
                            date_part(\'year\', :data::date) + 1
                        else 
                            date_part(\'year\', :data::date) 
                        end
                    else 
                        date_part(\'year\', :data::date) 
                    end
                    || \'-\' || a.mes || \'-\' || a.dia
                    , \'yyyy-mm-dd\') as aniversario
            from anivA a
        )
        select * 
        from anivB 
        where aniversario between :data::date and :data::date + \'15 days\'::interval
        order by aniversario
        ';

        $params['data'] = Carbon::now()->toDateString();

        $result = DB::select($sql, $params);
        return $result;

    } 
}
