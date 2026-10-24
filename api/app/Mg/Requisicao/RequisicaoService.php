<?php

namespace Mg\Requisicao;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Estoque\EstoqueLocal;
use Mg\Estoque\EstoqueSaldoRelatorioService;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;
use Mg\Pdv\Pdv;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoBarraPesquisaResource;
use Mg\Produto\UnidadeMedidaService;
use Mg\Usuario\Autorizador;

// Requisicao entre lojas (TASK-55, doc-6): cadastro, permissoes e painel.
// O status nao se grava aqui: quem decide e' o RequisicaoProcessamentoService.
class RequisicaoService
{
    // a linha das listas (painel e listagem): as duas lojas, quem retira e quantos itens
    const SELECT_LISTA = "
        select
            r.codrequisicao, r.status, r.destino, r.atendimento, r.criacao,
            array_to_string(r.nomes, ', ') as nome,
            r.codestoquelocalrequisitante, elr.sigla as siglarequisitante,
            elr.estoquelocal as requisitante,
            r.codestoquelocalrequisitada, eld.sigla as siglarequisitada,
            eld.estoquelocal as requisitada,
            (select count(*) from tblrequisicaoitem ri
              where ri.codrequisicao = r.codrequisicao and ri.quantidade > 0) as itens
        from tblrequisicao r
        inner join tblestoquelocal elr on (elr.codestoquelocal = r.codestoquelocalrequisitante)
        left join tblestoquelocal eld on (eld.codestoquelocal = r.codestoquelocalrequisitada)
    ";

    const POR_PAGINA = 50;

    // o lado de quem pediu: o dispositivo e' de uma filial da loja requisitante (a filial do
    // dispositivo e' a do local de estoque dele, TASK-257)
    public static function ehRequisitante(Requisicao $req, ?Pdv $pdv): bool
    {
        return !empty($pdv) && $pdv->EstoqueLocal?->codfilial == $req->EstoqueLocalRequisitante->codfilial;
    }

    // o lado de quem atende
    public static function ehRequisitada(Requisicao $req, ?Pdv $pdv): bool
    {
        return !empty($pdv) && !empty($req->EstoqueLocalRequisitada)
            && $pdv->EstoqueLocal?->codfilial == $req->EstoqueLocalRequisitada->codfilial;
    }

    public static function ehGerente(): bool
    {
        return Autorizador::pode(['Gerente']);
    }

    public static function podeEditar(Requisicao $req, ?Pdv $pdv): bool
    {
        return $req->status == Requisicao::STATUS_AGUARDANDO && static::ehRequisitante($req, $pdv);
    }

    public static function podeCancelar(Requisicao $req, ?Pdv $pdv): bool
    {
        return !in_array($req->status, [Requisicao::STATUS_CANCELADA, Requisicao::STATUS_ENTREGUE])
            && (static::ehRequisitante($req, $pdv) || static::ehGerente());
    }

    // justificar os itens e finalizar com divergencia: quem atende ou Gerente (tambem
    // sem nada separado: e' assim que quem atende diz que nao tem)
    public static function podeJustificar(Requisicao $req, ?Pdv $pdv): bool
    {
        return in_array($req->status, [
            Requisicao::STATUS_AGUARDANDO,
            Requisicao::STATUS_EM_SEPARACAO,
            Requisicao::STATUS_PARCIAL,
            Requisicao::STATUS_DIVERGENCIA,
        ]) && (static::ehRequisitada($req, $pdv) || static::ehGerente());
    }

    // separar: so' a loja que atende, enquanto nao esta' finalizada
    public static function podeSeparar(Requisicao $req, ?Pdv $pdv): bool
    {
        return in_array($req->status, [
            Requisicao::STATUS_AGUARDANDO,
            Requisicao::STATUS_EM_SEPARACAO,
            Requisicao::STATUS_PARCIAL,
        ]) && static::ehRequisitada($req, $pdv);
    }

