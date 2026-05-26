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
use Mg\GrupoEconomico\GrupoEconomicoService;
use Mg\Usuario\Autorizador;

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
            $num = preg_replace('/\D/', '', $nome);
            if ($num == $nome) {
                $qry->orWhere('cnpj', $num);
            }
        }
        $qry->orderBy('fantasia', 'asc');
        return $qry->limit(100)->get();
    }

    public static function index(
        $limit, $offset, $codpessoa, $pessoa, $cnpj, $email, $fone,
        $codgrupoeconomico, $codcidade, $inativo, $codformapagamento,
        $codgrupocliente, $fisica = null
    ) {
        $sql = '
            select p.*
            from tblpessoa p
            left join tblgrupoeconomico ge on (ge.codgrupoeconomico = p.codgrupoeconomico)
        ';

        $where = [];
        $params = [];
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
            $where[] = ' p.codpessoa in (select pt.codpessoa from tblpessoatelefone pt where cast(pt.telefone as varchar) ilike :fone)';
            $params['fone'] = "%{$fone}%";
        }
        if (!empty($email)) {
            $where[] = ' p.codpessoa in (select pe.codpessoa from tblpessoaemail pe where pe.email ilike :email)';
            $params['email'] = "%{$email}%";
        }
        if (!empty($cnpj)) {
            $cnpj = numeroLimpo($cnpj);
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

        if ($fisica !== null && is_string($fisica)) {
            $fisica = strtolower($fisica) === 'true' ? true : (strtolower($fisica) === 'false' ? false : null);
        }
        if ($fisica !== null) {
            $where[] = 'p.fisica = :fisica';
            $params['fisica'] = $fisica;
        }

        if (count($where) > 0) {
            $sql .= ' where ' . implode(' and ', $where);
        }

        $sql .= ' order by p.fantasia limit :limit offset :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $regs = DB::select($sql, $params);
        return Pessoa::hydrate($regs);
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

    public static function buscaCodGrupoEconomicoPelaRaizCnpj($cnpj): ?int
    {
        $raiz = substr(str_pad(numeroLimpo($cnpj), 14, '0'), 0, 8);
        $sql = "
            select codgrupoeconomico
            from tblpessoa
            where to_char(cnpj, 'FM00000000000000') ilike :raiz
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
        $sql = "
            select codgrupoeconomico
            from tblpessoa
            where to_char(cnpj, 'FM00000000000') ilike :cpf
            and codgrupoeconomico is not null
            order by alteracao desc
            limit 1
        ";
        if ($ret = DB::selectOne($sql, ['cpf' => $cpf])) {
            return $ret->codgrupoeconomico;
        }
        return null;
    }

    public static function podeVenderAPrazo(Pessoa $pessoa, $valorAvaliar = 0): bool
    {
        if ($valorAvaliar <= 0) {
            return true;
        }
        if ($pessoa->creditobloqueado) {
            return false;
        }
        if (!empty($pessoa->credito)) {
            $saldo = $pessoa->TituloS()->sum('saldo');
            if (($saldo + $valorAvaliar) > ($pessoa->credito * 1.05)) {
                return false;
            }
        }
        $titulo = $pessoa->TituloS()->where('saldo', '>', 0)->orderBy('vencimento', 'asc')->first();
        if ($titulo && $titulo->vencimento->isPast()) {
            if ($titulo->vencimento->diffInDays() > $pessoa->toleranciaatraso) {
                return false;
            }
        }
        return true;
    }

    public static function createPelaSefazReceitaWs(array $data): Pessoa
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
        if (!Autorizador::pode(['Recursos Humanos'])) {
            unset($data['rg'], $data['pispasep'], $data['ctps'], $data['seriectps'],
                $data['emissaoctps'], $data['codestadoctps'], $data['tituloeleitor'],
                $data['titulozona'], $data['titulosecao']);
        }

        if (!Autorizador::pode(['Financeiro', 'Recursos Humanos'])) {
            unset($data['credito'], $data['creditobloqueado'], $data['toleranciaatraso'],
                $data['consumidor'], $data['codgrupocliente'], $data['desconto'],
                $data['vendedor'], $data['notafiscal'], $data['fornecedor'],
                $data['cliente'], $data['codformapagamento']);
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

    public static function delete(Pessoa $pessoa)
    {
        return $pessoa->delete();
    }

    public static function ativar(Pessoa $pessoa): Pessoa
    {
        $pessoa->update(['inativo' => null]);
        return $pessoa->refresh();
    }

    public static function inativar(Pessoa $pessoa): Pessoa
    {
        $pessoa->update(['inativo' => Carbon::now()]);
        return $pessoa->refresh();
    }

    public static function importar($codfilial, $uf, $cnpj, $cpf, $ie)
    {
        // Versão pendente — depende de NFePHPService::sefazCadastro
        // (Mg\NFePHP ainda não foi migrado). Mantemos a versão da Receita WS.
        $retReceita = null;
        if (!empty($cnpj)) {
            $cnpj = str_pad(numeroLimpo($cnpj), 14, '0', STR_PAD_LEFT);
            $retReceita = static::buscarReceitaWs($cnpj);
            if ($retReceita->status() != 200) {
                throw new Exception($retReceita['message'] ?? 'Erro consultando Receita WS', 1);
            }
            $uf = $retReceita['uf'] ?? $uf;
        }

        $retPessoas = [];
        if (!empty($retReceita)) {
            $pessoa = static::buscarPorCnpjIe(numeroLimpo($retReceita['cnpj']), null);
            if ($pessoa == null) {
                $pessoa = new Pessoa();
                $pessoa->fantasia = substr($retReceita['fantasia'] ?? $retReceita['nome'], 0, 50);
            }

            $codgrupoeconomico = static::buscaCodGrupoEconomicoPelaRaizCnpj($retReceita['cnpj']);
            $grupoCliente = GrupoEconomicoService::buscarPeloCnpjCpf(false, $retReceita['cnpj']);
            $pessoa->codgrupoeconomico = $codgrupoeconomico;
            $pessoa->codgrupocliente = $grupoCliente?->codgrupocliente;
            $pessoa->fisica = false;
            $pessoa->cnpj = numeroLimpo($retReceita['cnpj']);
            $pessoa->pessoa = $retReceita['nome'];
            $pessoa->notafiscal = 0;

            if ($retReceita['situacao'] == 'ATIVA') {
                $pessoa->inativo = null;
            } elseif (empty($pessoa->inativo)) {
                $pessoa->inativo = Carbon::now();
            }

            $estado = Estado::firstWhere(['sigla' => $retReceita['uf']]);
            if ($estado) {
                $cidade = Cidade::where('codestado', $estado->codestado)
                    ->where('cidade', 'ilike', removeAcentos($retReceita['municipio']))
                    ->first();
                if ($cidade) {
                    $pessoa->codcidade = $cidade->codcidade;
                }
            }

            $pessoa->save();
            $retPessoas[] = $pessoa->fresh();

            PessoaEnderecoService::createOrUpdate([
                'codpessoa' => $pessoa->codpessoa,
                'endereco' => substr($retReceita['logradouro'] ?? '', 0, 60),
                'numero' => substr($retReceita['numero'] ?? '', 0, 10),
                'bairro' => substr($retReceita['bairro'] ?? '', 0, 50),
                'complemento' => substr(trim($retReceita['complemento'] ?? ''), 0, 50),
                'codcidade' => $pessoa->codcidade,
                'cep' => numeroLimpo($retReceita['cep'] ?? '00000000'),
            ]);

            if (!empty($retReceita['email'])) {
                PessoaEmailService::createOrUpdate([
                    'codpessoa' => $pessoa->codpessoa,
                    'email' => $retReceita['email'],
                ]);
            }

            if (!empty($retReceita['telefone'])) {
                foreach (explode('/', $retReceita['telefone']) as $tel) {
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

        foreach ($retPessoas as $pessoa) {
            static::atualizaCamposLegado($pessoa);
        }

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
        if (empty($data['telefone1'])) {
            $data['telefone1'] = '(66) 9999-9999';
        }

        $i = 0;
        foreach ($pessoa->PessoaEmailS()->whereNull('inativo')->orderBy('nfe', 'desc')->orderBy('ordem')->limit(3)->get() as $email) {
            $i++;
            switch ($i) {
                case 1: $data['email'] = $email->email; break;
                case 2: $data['emailnfe'] = $email->email; break;
                case 3: $data['emailcobranca'] = $email->email; break;
            }
        }
        if (empty($data['email'])) {
            $data['email'] = 'nfe@mgpapelaria.com.br';
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

        return static::update($pessoa, $data);
    }

    public static function buscarReceitaWs($cnpj)
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer' . env('RECEITA_WS_TOKEN'),
        ])->get('https://receitaws.com.br/v1/cnpj/' . $cnpj);
    }

    public static function verificaIeSefaz($codfilial, $uf, $cnpj, $cpf, $ie)
    {
        // Versão simplificada — sem consulta SEFAZ (NFePHP ainda não migrado).
        // Quando NFePHPService for portado, restaurar bloco try/catch sefazCadastro.
        $retReceita = null;
        if (!empty($cnpj)) {
            $cnpj = str_pad(numeroLimpo($cnpj), 14, '0', STR_PAD_LEFT);
            $retReceita = static::buscarReceitaWs($cnpj);
        }
        return [
            'retReceita' => $retReceita ? json_decode($retReceita) : null,
            'retSefaz' => [],
            'retPix' => null,
        ];
    }

    public static function aniversarios($tipo)
    {
        if (!$tipo) {
            $tipo = 'todos';
        }
        $sql = "
        with aniversarios as (
            select
                date_part('month', p.nascimento) as mes,
                date_part('day', p.nascimento) as dia,
                date_part('year', now()) - date_part('year', p.nascimento) as idade,
                'Idade' as tipo,
                p.pessoa, p.fantasia, p.fisica, p.cliente, p.fornecedor,
                p.codpessoa, p.nascimento as data,
                exists(select 1 from tblcolaborador c where c.codpessoa = p.codpessoa and c.rescisao is null) as colaborador
            from tblpessoa p
            where p.nascimento is not null
            and p.inativo is null
        ";
        switch ($tipo) {
            case 'cliente':
                $sql .= " and p.codpessoa not in (select c.codpessoa from tblcolaborador c where c.rescisao is null) and p.cliente )";
                break;
            case 'fornecedor':
                $sql .= ' and p.fornecedor = true )';
                break;
            case 'colaborador':
                $sql .= ' and p.codpessoa in (select c.codpessoa from tblcolaborador c where c.rescisao is null)';
                break;
        }
        switch ($tipo) {
            case 'colaborador':
            case 'todos':
                $sql .= "
                union all
                select
                    date_part('month', c.contratacao) as mes,
                    date_part('day', c.contratacao) as dia,
                    date_part('year', now()) - date_part('year', c.contratacao) as idade,
                    'Empresa' as tipo,
                    p.pessoa, p.fantasia, p.fisica, p.cliente, p.fornecedor,
                    c.codpessoa, c.contratacao as data, true as colaborador
                from tblcolaborador c
                inner join tblpessoa p on (p.codpessoa = c.codpessoa)
                where c.rescisao is null
                and date_part('year', c.contratacao) < date_part('year', CURRENT_DATE)
                )";
                break;
        }
        $sql .= ' select * from aniversarios order by mes, dia';
        return DB::select($sql);
    }

    public static function aniversariosColaboradores()
    {
        $sql = "
        with anivB as (
            with anivA as (
                select
                    date_part('month', p.nascimento) as mes,
                    date_part('day', p.nascimento) as dia,
                    date_part('year', :data::date) - date_part('year', p.nascimento) as idade,
                    'Idade' as tipo, p.pessoa, p.codpessoa, p.nascimento as data
                from tblpessoa p
                where p.nascimento is not null
                and p.codpessoa in (select c.codpessoa from tblcolaborador c where c.rescisao is null)
                union all
                select
                    date_part('month', c.contratacao) as mes,
                    date_part('day', c.contratacao) as dia,
                    date_part('year', :data::date) - date_part('year', c.contratacao) as idade,
                    'Empresa' as tipo, p.pessoa, c.codpessoa, c.contratacao as data
                from tblcolaborador c
                inner join tblpessoa p on (p.codpessoa = c.codpessoa)
                where c.rescisao is null
                and date_part('year', c.contratacao) < date_part('year', :data::date)
            )
            select *,
                to_date(
                    case when (date_part('month', :data::date) = 12) then
                        case when (a.mes = 1) then date_part('year', :data::date) + 1
                        else date_part('year', :data::date) end
                    else date_part('year', :data::date) end
                    || '-' || a.mes || '-' || a.dia
                    , 'yyyy-mm-dd') as aniversario
            from anivA a
        )
        select * from anivB
        where aniversario between :data::date - '2 days'::interval and :data::date + '15 days'::interval
        order by aniversario
        ";
        return DB::select($sql, ['data' => Carbon::now()->toDateString()]);
    }

    public static function aniversariosColaboradoresIndividual()
    {
        $sql = "
        with aniversarios as (
            select
                date_part('month', p.nascimento) as mes,
                date_part('day', p.nascimento) as dia,
                date_part('year', now()) - date_part('year', p.nascimento) as idade,
                'Idade' as tipo, p.pessoa, p.fantasia, p.fisica, p.codpessoa, p.nascimento as data
            from tblpessoa p
            where p.nascimento is not null
            and p.codpessoa in (select c.codpessoa from tblcolaborador c where c.rescisao is null)
            union all
            select
                date_part('month', c.contratacao) as mes,
                date_part('day', c.contratacao) as dia,
                date_part('year', now()) - date_part('year', c.contratacao) as idade,
                'Empresa' as tipo, p.pessoa, p.fantasia, p.fisica, c.codpessoa, c.contratacao as data
            from tblcolaborador c
            inner join tblpessoa p on (p.codpessoa = c.codpessoa)
            where c.rescisao is null
            and date_part('year', c.contratacao) < date_part('year', CURRENT_DATE)
        )
        select * from aniversarios a
        where date_part('month', a.data) = date_part('month', current_date)
        and date_part('day', a.data) = date_part('day', current_date)
        ";
        return DB::select($sql);
    }
}
