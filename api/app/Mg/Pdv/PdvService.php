<?php

namespace Mg\Pdv;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Setor;
use Mg\Portador\Portador;
use Mg\PagarMe\PagarMePos;
use Mg\Saurus\SaurusPdv;
use Mg\Saurus\SaurusPinPad;
use Mg\Maquineta\MaquinetaService;
use Mg\Estoque\EstoqueLocal;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Ocorrencia\OcorrenciaService;
use Mg\Pagamento\PagamentoService;

class PdvService
{
    // o que so' Administrador ou Gerente da filial altera (TASK-46). A filial nao vem da tela:
    // e' a do local de estoque
    const CAMPOS_CADASTRO = [
        'apelido',
        'codsetor',
        'codportador',
        'monitoramento',
        'minutosesquecido',
        'observacoes',
    ];

    // o que o proprio PDV tambem altera: os padroes dos negocios dele
    const CAMPOS_CONFIGURACAO = [
        'codestoquelocal',
        'codnaturezaoperacao',
        'impressora',
        'codmaquineta',
        'codportadorpix',
    ];

    // sem estes o dispositivo nao e' ativado
    const OBRIGATORIOS_ATIVAR = [
        'apelido' => 'Apelido',
        'codfilial' => 'Filial',
        'codestoquelocal' => 'Local de Estoque',
        'codsetor' => 'Setor',
        'codnaturezaoperacao' => 'Natureza de Operação',
    ];

    // Cadastrar (TASK-46): o navegador vira um dispositivo, que nasce inativo; ativar e' o que o
    // autoriza. O cadastro quem preenche e' o Administrador/Gerente, na pagina dele.
    // Clicar duas vezes devolve o mesmo dispositivo.
    public static function cadastrar(
        $uuid,
        $ip,
        $latitude,
        $longitude,
        $precisao,
        $desktop,
        $navegador,
        $versaonavegador,
        $plataforma
    ) {
        $pdv = Pdv::where('uuid', $uuid)->first();
        if ($pdv) {
            return $pdv;
        }
        $pdv = Pdv::create([
            'uuid' => $uuid,
            'inativo' => Carbon::now(),
            'desktop' => $desktop,
            'navegador' => $navegador,
            'versaonavegador' => $versaonavegador,
            'plataforma' => $plataforma,
            'codsetor' => Setor::whereNull('inativo')->first()->codsetor,
        ]);
        static::registrarLocalizacao($pdv, $ip, $latitude, $longitude, $precisao);
        // fresh(): o minutosesquecido vem do default da tabela
        return $pdv->fresh();
    }

    // Sincronizacao: atualiza o que o navegador sabe de si; nao cadastra (isso e' o cadastrar)
    public static function dispositivo(
        $uuid,
        $ip,
        $latitude,
        $longitude,
        $precisao,
        $desktop,
        $navegador,
        $versaonavegador,
        $plataforma,
        $legado = null
    ) {
        $pdv = Pdv::where('uuid', $uuid)->first();
        if (!$pdv) {
            abort(404, 'Dispositivo não cadastrado! Abra o Meu Dispositivo e clique em Cadastrar.');
        }
        $pdv->desktop = $desktop;
        $pdv->navegador = $navegador;
        $pdv->versaonavegador = $versaonavegador;
        $pdv->plataforma = $plataforma;

        if (!empty($legado)) {
            static::migrarConfiguracao($pdv, $legado);
        }

        $pdv->save();
        static::registrarLocalizacao($pdv, $ip, $latitude, $longitude, $precisao);
        return $pdv;
    }