    protected static function exigirJustificar(Requisicao $req, ?Pdv $pdv): void
    {
        if (!static::podeJustificar($req, $pdv)) {
            abort(403, 'Só a loja que atende ou um Gerente justifica e finaliza, e só com a requisição pendente!');
        }
    }

    // motivo, observacao e substituto de um item (o substituto e' campo a parte)
    public static function justificarItem(RequisicaoItem $item, array $dados, ?Pdv $pdv): RequisicaoItem
    {
        static::exigirJustificar($item->Requisicao, $pdv);
        $item->motivo = $dados['motivo'] ?? null;
        $item->observacao = $dados['observacao'] ?? null;
        $item->codprodutobarrasubstituto = $dados['codprodutobarrasubstituto'] ?? null;
        $item->save();
        RequisicaoProcessamentoService::processar($item->Requisicao);
        return $item;
    }

    // produto que veio fora do pedido e nao substitui nada: entra como item com pedido 0
    // e motivo "enviado a mais" (o processo distribui as linhas dele para ca')
    public static function incluirEnviadoAMais(Requisicao $req, array $dados, ?Pdv $pdv): RequisicaoItem
    {
        static::exigirJustificar($req, $pdv);
        $item = RequisicaoItem::firstOrNew([
            'codrequisicao' => $req->codrequisicao,
            'codprodutobarra' => $dados['codprodutobarra'],
        ]);
        if (!$item->exists) {
            $item->quantidade = 0;
        }
        $item->motivo = RequisicaoItem::MOTIVO_ENVIADO_A_MAIS;
        $item->observacao = $dados['observacao'] ?? $item->observacao;
        $item->save();
        RequisicaoProcessamentoService::processar($req);
        return $item;
    }

    // Finalizar com divergencia: nada em separacao, cada item que nao bateu com motivo,
    // e cada produto fora do pedido como substituto de um item ou incluido a mais
    public static function finalizar(Requisicao $req, ?Pdv $pdv): Requisicao
    {
        static::exigirJustificar($req, $pdv);
        $calc = RequisicaoProcessamentoService::calcular([$req->codrequisicao]);
        $abertos = array_filter($calc['negocios'], fn ($n) => in_array($req->codrequisicao, $n['requisicoes'])
            && $n['codnegociostatus'] == NegocioService::STATUS_ABERTO);
        if (!empty($abertos)) {
            abort(422, 'Feche ou cancele antes o negócio ' . implode(', ', array_map(
                fn ($n) => '#' . str_pad($n['codnegocio'], 8, '0', STR_PAD_LEFT),
                $abertos
            )) . '!');
        }
        $erros = [];
        foreach ($req->RequisicaoItemS()->with('ProdutoBarra.Produto')->get() as $item) {
            $c = $calc['itens'][$item->codrequisicaoitem] ?? null;
            if ($c && abs($c['pedido'] - $c['atendido']) > RequisicaoProcessamentoService::TOLERANCIA && empty($item->motivo)) {
                $erros[] = "{$item->ProdutoBarra->Produto->produto} não bateu: informe o motivo";
            }
        }
        $substitutos = array_filter(array_column(array_filter($calc['itens'], fn ($i) => $i['codrequisicao'] == $req->codrequisicao), 'codprodutovariacaosubstituto'));
        foreach ($calc['fora'][$req->codrequisicao] ?? [] as $f) {
            if (!in_array($f['codprodutovariacao'], $substitutos)) {
                $pb = ProdutoBarra::with('Produto')->find($f['codprodutobarra']);
                $erros[] = "{$pb->Produto->produto} veio fora do pedido: marque como substituto de um item ou inclua como enviado a mais";
            }
        }
        if (!empty($erros)) {
            abort(422, implode('; ', array_unique($erros)) . '.');
        }
        $req->finalizacao = now();
        $req->codusuariofinalizacao = Auth::user()?->codusuario;
        $req->save();
        RequisicaoProcessamentoService::processar($req);
        return $req->fresh();
    }

