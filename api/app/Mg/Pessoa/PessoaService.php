<?php

namespace Mg\Pessoa;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mg\Cidade\Cidade;
use Mg\Cidade\CidadeService;
use Mg\Cidade\Estado;
use Mg\Filial\Filial;
use Mg\Usuario\Autorizador;
use Mg\NFePHP\NFePHPService;
use Mg\Pix\Pix;

class PessoaService
{
    public const NOTAFISCAL_TRATAMENTOPADRAO = 0;
    public const NOTAFISCAL_SEMPRE = 1;
    public const NOTAFISCAL_SOMENTE_FECHAMENTO = 2;
    public const NOTAFISCAL_NUNCA = 9;
    public const CONSUMIDOR = 1;

    public static function autocomplete(array $params)
    {
        $qry = Pessoa::query();
        $qry->select('codpessoa', 'pessoa', 'fantasia', 'cnpj', 'inativo', 'fisica', 'ie');
        if (!empty($params['codpessoa'])) {
            $qry->where('codpessoa', $params['codpessoa']);
        } elseif (isset($params['pessoa'])) {
            $nome = $params['pessoa'];
            $qry->where(function ($q) use ($nome) {
                $q->palavras('pessoa', $nome);
            });
            $qry->orWhere(function ($q) use ($nome) {
                $q->palavras('fantasia', $nome);
            });
            // Digitou só letras e dígitos terminando em 2 dígitos: pode ser CPF ou CNPJ, que
            // pode ter letras (TASK-283)
            $doc = documentoLimpo($nome);
            if ($doc == strtoupper($nome) && preg_match('/^[0-9A-Z]{0,12}[0-9]{2}$/', $doc)) {
                $qry->orWhereIn('cnpj', static::cnpjCpfCandidatos($doc));
            }
        }
        $qry->orderBy('fantasia', 'asc');
        return $qry->limit(100)->get();
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
        $codgrupocliente,
        $fisica = null
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
            $cnpj = documentoLimpo($cnpj);
            $where[] = 'p.cnpj ilike :cnpj';
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

        if ($fisica !== null && is_string($fisica)) {
            $fisica = strtolower($fisica) === 'true' ? true : (strtolower($fisica) === 'false' ? false : null);
        }

        if ($fisica !== null) {
            $where[] = 'p.fisica = :fisica';
            $params['fisica'] = $fisica;
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

    // Formatos em que o documento pode estar gravado no tblpessoa.cnpj (TASK-283). Só dígitos e
    // cabe em 11: pode ser CPF ou CNPJ que perdeu os zeros à esquerda, procura os dois, como a
    // coluna numeric fazia.
    public static function cnpjCpfCandidatos($documento): array
    {
        $documento = documentoLimpo($documento);
        $docs = [cnpjLimpo($documento)];
        if (ctype_digit($documento) && strlen($documento) <= 11) {
            $docs[] = cpfLimpo($documento);
        }
        return $docs;
    }

    // $cnpj é CNPJ ou CPF, texto ou número, com ou sem pontuação (vem também do XML da NF-e)
    public static function buscarPorCnpjIe($cnpj, $ie)
    {
        if (documentoLimpo($cnpj) === '') {
            $qry = Pessoa::whereNull('cnpj');
        } else {
            $qry = Pessoa::whereIn('cnpj', static::cnpjCpfCandidatos($cnpj));
        }
        $ie = (int) numeroLimpo($ie);
        if (!empty($ie)) {
            $qry = $qry->where(DB::raw("cast(regexp_replace(ie, '[^0-9]+', '', 'g') as numeric)"), $ie);
        } else {
            $qry = $qry->whereNull('ie');
        }
        return $qry->first();
    }

    public static function buscaCodGrupoEconomicoPelaRaizCnpj($cnpj): ?int
    {
        $raiz = substr(cnpjLimpo($cnpj) ?? '', 0, 8);
        if ($raiz === '') {
            return null;
        }
        $sql = "
            select codgrupoeconomico
            from tblpessoa
            where cnpj like :raiz
            and fisica = false
            and codgrupoeconomico is not null
            order by alteracao desc
            limit 1
        ";
        if ($ret = DB::selectOne($sql, ['raiz' => "{$raiz}%"])) {
            return $ret->codgrupoeconomico;
        }
        return null;
    }

    public static function buscaCodGrupoEconomicoPeloCpf($cpf): ?int
    {
        $sql = '
            select codgrupoeconomico
            from tblpessoa
            where cnpj = :cpf
            and codgrupoeconomico is not null
            order by alteracao desc
            limit 1
        ';
        if ($ret = DB::selectOne($sql, ['cpf' => cpfLimpo($cpf)])) {
            return $ret->codgrupoeconomico;
        }
        return null;
    }

    public static function podeVenderAPrazo(Pessoa $pessoa, $valorAvaliar = 0): bool
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

        $params['cnpj'] = cnpjCpfLimpo($cnpj);
        $params['ie'] = $ie;

        $result = DB::select($sql, $params);

        return $result;
    }

    public static function createPelaSefazReceitaWs($data): Pessoa
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
            $ends = isset($data['sefaz']['ender']['xLgr']) ? [$data['sefaz']['ender']] : $data['sefaz']['ender'];
            foreach ($ends as $end) {
                $cidade = CidadeService::buscaPeloNomeUf($end['xMun'], $data['sefaz']['UF']);
                if ($cidade) {
                    PessoaEnderecoService::create([
                        'codpessoa' => $pessoa->codpessoa,
                        'cep' => numeroLimpo($end['CEP'] ?? '00000000'),
                        'endereco' => substr($end['xLgr'] ?? 'Nao Informado', 0, 60),
                        'numero' => substr($end['nro'] ?? 'S/N', 0, 10),
                        'complemento' => substr($end['xCpl'] ?? '', 0, 50),
                        'bairro' => substr($end['xBairro'] ?? 'Nao Informado', 0, 50),
                        'codcidade' => $cidade->codcidade,
                    ]);
                }
            }
        }

