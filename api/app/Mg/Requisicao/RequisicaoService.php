<?php

namespace Mg\Requisicao;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Auditoria\AuditoriaService;
use Mg\Estoque\EstoqueLocal;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;
use Mg\Pdv\Pdv;
use Mg\Pessoa\Pessoa;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\ProdutoBarraPesquisaResource;
use Mg\Produto\UnidadeMedidaService;
use Mg\Usuario\Autorizador;

// Requisicao entre lojas (TASK-55, doc-6): cadastro, permissoes e painel.
// O status nao se grava aqui: quem decide e' o RequisicaoProcessamentoService.
class RequisicaoService
{
    // a linha das listas (painel e listagem): as duas lojas, quem pediu (o usuario), quem
    // retira e quantos itens
    // nao atendida: finalizada (D) sem nenhuma parte separada (negocio fechado ligado), a
    // loja que atende nao tinha. Nao e' status gravado: e' a D sem parte, a tela mostra
    // "Nao atendida" e o painel nao mostra (nao ha' o que entregar nem receber)
    const SQL_NAO_ATENDIDA = "(r.status = 'D' and not exists (
            select 1 from tblnegociorequisicao nr
            inner join tblnegocio n on (n.codnegocio = nr.codnegocio)
            where nr.codrequisicao = r.codrequisicao and nr.inativo is null and n.codnegociostatus = 2
        ))";

    const SELECT_LISTA = "
        select
            r.codrequisicao, r.status, r.destino, r.atendimento, r.prioridade, r.criacao,
            array_to_string(r.nomes, ', ') as nome,
            r.codestoquelocalrequisitante, elr.sigla as siglarequisitante,
            elr.estoquelocal as requisitante,
            r.codestoquelocalrequisitada, eld.sigla as siglarequisitada,
            eld.estoquelocal as requisitada,
            (select count(*) from tblrequisicaoitem ri
              where ri.codrequisicao = r.codrequisicao and ri.quantidade > 0) as itens,
            " . self::SQL_NAO_ATENDIDA . " as naoatendida,
            uc.usuario as usuariocriacao
        from tblrequisicao r
        inner join tblestoquelocal elr on (elr.codestoquelocal = r.codestoquelocalrequisitante)
        left join tblestoquelocal eld on (eld.codestoquelocal = r.codestoquelocalrequisitada)
        left join tblusuario uc on (uc.codusuario = r.codusuariocriacao)
    ";

    const POR_PAGINA = 50;

    // o lado de quem pediu: o local de estoque do dispositivo e' a loja requisitante (o mesmo
    // que o Fechar confere no negocio)
    public static function ehRequisitante(Requisicao $req, ?Pdv $pdv): bool
    {
        return !empty($pdv->codestoquelocal) && $pdv->codestoquelocal == $req->codestoquelocalrequisitante;
    }

    // o lado de quem atende
    public static function ehRequisitada(Requisicao $req, ?Pdv $pdv): bool
    {
        return !empty($pdv->codestoquelocal) && $pdv->codestoquelocal == $req->codestoquelocalrequisitada;
    }

    public static function ehGerente(): bool
    {
        return Autorizador::pode(['Gerente']);
    }

    // pode([]) e' so' o Administrador
    public static function ehAdministrador(): bool
    {
        return Autorizador::pode([]);
    }

    // Os botoes sao do lado do PDV (28/10/2026): quem pede edita, cancela, troca a prioridade e
    // da' o recebido; quem atende separa, justifica, finaliza e entrega ao cliente. Gerente nao
    // faz o servico do outro lado; so' o Administrador age em qualquer PDV.
    public static function podeEditar(Requisicao $req, ?Pdv $pdv): bool
    {
        return $req->status == Requisicao::STATUS_AGUARDANDO
            && (static::ehRequisitante($req, $pdv) || static::ehAdministrador());
    }

    public static function podeCancelar(Requisicao $req, ?Pdv $pdv): bool
    {
        return !in_array($req->status, [Requisicao::STATUS_CANCELADA, Requisicao::STATUS_ENTREGUE])
            && (static::ehRequisitante($req, $pdv) || static::ehAdministrador());
    }

    // justificar os itens e finalizar: quem atende, enquanto a separacao nao acabou (tambem
    // sem nada separado: e' assim que quem atende diz que nao tem)
    public static function podeFinalizar(Requisicao $req, ?Pdv $pdv): bool
    {
        return in_array($req->status, [
            Requisicao::STATUS_AGUARDANDO,
            Requisicao::STATUS_EM_SEPARACAO,
            Requisicao::STATUS_PARCIAL,
        ]) && (static::ehRequisitada($req, $pdv) || static::ehAdministrador());
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

    // trocar a prioridade: quem pediu, enquanto a requisicao esta' aberta
    public static function podePrioridade(Requisicao $req, ?Pdv $pdv): bool
    {
        return in_array($req->status, [
            Requisicao::STATUS_AGUARDANDO,
            Requisicao::STATUS_EM_SEPARACAO,
            Requisicao::STATUS_PARCIAL,
            Requisicao::STATUS_TOTAL,
            Requisicao::STATUS_DIVERGENCIA,
        ]) && (static::ehRequisitante($req, $pdv) || static::ehAdministrador());
    }

    // reabrir, desfazendo o Finalizar com divergencias (tambem a nao atendida): o Gerente, no
    // PDV da loja que atende
    public static function podeReabrir(Requisicao $req, ?Pdv $pdv): bool
    {
        return $req->status == Requisicao::STATUS_DIVERGENCIA
            && (static::ehAdministrador() || (static::ehGerente() && static::ehRequisitada($req, $pdv)));
    }

    // depois de criada, a prioridade so' muda aqui, com o motivo na auditoria
    public static function prioridade(Requisicao $req, int $prioridade, string $motivo, ?Pdv $pdv): Requisicao
    {
        if (!static::podePrioridade($req, $pdv)) {
            abort(403, 'Só a loja que pediu ou um Administrador troca a prioridade, e só com a requisição aberta!');
        }
        if (trim($motivo) === '') {
            abort(422, 'Informe o motivo!');
        }
        if ($req->prioridade == $prioridade) {
            return $req;
        }
        $antes = $req->prioridade;
        $req->prioridade = $prioridade;
        $req->save();
        AuditoriaService::registrar(
            'tblrequisicao',
            $req->codrequisicao,
            AuditoriaService::TIPO_PRIORIDADE_ALTERADA,
            ['prioridade' => Requisicao::PRIORIDADES[$antes] ?? $antes],
            ['prioridade' => Requisicao::PRIORIDADES[$prioridade] ?? $prioridade],
            $motivo
        );
        return $req;
    }

    protected static function exigirJustificar(Requisicao $req, ?Pdv $pdv): void
    {
        if (!static::podeFinalizar($req, $pdv)) {
            abort(403, 'Só a loja que atende justifica e finaliza, e só enquanto a separação não acabou (finalizada, reabra antes)!');
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

    // Finalizar = acabou a separacao, o que nao foi nao vai mais: nada em separacao, cada
    // item que nao bateu com motivo, e cada produto fora do pedido como substituto de um
    // item ou incluido a mais (faltou, recusa dizendo o que justificar)
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
                $erros[] = "{$item->ProdutoBarra->Produto->produto} (não bateu: clique no item e informe o motivo)";
            }
        }
        $substitutos = array_filter(array_column(array_filter($calc['itens'], fn ($i) => $i['codrequisicao'] == $req->codrequisicao), 'codprodutovariacaosubstituto'));
        foreach ($calc['fora'][$req->codrequisicao] ?? [] as $f) {
            if (!in_array($f['codprodutovariacao'], $substitutos)) {
                $pb = ProdutoBarra::with('Produto')->find($f['codprodutobarra']);
                $erros[] = "{$pb->Produto->produto} (veio fora do pedido: marque como substituto de um item ou Enviado a mais)";
            }
        }
        if (!empty($erros)) {
            abort(422, 'Justifique antes de finalizar: ' . implode('; ', array_unique($erros)) . '.');
        }
        $req->finalizacao = now();
        $req->codusuariofinalizacao = Auth::user()?->codusuario;
        $req->save();
        RequisicaoProcessamentoService::processar($req);
        return $req->fresh();
    }

    // tira a justificativa de um item (achou o produto, tem saldo): enquanto a separacao
    // nao acabou, quem pode finalizar. O "enviado a mais" (item com pedido 0) sai da
    // requisicao e o produto volta ao fora do pedido
    public static function tirarJustificativa(RequisicaoItem $item, ?Pdv $pdv): void
    {
        $req = $item->Requisicao;
        static::exigirJustificar($req, $pdv);
        if ((float) $item->quantidade == 0) {
            $item->delete();
        } else {
            $item->motivo = null;
            $item->observacao = null;
            $item->codprodutobarrasubstituto = null;
            $item->save();
        }
        RequisicaoProcessamentoService::processar($req);
    }

    // desfaz o Finalizar: o processo devolve a pendente (ou aguardando, sem nada
    // fechado); as justificativas dos itens ficam
    // (cada uma sai pelo tirarJustificativa)
    public static function reabrir(Requisicao $req, string $motivo, ?Pdv $pdv): Requisicao
    {
        if (!static::podeReabrir($req, $pdv)) {
            abort(403, 'Só o Gerente da loja que atende (no PDV dela) ou um Administrador reabre, e só requisição finalizada!');
        }
        if (trim($motivo) === '') {
            abort(422, 'Informe o motivo!');
        }
        $antes = $req->finalizacao;
        $req->finalizacao = null;
        $req->codusuariofinalizacao = null;
        $req->save();
        AuditoriaService::registrar(
            'tblrequisicao',
            $req->codrequisicao,
            AuditoriaService::TIPO_REQUISICAO_REABERTA,
            ['finalizacao' => $antes],
            ['finalizacao' => null],
            $motivo
        );
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
        // quem pede e' a loja do PDV; outra, so' Gerente escolhe
        if ($dados['codestoquelocalrequisitante'] != $pdv->codestoquelocal && !static::ehGerente()) {
            $loja = $pdv->EstoqueLocal->estoquelocal ?? 'sem local de estoque';
            abort(422, "A loja que pede é a deste PDV ({$loja}). Só Gerente escolhe outra!");
        }
        static::validarCabecalho($dados);

        $req = new Requisicao();
        $req->fill($dados);
        $req->atendimento = Requisicao::ATENDIMENTO_LOJA;
        $req->codigoretirada = static::gerarCodigoRetirada();
        $req->codpdv = $pdv->codpdv;
        $req->status = Requisicao::STATUS_AGUARDANDO;
        // fora do fillable: o atualizar nao mexe nela
        $req->prioridade = $dados['prioridade'] ?? Requisicao::PRIORIDADE_NORMAL;
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
            abort(403, 'Só a loja que pediu cancela a requisição!');
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

    // O endereco numa linha so': "rua, numero - complemento - bairro - cidade/UF". O do cliente
    // (rascunho da venda) e o da loja que pediu (reposicao, no detalhe); sem nada, null
    public static function enderecoDe(?Pessoa $pessoa): ?string
    {
        if (!$pessoa) {
            return null;
        }
        return implode(' - ', array_filter([
            implode(', ', array_filter([$pessoa->endereco, $pessoa->numero])),
            $pessoa->complemento,
            $pessoa->bairro,
            $pessoa->Cidade ? ($pessoa->Cidade->cidade . '/' . ($pessoa->Cidade->Estado->sigla ?? '')) : null,
        ])) ?: null;
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
        $endereco = $temCliente ? static::enderecoDe($pessoa) : null;
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
            // a loja da venda e' quem pede: a tela nao pergunta
            'codestoquelocal' => $negocio->codestoquelocal,
            'codpessoa' => $temCliente ? $negocio->codpessoa : null,
            // o nome do cliente para cada telefone do cadastro
            'nomes' => $temCliente ? array_fill(0, max(count($telefones), 1), $nome) : [],
            'telefones' => $telefones,
            'endereco' => $endereco,
            'itens' => static::itensComProduto($quantidades),
        ];
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
                " . self::SQL_NAO_ATENDIDA . " as naoatendida,
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
    // (requisitante), a tela separa. Saiu para entrega (entrega no endereco ou reposicao, tudo
    // ja' saiu da loja que atende) so' no lado de quem pediu: o servico de quem atende acabou na
    // saida. Separada (total ou finalizada) fica nas duas lojas ate' sair: quem separou
    // acompanha o que ainda nao saiu (28/10/2026), e sai do painel dela na saida para entrega
    // ou quando o cliente retira. Da mais urgente para a menos e, na
    // mesma prioridade, da mais antiga para a mais nova; as entregues, nao atendidas
    // (SQL_NAO_ATENDIDA) e canceladas ficam na listagem.
    public static function painel(int $codestoquelocal): array
    {
        $sql = self::SELECT_LISTA . "
            where (r.codestoquelocalrequisitada = :codestoquelocal or r.codestoquelocalrequisitante = :codestoquelocal2)
            and (r.status in ('A', 'S', 'P', 'T', 'D')
                or (r.status = 'R' and r.codestoquelocalrequisitante = :codestoquelocal3))
            and not " . self::SQL_NAO_ATENDIDA . "
            order by r.prioridade desc, r.criacao, r.codrequisicao
            limit 500
        ";
        return DB::select($sql, [
            'codestoquelocal' => $codestoquelocal,
            'codestoquelocal2' => $codestoquelocal,
            'codestoquelocal3' => $codestoquelocal,
        ]);
    }

    // Listagem: todas as requisicoes, da mais nova para a mais antiga, nos filtros
    // que vierem (nenhum e' obrigatorio)
    public static function pesquisar(array $filtros, int $limite, int $offset = 0): array
    {
        $where = ['true'];
        $bind = [];
        foreach (['codrequisicao', 'codnegocio', 'codpessoa', 'codestoquelocalrequisitante', 'codestoquelocalrequisitada', 'destino', 'prioridade'] as $campo) {
            if (!empty($filtros[$campo])) {
                $where[] = "r.{$campo} = :{$campo}";
                $bind[$campo] = $filtros[$campo];
            }
        }
        // o status pode vir em lista ("S,P"): o filtro da tela junta os que tem o mesmo nome
        if (!empty($filtros['status'])) {
            $marcas = [];
            foreach (explode(',', $filtros['status']) as $i => $letra) {
                $marcas[] = ":status{$i}";
                $bind["status{$i}"] = $letra;
            }
            $where[] = 'r.status in (' . implode(', ', $marcas) . ')';
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