    // Historico (TASK-46): uma linha por periodo no mesmo lugar. Com o mesmo IP e a mesma posicao
    // da ultima linha, so' conta mais uma sincronizacao nela (a alteracao vira a da ultima);
    // mudou qualquer um, linha nova
    public static function registrarLocalizacao(Pdv $pdv, $ip, $latitude, $longitude, $precisao)
    {
        $latitude = is_null($latitude) ? null : (float) $latitude;
        $longitude = is_null($longitude) ? null : (float) $longitude;
        $ultima = $pdv->UltimaLocalizacao()->first();
        if (
            $ultima
            && $ultima->ip === $ip
            && $ultima->latitude === $latitude
            && $ultima->longitude === $longitude
        ) {
            $ultima->sincronizacoes++;
            $ultima->save();
            return $ultima;
        }
        return PdvLocalizacao::create([
            'codpdv' => $pdv->codpdv,
            'ip' => $ip,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'precisao' => $precisao,
        ]);
    }

    // O PDV avisa no fim da sincronizacao a data da completa: a mais antiga entre as de cada
    // cadastro baixado (pessoas, produtos, pranchetas...), a mesma que o botao Sincronizar mostra
    public static function sincronizacaoCompleta(Pdv $pdv, $completa)
    {
        $pdv->sincronizacaocompleta = $completa;
        $pdv->save();
        return $pdv;
    }

    // Uma vez por PDV (TASK-46): a configuracao que estava so' no navegador
    // preenche as colunas ainda vazias; o que ja esta na tabela prevalece.
    public static function migrarConfiguracao(Pdv $pdv, array $legado)
    {
        if (empty($pdv->codestoquelocal) && !empty($legado['codestoquelocal'])) {
            $estoque = EstoqueLocal::find($legado['codestoquelocal']);
            $pdv->codestoquelocal = $estoque?->codestoquelocal;
            // sem filial ainda, fica a do local de estoque
            if (empty($pdv->codfilial)) {
                $pdv->codfilial = $estoque?->codfilial;
            }
        }
        if (empty($pdv->codnaturezaoperacao) && !empty($legado['codnaturezaoperacao'])) {
            $pdv->codnaturezaoperacao = NaturezaOperacao::find($legado['codnaturezaoperacao'])?->codnaturezaoperacao;
        }
        if (empty($pdv->impressora) && !empty($legado['impressora'])) {
            $pdv->impressora = substr($legado['impressora'], 0, 100);
        }
        if (empty($pdv->codportadorpix) && !empty($legado['codportador'])) {
            $pdv->codportadorpix = Portador::find($legado['codportador'])?->codportador;
        }
        if (empty($pdv->codmaquineta)) {
            $pdv->codmaquineta = static::maquinetaLegado($legado);
        }
    }

