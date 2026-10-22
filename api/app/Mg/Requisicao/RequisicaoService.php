<?php

namespace Mg\Requisicao;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Estoque\EstoqueLocal;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;
use Mg\Pdv\Pdv;
use Mg\Produto\ProdutoBarra;
use Mg\Produto\UnidadeMedidaService;
use Mg\Usuario\Autorizador;

// Requisicao entre lojas (TASK-55, doc-6): cadastro, permissoes e painel.
// O status nao se grava aqui: quem decide e' o RequisicaoProcessamentoService.
class RequisicaoService
{
    // o lado de quem pediu: o dispositivo e' de uma filial da loja requisitante
    public static function ehRequisitante(Requisicao $req, ?Pdv $pdv): bool
    {
        return !empty($pdv) && $pdv->codfilial == $req->EstoqueLocalRequisitante->codfilial;
    }

    // o lado de quem atende
    public static function ehRequisitada(Requisicao $req, ?Pdv $pdv): bool
    {
        return !empty($pdv) && !empty($req->EstoqueLocalRequisitada)
            && $pdv->codfilial == $req->EstoqueLocalRequisitada->codfilial;
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

    // justificar os itens e finalizar com divergencia: quem atende ou Gerente
    public static function podeJustificar(Requisicao $req, ?Pdv $pdv): bool
    {
        return in_array($req->status, [Requisicao::STATUS_PARCIAL, Requisicao::STATUS_DIVERGENCIA])
            && (static::ehRequisitada($req, $pdv) || static::ehGerente());
    }

    public static function gerarCodigoRetirada(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function criar(array $dados, Pdv $pdv): Requisicao
    {
        $dados['codestoquelocalrequisitante'] = $dados['codestoquelocalrequisitante'] ?? $pdv->codestoquelocal;
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

    // Painel por loja: o que ela tem para atender (requisitada) ou o que ela pediu
    // (requisitante). So' as abertas, das mais antigas para as mais novas; com
    // finalizadas, tambem as entregues e canceladas dos ultimos 30 dias.
    public static function painel(int $codestoquelocal, string $lado, bool $finalizadas = false): array
    {
        $coluna = $lado === 'requisitar' ? 'r.codestoquelocalrequisitante' : 'r.codestoquelocalrequisitada';
        $status = "r.status in ('A', 'S', 'P', 'T', 'D')";
        if ($finalizadas) {
            $status = "({$status} or r.criacao >= now() - interval '30 days')";
        }
        $sql = "
            select
                r.codrequisicao, r.status, r.destino, r.atendimento, r.nome, r.criacao,
                r.codestoquelocalrequisitante, elr.sigla as siglarequisitante,
                elr.estoquelocal as requisitante,
                r.codestoquelocalrequisitada, eld.sigla as siglarequisitada,
                eld.estoquelocal as requisitada,
                (select count(*) from tblrequisicaoitem ri
                  where ri.codrequisicao = r.codrequisicao and ri.quantidade > 0) as itens
            from tblrequisicao r
            inner join tblestoquelocal elr on (elr.codestoquelocal = r.codestoquelocalrequisitante)
            left join tblestoquelocal eld on (eld.codestoquelocal = r.codestoquelocalrequisitada)
            where {$coluna} = :codestoquelocal
            and {$status}
            order by r.criacao, r.codrequisicao
            limit 500
        ";
        return DB::select($sql, ['codestoquelocal' => $codestoquelocal]);
    }
}