    public static function gerarCodigoRetirada(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function criar(array $dados, Pdv $pdv): Requisicao
    {
        $dados['codestoquelocalrequisitante'] = $dados['codestoquelocalrequisitante'] ?? $pdv->codestoquelocal;
        if (empty($dados['codestoquelocalrequisitante'])) {
            abort(422, 'Informe a loja que pede (este dispositivo não tem local de estoque configurado)!');
        }
        static::validarCabecalho($dados);

        $req = new Requisicao();
        $req->fill($dados);
        $req->atendimento = Requisicao::ATENDIMENTO_LOJA;
        $req->codigoretirada = static::gerarCodigoRetirada();
        $req->codpdv = $pdv->codpdv;
        $req->status = Requisicao::STATUS_AGUARDANDO;
        $req->save();

        static::salvarItens($req, $dados['itens']);
        RequisicaoProcessamentoService::processar($req);
        return $req->fresh();
    }

    public static function atualizar(Requisicao $req, array $dados, Pdv $pdv): Requisicao
    {
        if (!static::podeEditar($req, $pdv)) {
            abort(422, 'Só a loja que pediu altera, e só enquanto a requisição está aguardando!');
        }
        $dados['codestoquelocalrequisitante'] = $req->codestoquelocalrequisitante;
        static::validarCabecalho($dados);
        $req->fill($dados);
        $req->save();
        static::salvarItens($req, $dados['itens']);
        RequisicaoProcessamentoService::processar($req);
        return $req->fresh();
    }

    public static function cancelar(Requisicao $req, Pdv $pdv): Requisicao
    {
        if (!static::podeCancelar($req, $pdv)) {
            abort(403, 'Só a loja que pediu ou um Gerente cancela a requisição!');
        }
        $ativos = RequisicaoProcessamentoService::negociosAtivos($req);
        if (!empty($ativos)) {
            abort(422, 'Cancele antes o(s) negócio(s) de transferência ' . implode(', ', array_map(
                fn ($cod) => '#' . str_pad($cod, 8, '0', STR_PAD_LEFT),
                $ativos
            )) . '!');
        }
        $req->cancelamento = now();
        $req->codusuariocancelamento = Auth::user()?->codusuario;
        $req->save();
        RequisicaoProcessamentoService::processar($req);
        return $req->fresh();
    }

    protected static function validarCabecalho(array $dados): void
    {
        $requisitante = EstoqueLocal::with('Filial')->findOrFail($dados['codestoquelocalrequisitante']);
        $requisitada = EstoqueLocal::with('Filial')->findOrFail($dados['codestoquelocalrequisitada']);
        if ($requisitante->codestoquelocal == $requisitada->codestoquelocal) {
            abort(422, 'A loja que atende tem que ser outra!');
        }
        foreach ([$requisitante, $requisitada] as $local) {
            if (!empty($local->inativo)) {
                abort(422, "O local {$local->estoquelocal} está inativo!");
            }
        }
        if ($requisitante->Filial->codempresa != $requisitada->Filial->codempresa) {
            abort(422, 'As duas lojas precisam ser da mesma empresa (a requisição vira transferência)!');
        }
        if (!empty($dados['codnegocio'])) {
            $negocio = Negocio::findOrFail($dados['codnegocio']);
            if ($negocio->codnegociostatus != NegocioService::STATUS_FECHADO) {
                abort(422, 'Feche o negócio antes de requisitar!');
            }
        }
    }

    // grava os itens da tela: atualiza os que ficaram, cria os novos, apaga os que sairam
    protected static function salvarItens(Requisicao $req, array $itens): void
    {
        $vistos = [];
        foreach ($itens as $item) {
            $pb = ProdutoBarra::findOrFail($item['codprodutobarra']);
            if (isset($vistos[$pb->codprodutobarra])) {
                abort(422, "{$pb->Produto->produto} está duas vezes na requisição; some as quantidades numa linha só!");
            }
            $vistos[$pb->codprodutobarra] = true;
            if ($erro = UnidadeMedidaService::fracaoRecusada($pb, $item['quantidade'])) {
                abort(422, $erro);
            }
            $reg = RequisicaoItem::firstOrNew([
                'codrequisicao' => $req->codrequisicao,
                'codprodutobarra' => $pb->codprodutobarra,
            ]);
            $reg->quantidade = $item['quantidade'];
            $reg->save();
        }
        $req->RequisicaoItemS()->whereNotIn('codprodutobarra', array_keys($vistos))->delete();
    }

    // Requisitar a partir da venda fechada: cliente e itens (somados por barra) para a tela
    // de nova requisicao, que deixa mexer em tudo antes de gravar
    public static function rascunhoDoNegocio(Negocio $negocio): array
    {
        if ($negocio->codnegociostatus != NegocioService::STATUS_FECHADO) {
            abort(422, 'Feche o negócio antes de requisitar!');
        }
        $pessoa = $negocio->Pessoa;
        $temCliente = $pessoa && $negocio->codpessoa != 1;
        $endereco = $temCliente ? implode(' - ', array_filter([
            implode(', ', array_filter([$pessoa->endereco, $pessoa->numero])),
            $pessoa->complemento,
            $pessoa->bairro,
            $pessoa->Cidade ? ($pessoa->Cidade->cidade . '/' . ($pessoa->Cidade->Estado->sigla ?? '')) : null,
        ])) : null;
        // os telefones do cadastro so' com os digitos, sem o zero na frente do DDD; o que nao
        // sobra digito nenhum ('00 0000 0000') fica de fora
        $telefones = $temCliente ? array_values(array_unique(array_filter(array_map(
            fn ($t) => ltrim(numeroLimpo($t ?? ''), '0'),
            [$pessoa->telefone1, $pessoa->telefone2, $pessoa->telefone3]
        )))) : [];
        $nome = $temCliente ? ($pessoa->fantasia ?: $pessoa->pessoa) : null;
        $quantidades = [];
        foreach ($negocio->NegocioProdutoBarraS()->whereNull('inativo')->get() as $npb) {
            $quantidades[$npb->codprodutobarra] = ($quantidades[$npb->codprodutobarra] ?? 0) + $npb->quantidade;
        }
        return [
            'codnegocio' => $negocio->codnegocio,
            'codpessoa' => $temCliente ? $negocio->codpessoa : null,
            // o nome do cliente para cada telefone do cadastro
            'nomes' => $temCliente ? array_fill(0, max(count($telefones), 1), $nome) : [],
            'telefones' => $telefones,
            'endereco' => $endereco ?: null,
            'itens' => static::itensComProduto($quantidades),
        ];
    }

    // Sugestao abaixo do minimo (mesmas linhas do PDF de Sugestao de Transferencias): o que
    // a requisitante tem no minimo ou abaixo e a requisitada tem saldo, ate' o maximo,
    // na embalagem de transferencia (ou na unidade, se a variacao nao tem a barra dela)
    public static function sugestao(int $requisitante, int $requisitada, ?int $codmarca = null, ?string $abc = null): array
    {
        $rows = EstoqueSaldoRelatorioService::transferenciasLinhas([
            'codestoquelocalorigem' => $requisitada,
            'codestoquelocaldestino' => $requisitante,
            'codmarca' => $codmarca,
            'abc' => $abc,
        ]);
        $quantidades = [];
        foreach ($rows as $r) {
            if (!empty($r->codprodutobarraembalagem)) {
                $quantidades[$r->codprodutobarraembalagem] = $r->transferir / $r->quantidadeembalagem;
            } elseif (!empty($r->codprodutobarraunidade)) {
                $quantidades[$r->codprodutobarraunidade] = (float) $r->transferir;
            }
        }
        return static::itensComProduto($quantidades);
    }

    // [codprodutobarra => quantidade] no shape dos itens do formulario
    protected static function itensComProduto(array $quantidades): array
    {
        $barras = ProdutoBarra::with([
            'Produto.UnidadeMedida',
            'ProdutoEmbalagem.UnidadeMedida',
            'ProdutoVariacao.ProdutoImagem',
        ])->whereIn('codprodutobarra', array_keys($quantidades))->get()->keyBy('codprodutobarra');
        $itens = [];
        foreach ($quantidades as $codprodutobarra => $quantidade) {
            if (!$pb = $barras[$codprodutobarra] ?? null) {
                continue;
            }
            $itens[] = [
                'codprodutobarra' => (int) $codprodutobarra,
                'quantidade' => (float) $quantidade,
                'produto' => (new ProdutoBarraPesquisaResource($pb))->toArray(request()),
            ];
        }
        return $itens;
    }

    // as requisicoes que nasceram da venda (a tela do negocio mostra)
    public static function doNegocio(int $codnegocio): array
    {
        return DB::select("
            select r.codrequisicao, r.status, r.destino, r.criacao,
                elr.sigla as siglarequisitante, eld.sigla as siglarequisitada,
                eld.estoquelocal as requisitada
            from tblrequisicao r
            inner join tblestoquelocal elr on (elr.codestoquelocal = r.codestoquelocalrequisitante)
            left join tblestoquelocal eld on (eld.codestoquelocal = r.codestoquelocalrequisitada)
            where r.codnegocio = :codnegocio
            order by r.codrequisicao
        ", ['codnegocio' => $codnegocio]);
    }

    // Painel por loja: o que ela tem para atender (requisitada) e o que ela pediu
    // (requisitante), a tela separa. So' as abertas, das mais antigas para as mais
    // novas; as entregues e canceladas ficam na listagem.
    public static function painel(int $codestoquelocal): array
    {
        $sql = self::SELECT_LISTA . "
            where :codestoquelocal in (r.codestoquelocalrequisitada, r.codestoquelocalrequisitante)
            and r.status in ('A', 'S', 'P', 'T', 'D')
            order by r.criacao, r.codrequisicao
            limit 500
        ";
        return DB::select($sql, ['codestoquelocal' => $codestoquelocal]);
    }

    // Listagem: todas as requisicoes, da mais nova para a mais antiga, nos filtros
    // que vierem (nenhum e' obrigatorio)
    public static function pesquisar(array $filtros, int $limite, int $offset = 0): array
    {
        $where = ['true'];
        $bind = [];
        foreach (['codrequisicao', 'codnegocio', 'codpessoa', 'codestoquelocalrequisitante', 'codestoquelocalrequisitada', 'status', 'destino'] as $campo) {
            if (!empty($filtros[$campo])) {
                $where[] = "r.{$campo} = :{$campo}";
                $bind[$campo] = $filtros[$campo];
            }
        }
        if (!empty($filtros['de'])) {
            $where[] = 'r.criacao >= :de';
            $bind['de'] = substr($filtros['de'], 0, 10) . ' 00:00:00';
        }
        if (!empty($filtros['ate'])) {
            $where[] = 'r.criacao <= :ate';
            $bind['ate'] = substr($filtros['ate'], 0, 10) . ' 23:59:59';
        }
        if (!empty($filtros['busca'])) {
            // quem retira ou recebe
            $where[] = "array_to_string(r.nomes, ' ') ilike :busca";
            $bind['busca'] = '%' . preg_replace('/\s+/', '%', trim($filtros['busca'])) . '%';
        }
        // o telefone e' gravado so' com os digitos: a mascara digitada nao atrapalha, e um
        // pedaco (os ultimos 4) tambem acha
        $telefone = preg_replace('/\D/', '', $filtros['telefone'] ?? '');
        if ($telefone !== '') {
            $where[] = "array_to_string(r.telefones, ' ') like :telefone";
            $bind['telefone'] = "%{$telefone}%";
        }
        // o codigo inteiro: pedaco nao acha
        if (!empty($filtros['codigoretirada'])) {
            $where[] = 'r.codigoretirada = :codigoretirada';
            $bind['codigoretirada'] = $filtros['codigoretirada'];
        }
        $sql = self::SELECT_LISTA . '
            where ' . implode("\n            and ", $where) . '
            order by r.criacao desc, r.codrequisicao desc
            limit ' . $limite . ' offset ' . $offset;
        return DB::select($sql, $bind);
    }
}