        if (!empty($data['receitaWs'])) {
            $rec = $data['receitaWs'];
            if (isset($rec['abertura'])) {
                $pessoa->nascimento = Carbon::createFromFormat('d/m/Y', $rec['abertura']);
                $pessoa->save();
            }
            if (isset($rec['municipio'])) {
                $cidade = CidadeService::buscaPeloNomeUf($rec['municipio'], $rec['uf']);
                if ($cidade) {
                    PessoaEnderecoService::create([
                        'codpessoa' => $pessoa->codpessoa,
                        'cep' => numeroLimpo($rec['cep'] ?? '00000000'),
                        'endereco' => substr($rec['logradouro'] ?? 'Nao Informado', 0, 60),
                        'numero' => substr($rec['numero'] ?? 'S/N', 0, 10),
                        'complemento' => substr($rec['complemento'] ?? '', 0, 50),
                        'bairro' => substr($rec['bairro'] ?? 'Nao Informado', 0, 50),
                        'codcidade' => $cidade->codcidade,
                    ]);
                }
            }
            if (isset($rec['email'])) {
                PessoaEmailService::createOrUpdate([
                    'codpessoa' => $pessoa->codpessoa,
                    'email' => $rec['email'],
                ]);
            }
            if (isset($rec['telefone'])) {
                $tels = explode('/', $rec['telefone']);
                foreach ($tels as $tel) {
                    $tel = (int) numeroLimpo($tel);
                    if (empty($tel)) {
                        continue;
                    }
                    PessoaTelefoneService::createOrUpdate([
                        'codpessoa' => $pessoa->codpessoa,
                        'ddd' => substr($tel, 0, 2),
                        'telefone' => substr($tel, 2),
                    ]);
                }
            }
        }
        return $pessoa;
    }

    public static function create(array $data): Pessoa
    {
        $pessoa = new Pessoa($data);
        $pessoa->save();
        return $pessoa->refresh();
    }

    public static function update(Pessoa $pessoa, array $data): Pessoa
    {
        // Remove campos sensíveis se o usuário não é Recursos Humanos
        if (!Autorizador::pode(['Recursos Humanos'])) {
            unset($data['rg']);
            unset($data['pispasep']);
            unset($data['ctps']);
            unset($data['seriectps']);
            unset($data['emissaoctps']);
            unset($data['codestadoctps']);
            unset($data['tituloeleitor']);
            unset($data['titulozona']);
            unset($data['titulosecao']);
        }

        // Remove campos de crédito/cliente se o usuário não é Financeiro
        if (!Autorizador::pode(['Financeiro', 'Recursos Humanos'])) {
            unset($data['credito']);
            unset($data['creditobloqueado']);
            unset($data['toleranciaatraso']);
            unset($data['consumidor']);
            unset($data['codgrupocliente']);
            unset($data['desconto']);
            unset($data['vendedor']);
            unset($data['notafiscal']);
            unset($data['fornecedor']);
            unset($data['cliente']);
            unset($data['codformapagamento']);
        }

        if (!empty($data['creditobloqueado'])) {
            $creditobloqueado = filter_var($data['creditobloqueado'], FILTER_VALIDATE_BOOLEAN);
            $consumidor = filter_var($data['consumidor'] ?? false, FILTER_VALIDATE_BOOLEAN);
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
            $pessoa->fill($data);
            $pessoa->fisica = filter_var($data['fisica'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $pessoa->cliente = filter_var($data['cliente'], FILTER_VALIDATE_BOOLEAN);
            $pessoa->fornecedor = filter_var($data['fornecedor'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $pessoa->vendedor = filter_var($data['vendedor'] ?? false, FILTER_VALIDATE_BOOLEAN);
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
        if (empty($data['email'])) {
            $data['email'] = config('mail.destinatarios.nfe');
        }

        if ($endereco = PessoaEndereco::where('codpessoa', $pessoa->codpessoa)
            ->whereNull('inativo')->orderBy('nfe', 'desc')->orderBy('ordem')->first()
        ) {
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
            $data['complemento'] = null;
            $data['bairro'] = 'Nao Informado';
            $data['codcidade'] = $pessoa->codcidade;
            $data['cep'] = '78550000';
            $data['enderecocobranca'] = 'Nao Informado';
            $data['numerocobranca'] = 'S/N';
            $data['complementocobranca'] = null;
            $data['bairrocobranca'] = 'Nao Informado';
            $data['codcidadecobranca'] = $pessoa->codcidade;
            $data['cepcobranca'] = '78550000';
        }

        return PessoaService::update($pessoa, $data);
    }

    public static function buscarNomePeloPixCpf($cpf)
    {
        return Pix::select(['nome'])->where('cpf', $cpf)->orderBy('criacao', 'desc')->first();
    }

    public static function buscarNomePeloPixCnpj($cnpj)
    {
        return Pix::select(['nome'])->where('cnpj', cnpjLimpo($cnpj))->orderBy('criacao', 'desc')->first();
    }

    public static function buscarReceitaWs($cnpj)
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer' . config('services.receitaws.token'),
        ])->get('https://receitaws.com.br/v1/cnpj/' . cnpjLimpo($cnpj));
    }

    public static function verificaIeSefaz($codfilial, $uf, $cnpj, $cpf, $ie)
    {

        $retReceita = null;

        // Se veio um CNPJ, consulta a receita com esse CNPJ
        if (!empty($cnpj)) {
            $cnpj = cnpjLimpo($cnpj);
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

        if (!empty($cpf)) {
            $pix = static::buscarNomePeloPixCpf($cpf);
        } elseif (!empty($cnpj)) {
            $pix = static::buscarNomePeloPixCnpj($cnpj);
        }

        return [
            'retReceita' => $resultReceita,
            'retSefaz' => $retIes,
            'retPix' => $pix
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
                p.fantasia,
                p.fisica,
                p.cliente,
                p.fornecedor,
                p.codpessoa,
                p.nascimento as data,
                exists(select 1 from tblcolaborador c where c.codpessoa = p.codpessoa and c.rescisao is null) as colaborador
            from tblpessoa p
            where p.nascimento is not null
            and p.inativo is null
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
                    p.fantasia,
                    p.fisica,
                    p.cliente,
                    p.fornecedor,
                    c.codpessoa,
                    c.contratacao as data,
                    true as colaborador
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
        where aniversario between :data::date - \'2 days\'::interval and :data::date + \'15 days\'::interval
        order by aniversario
        ';

        $params['data'] = Carbon::now()->toDateString();

        $result = DB::select($sql, $params);
        return $result;
    }

    public static function aniversariosColaboradoresIndividual()
    {

        $sql = '
        with aniversarios as (
            select
                date_part(\'month\', p.nascimento) as mes,
                date_part(\'day\', p.nascimento) as dia,
                date_part(\'year\', now()) - date_part(\'year\', p.nascimento) as idade,
                \'Idade\' as tipo,
                p.pessoa,
                p.fantasia,
                p.fisica,
                p.codpessoa,
                p.nascimento as data
            from tblpessoa p
            where p.nascimento is not null
         and p.codpessoa in ( --filtro colaborador
                select c.codpessoa
                from tblcolaborador c
                where c.rescisao is null
                )
                union all
                select
                    date_part(\'month\', c.contratacao) as mes,
                    date_part(\'day\', c.contratacao) as dia,
                    date_part(\'year\', now()) - date_part(\'year\', c.contratacao) as idade,
                    \'Empresa\' as tipo,
                    p.pessoa,
                    p.fantasia,
                    p.fisica,
                    c.codpessoa,
                    c.contratacao as data
                from tblcolaborador c
                inner join tblpessoa p on (p.codpessoa = c.codpessoa)
                where c.rescisao is null
                and date_part(\'year\', c.contratacao) < date_part(\'year\', CURRENT_DATE) -- ate aqui somente se Todos ou Colaborador
                )
        select *
        from aniversarios a
        where date_part(\'month\', a.data) = date_part(\'month\', current_date)
        and date_part(\'day\', a.data) = date_part(\'day\', current_date)
        ';


        return DB::select($sql);
    }


    public static function enviaEmailIndividual()
    {
        $anivs = static::aniversariosColaboradoresIndividual();
        if (sizeof($anivs) == 0) {
            return true;
        }
        foreach ($anivs as $aniv) {
            $emails = PessoaEmail::select('email')->where('codpessoa', $aniv->codpessoa)->get()->pluck('email');
            Mail::to($emails)->queue(new EmailAniversarioIndividual($aniv));
        }
        return true;
    }


    public static function enviaEmailGeral()
    {
        $email = config('mail.destinatarios.aniversariantes');
        if (!$email) {
            throw new Exception("MAIL_ANIVERSARIANTES não está definido no .env!", 1);
        }
        $emails = explode(',', $email);
        $anivs = static::aniversariosColaboradores();
        $email = Mail::to($emails)->queue(new EmailAniversarioGeral($anivs));
        return true;
    }
}