    // o navegador guardava o POS PagarMe ou o PDV Saurus; a maquineta e' o
    // cadastro unificado. Saurus com mais de um pinpad no mesmo PDV fica sem.
    public static function maquinetaLegado(array $legado)
    {
        $maquineta = $legado['maquineta'] ?? null;
        if ($maquineta === 'pagarme' && !empty($legado['codpagarmepos'])) {
            $regs = DB::select('
                select codmaquineta from tblmaquineta
                where codpagarmepos = :codpagarmepos and inativo is null
            ', ['codpagarmepos' => $legado['codpagarmepos']]);
        } elseif ($maquineta === 'saurus' && !empty($legado['codsauruspos'])) {
            $regs = DB::select('
                select m.codmaquineta from tblmaquineta m
                inner join tblsauruspinpad pin on (pin.codsauruspinpad = m.codsauruspinpad)
                where pin.codsauruspdv = :codsauruspdv and m.inativo is null
            ', ['codsauruspdv' => $legado['codsauruspos']]);
        } else {
            return null;
        }
        return count($regs) === 1 ? $regs[0]->codmaquineta : null;
    }

    // os 20 registros mais recentes feitos no dispositivo, para a pagina dele. Rapido por causa
    // dos indices (codpdv, data) do pdv_configuracao.sql: so' com o de codpdv o Postgres desce o
    // indice da data inteiro filtrando o PDV
    public static function registros(int $codpdv)
    {
        $negocios = DB::select('
            select
                n.codnegocio, n.lancamento, n.valortotal, n.codnegociostatus,
                ns.negociostatus, nat.naturezaoperacao, p.fantasia, u.usuario
            from tblnegocio n
            inner join tblnegociostatus ns on (ns.codnegociostatus = n.codnegociostatus)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            left join tblpessoa p on (p.codpessoa = n.codpessoa)
            left join tblusuario u on (u.codusuario = n.codusuario)
            where n.codpdv = :codpdv
            order by n.lancamento desc
            limit 20
        ', ['codpdv' => $codpdv]);

        $pagamentos = DB::select('
            select
                pg.codpagamento, pg.transacao, pg.meio, pg.estado, pg.total, pg.valortroco,
                pg.parcelas, pg.codpixcob, pg.codnegocio, p.fantasia
            from tblpagamento pg
            left join tblpessoa p on (p.codpessoa = pg.codpessoa)
            where pg.codpdv = :codpdv
            order by pg.transacao desc
            limit 20
        ', ['codpdv' => $codpdv]);
        foreach ($pagamentos as $pag) {
            $pag->estadodescricao = PagamentoService::ESTADOS[$pag->estado] ?? $pag->estado;
        }

        $ocorrencias = DB::select('
            select
                o.codocorrencia, o.criacao, o.tipo, o.descricao, o.valor, o.codnegocio,
                o.conferencia
            from tblocorrencia o
            where o.codpdv = :codpdv
            order by o.criacao desc
            limit 20
        ', ['codpdv' => $codpdv]);
        foreach ($ocorrencias as $oc) {
            $oc->tipodescricao = OcorrenciaService::TIPOS[$oc->tipo] ?? $oc->tipo;
        }

        $localizacoes = DB::select('
            select
                l.codpdvlocalizacao, l.criacao, l.alteracao, l.sincronizacoes, host(l.ip) as ip,
                l.latitude, l.longitude, l.precisao
            from tblpdvlocalizacao l
            where l.codpdv = :codpdv
            order by l.codpdvlocalizacao desc
            limit 20
        ', ['codpdv' => $codpdv]);

        return [
            'negocios' => $negocios,
            'pagamentos' => $pagamentos,
            'ocorrencias' => $ocorrencias,
            'localizacoes' => $localizacoes,
        ];
    }

    // dispositivo ativo (inativo vazio) com este uuid; com uuid repetido, vale o ativo
    public static function podeAcessar($uuid)
    {
        $pdv = Pdv::where('uuid', $uuid)->whereNull('inativo')->first();
        return $pdv ?: false;
    }

    public static function autoriza($uuid)
    {
        if (!$pdv = static::podeAcessar($uuid)) {
            abort(403, 'Dispositivo não cadastrado ou inativo! Abra o Meu Dispositivo.');
        }
        return $pdv;
    }

    public static function exigirParaAtivar(Pdv $pdv)
    {
        $faltam = [];
        foreach (static::OBRIGATORIOS_ATIVAR as $campo => $nome) {
            if (blank($pdv->$campo)) {
                $faltam[] = $nome;
            }
        }
        if (!empty($faltam)) {
            abort(422, 'Para ativar o dispositivo, preencha: ' . implode(', ', $faltam) . '.');
        }
    }

    // ativar e' autorizar: o dispositivo passa a vender e sincronizar (TASK-46)
    public static function ativar(Pdv $pdv)
    {
        static::exigirParaAtivar($pdv);
        $pdv->update(['inativo' => null]);
        return $pdv;
    }

    public static function inativar(Pdv $pdv)
    {
        $pdv->update(['inativo' => Carbon::now()]);
        return $pdv;
    }

    public static function produtoCount()
    {
        $sql = '
            select
            	count(pb.codprodutobarra) as count
            from tblprodutobarra pb 
        ';
        $regs = DB::select($sql);
        return $regs[0];
    }

    public static function produto($codprodutobarra, $limite)
    {
        $sincronizado = date('Y-m-d H:i:s');
        $sql = '
            select
            	pb.codprodutobarra,
                p.codproduto,
            	pb.barras,
                p.produto,
                pv.variacao,
                p.abc,
            	coalesce(ume.sigla, um.sigla) as sigla,
            	pe.quantidade,
            	pri.codimagem,
            	coalesce(pe.preco, p.preco * coalesce(pe.quantidade, 1)) as preco,
            	coalesce(pv.inativo, p.inativo) as inativo,
                :sincronizado as sincronizado
            from tblproduto p
            inner join tblunidademedida um on (um.codunidademedida = p.codunidademedida)
            inner join tblprodutovariacao pv  on (pv.codproduto = p.codproduto)
            inner join tblprodutobarra pb on (pb.codprodutovariacao = pv.codprodutovariacao)
            left join tblprodutoembalagem pe on (pe.codprodutoembalagem = pb.codprodutoembalagem)
            left join tblunidademedida ume on (ume.codunidademedida = pe.codunidademedida)
            left join tblprodutoimagem pri on (pri.codprodutoimagem = pv.codprodutoimagem)
        ';
        if ($limite == 1) {
            $sql .= '
                where pb.codprodutobarra = :codprodutobarra
                order by pb.codprodutobarra
                limit :limite
            ';
        } else {
            $sql .= '
                where pb.codprodutobarra > :codprodutobarra
                order by pb.codprodutobarra
                limit :limite
            ';
        }
        $regs = DB::select($sql, [
            'codprodutobarra' => $codprodutobarra,
            'limite' => $limite,
            'sincronizado' => $sincronizado
        ]);
        return array_map(function ($item) {
            if ($item->quantidade) {
                $item->quantidade = floatval($item->quantidade);
            }
            $item->preco = floatval($item->preco);
            $item->produto = static::montarDescricaoProduto($item->produto, $item->variacao, $item->sigla, $item->quantidade);
            $item->busca =
                $item->produto . ' ' .
                number_format($item->preco, 2, ',', '')  . ' ' .
                $item->barras  . ' ' .
                substr($item->barras, -6, 6);
            $item->buscaArr = array_values(array_unique(explode(' ', $item->busca)));
            return $item;
        }, $regs);
        return $regs;
    }

    public static function montarDescricaoProduto($produto, $variacao, $unidade, $quantidade)
    {
        $descricao = $produto;
        if (!empty($variacao)) {
            $descricao .= ' ' . $variacao;
        }
        $descricao .= ' ' . $unidade;
        if (!empty($quantidade)) {
            $descricao .= ' C/' . intval($quantidade);
        }
        return $descricao;
    }

    public static function pessoaCount()
    {
        $sql = '
            select
            	count(p.codpessoa) as count
            from tblpessoa p
        ';
        $regs = DB::select($sql);
        return $regs[0];
    }

    public static function pessoa($codpessoa, $cnpj, $limite)
    {
        $sincronizado = date('Y-m-d H:i:s');
        $params = [
            'sincronizado' => $sincronizado,
            'limite' => $limite,
        ];
        $sql = '
            select 
                p.codpessoa,
                p.pessoa,
                p.fantasia,
                case when p.fisica then to_char(p.cnpj, \'FM00000000000\') else to_char(p.cnpj, \'FM00000000000000\') end as cnpj,
                p.ie,
                p.fisica,
                p.endereco,
                p.numero,
                p.bairro,
                p.complemento,
                c.cidade,
                e.sigla as uf,
                p.vendedor,
                p.inativo,
                p.mensagemvenda,
                p.codformapagamento,
                p.desconto,
                :sincronizado as sincronizado
            from tblpessoa p
            left join tblcidade c on (c.codcidade = p.codcidade)
            left join tblestado e on (e.codestado = c.codestado)
        ';
        if (!empty($cnpj)) {
            $params['cnpj'] = "%{$cnpj}%";
            $sql .= '
                where to_char(cnpj, \'FM00000000000000\') ilike :cnpj
            ';
        } else {
            $params['codpessoa'] = $codpessoa;
            if ($limite == 1) {
                $sql .= '
                    where codpessoa = :codpessoa
                ';
            } else {
                $sql .= '
                    where codpessoa > :codpessoa
                ';
            }
        }
        $sql .= '
            order by codpessoa
            limit :limite
        ';
        $regs = DB::select($sql, $params);
        $regs = array_map(function ($item) {
            $busca = "{$item->pessoa} . {$item->fantasia} . {$item->cnpj} " . str_pad($item->codpessoa, 8, "0", STR_PAD_LEFT);
            $busca = trim(preg_replace('/[^A-Za-z0-9 ]/', '', $busca));
            $busca = preg_replace('/\s+/', ' ', $busca);
            $item->busca = $busca;
            $item->buscaArr = array_values(array_unique(explode(' ', $item->busca)));
            return $item;
        }, $regs);
        return $regs;
    }

    public static function naturezaOperacao()
    {
        $sincronizado = date('Y-m-d H:i:s');
        $sql = '
            select 
                nat.codnaturezaoperacao, 
                nat.naturezaoperacao, 
                nat.codoperacao, 
                nat.estoque, 
                nat.compra, 
                nat.venda, 
                nat.vendadevolucao, 
                nat.transferencia,
                nat.financeiro,
                nat.codnaturezaoperacaodevolucao,
                nat.preco,
                nat.emitida,
                :sincronizado as sincronizado
            from tblnaturezaoperacao nat 
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);
        return $regs;
    }

    public static function estoqueLocal()
    {
        $sincronizado = date('Y-m-d H:i:s');
        $sql = '
            select 
                t.codestoquelocal,
                t.estoquelocal,
                t.deposito,
                t.inativo,
                t.sigla,
                f.codfilial,
                f.filial,
                f.codpessoa,
                f.codempresa,
                p.fantasia,
                p.pessoa,
                p.cnpj,
                p.telefone1 as telefone,
                p.endereco,
                p.numero,
                p.complemento,
                p.bairro,
                c.cidade,
                e.sigla as uf,
                :sincronizado as sincronizado
            from tblestoquelocal t 
            inner join tblfilial f on (f.codfilial = t.codfilial)
            inner join tblpessoa p on (p.codpessoa = f.codpessoa)
            inner join tblcidade c on (c.codcidade = p.codcidade)
            inner join tblestado e on (e.codestado = c.codestado)
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);
        foreach ($regs as $reg) {
            // maquinetas ativas da filial + compartilhadas (cartão integrado e manual no Receber)
            $reg->MaquinetaS = MaquinetaService::paraPdv($reg->codfilial);
            $reg->PagarMePosS = PagarMePos::select(['codpagarmepos', 'serial', 'apelido'])->where('codfilial', $reg->codfilial)->whereNull('inativo')->get();
            // um registro por pinpad; serial é o número de série físico (null até o 1º uso no PDV)
            $reg->SaurusPosS = SaurusPinPad::select(['tblsauruspinpad.codsauruspinpad', 'tblsauruspinpad.serial', 'tblsauruspdv.codsauruspdv', 'tblsauruspdv.apelido'])
                ->join('tblsauruspdv', 'tblsauruspdv.codsauruspdv', '=', 'tblsauruspinpad.codsauruspdv')
                ->where('tblsauruspdv.codfilial', $reg->codfilial)
                ->whereNull('tblsauruspdv.inativo')
                ->whereNull('tblsauruspinpad.inativo')
                ->orderBy('tblsauruspdv.apelido')
                ->get();
        }
        return $regs;
    }

    public static function formaPagamento()
    {
        $sincronizado = date('Y-m-d H:i:s');
        $sql = '
            select 
                fp.codformapagamento,
                fp.formapagamento,
                fp.boleto,
                fp.fechamento,
                fp.notafiscal,
                fp.parcelas,
                fp.diasentreparcelas,
                fp.avista,
                fp.lio,
                fp.pix,
                fp.pagarme,
                fp.integracao,
                :sincronizado as sincronizado
            from tblformapagamento fp
            where fp.inativo is null
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);
        return $regs;
    }

    /**
     * Catalogo de modelos de vale compras para o cache offline do PDV.
     *
     * Espelha formaPagamento(): consulta crua, carimbo "sincronizado" em
     * todas as linhas e so' o que esta' ativo. Os itens de cada modelo vem
     * dentro dele, e de proposito SO' com codprodutobarra / quantidade /
     * valorunitario: descricao, barras e imagem o PDV ja' tem no cache de
     * produtos, e repeti-las aqui inflaria a carga (204 modelos x ~21 itens)
     * sem acrescentar nada.
     *
     * O catalogo e' sazonal: fora da temporada ele pode voltar VAZIO, e
     * quem consome precisa aguentar isso (ver sincronizarValeModelo no
     * front).
     */
    public static function valeModelo()
    {
        $sincronizado = date('Y-m-d H:i:s');
        $sql = '
            select
                vm.codvalemodelo,
                vm.modelo,
                vm.codpessoafavorecido,
                p.fantasia as favorecido,
                vm.valorprodutos,
                vm.valoravulso,
                vm.valorvale,
                vm.observacoes,
                :sincronizado as sincronizado
            from tblvalemodelo vm
            left join tblpessoa p on (p.codpessoa = vm.codpessoafavorecido)
            where vm.inativo is null
            order by vm.modelo
            ';
        $regs = DB::select($sql, [
            'sincronizado' => $sincronizado
        ]);

        // Uma consulta so' para os itens de todos os modelos (em vez de uma
        // por modelo), agrupada em memoria.
        $itens = DB::select('
            select
                vmpb.codvalemodelo,
                vmpb.codprodutobarra,
                vmpb.quantidade,
                vmpb.valorunitario
            from tblvalemodeloprodutobarra vmpb
            order by vmpb.codvalemodeloprodutobarra
        ');
        $porModelo = [];
        foreach ($itens as $item) {
            $porModelo[$item->codvalemodelo][] = $item;
        }
        foreach ($regs as $reg) {
            $reg->itens = $porModelo[$reg->codvalemodelo] ?? [];
        }

        return $regs;
    }

    public static function impressora()
    {
        $printers = json_decode(file_get_contents(base_path('printers.json')), true);
        $ret = [];
        $sincronizado = date('Y-m-d H:i:s');
        $codimpressora = 0;
        foreach ($printers as $impressora => $nome) {
            $codimpressora++;
            $ret[] = [
                'codimpressora' => $codimpressora,
                'impressora' => $impressora,
                'nome' => $nome,
                'sincronizado' => $sincronizado,
            ];
        }
        return $ret;
    }

    // grava so' os campos que vieram: quem nao altera o cadastro nem manda (o controller recusa)
    public static function update(Pdv $pdv, array $data)
    {
        $data = array_intersect_key(
            $data,
            array_flip(array_merge(static::CAMPOS_CADASTRO, static::CAMPOS_CONFIGURACAO))
        );
        foreach (['monitoramento', 'minutosesquecido'] as $campo) {
            if (array_key_exists($campo, $data) && $data[$campo] === '') {
                $data[$campo] = null;
            }
        }
        $pdv->fill($data);
        // a filial e' sempre a do local de estoque: nao tem como ficarem incoerentes
        if (!empty($pdv->codestoquelocal)) {
            $pdv->codfilial = EstoqueLocal::findOrFail($pdv->codestoquelocal)->codfilial;
        }
        if ($pdv->minutosesquecido === null) {
            $pdv->minutosesquecido = 120;
        }
        if ($pdv->minutosesquecido < 10) {
            abort(422, 'O tempo para considerar o negócio esquecido precisa ser de pelo menos 10 minutos!');
        }
        if (!empty($pdv->codportador)) {
            $portador = Portador::findOrFail($pdv->codportador);
            if ($portador->tipo !== Portador::TIPO_ESPECIE) {
                abort(422, "O portador {$portador->portador} não é em espécie!");
            }
            if ($portador->codfilial != $pdv->codfilial) {
                abort(422, "O portador {$portador->portador} não é da filial do PDV!");
            }
        }
        $pdv->save();
        return $pdv;
    }
}
