<?php

namespace Mg\Negocio;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mg\MgService;

// Entregas do negocio (TASK-55, doc-6): cada tentativa e' um registro; o negocio
// fica entregue (tblnegocio.entregue) com um registro E/P ativo.
class NegocioEntregaService
{
    public static function entregas(Negocio $negocio)
    {
        return NegocioEntrega::with('UsuarioCriacao')
            ->where('codnegocio', $negocio->codnegocio)
            ->orderBy('data')
            ->orderBy('codnegocioentrega')
            ->get();
    }

    public static function validar(array $dados): void
    {
        $resultado = $dados['resultado'] ?? null;
        $motivo = $dados['motivo'] ?? null;
        $motivos = NegocioEntrega::MOTIVOS[$resultado] ?? [];
        if (empty($motivos) && !empty($motivo)) {
            throw ValidationException::withMessages(['motivo' => 'Entregue não leva motivo.']);
        }
        if (!empty($motivos) && !array_key_exists((string) $motivo, $motivos)) {
            throw ValidationException::withMessages(['motivo' => 'Informe o motivo.']);
        }
        if ($resultado !== NegocioEntrega::RESULTADO_NAO_ENTREGUE && blank($dados['recebedor'] ?? null)) {
            throw ValidationException::withMessages(['recebedor' => 'Informe quem recebeu.']);
        }
    }

    public static function registrar(Negocio $negocio, array $dados): NegocioEntrega
    {
        if ($negocio->codnegociostatus != NegocioService::STATUS_FECHADO) {
            abort(422, 'Só negócio fechado tem entrega!');
        }
        // negocio de requisicao se entrega pela requisicao (TASK-55): com cliente, ela confere o
        // codigo de retirada; na entrega no endereco, a loja que atende registra a retirada pelo
        // entregador e a loja que pediu, a entrega ao cliente; na reposicao, so' a loja que pediu
        // da' o recebido, nunca quem enviou
        if (empty($dados['codrequisicao'])) {
            $req = \Mg\Requisicao\RequisicaoNegocioService::requisicoesAtivas($negocio->codnegocio)->first();
            if ($req) {
                $cod = str_pad($req->codrequisicao, 8, '0', STR_PAD_LEFT);
                abort(422, match ($req->destino) {
                    \Mg\Requisicao\Requisicao::DESTINO_ENTREGA => "Este negócio é da requisição #{$cod}: registre por ela a retirada pelo entregador (na loja que atende) e a entrega ao cliente (na loja que pediu).",
                    \Mg\Requisicao\Requisicao::DESTINO_RETIRA => "Este negócio é da requisição #{$cod}: registre a entrega por ela (com o código de retirada).",
                    default => "Este negócio é da requisição #{$cod}: o recebido é registrado por ela, na loja que pediu.",
                });
            }
        }
        static::validar($dados);
        $entrega = new NegocioEntrega();
        $entrega->fill($dados);
        $entrega->codnegocio = $negocio->codnegocio;
        $entrega->data = $dados['data'] ?? now();
        if (empty($negocio->entrega)) {
            // registrou entrega de negocio que nao estava marcado: passa a controlar
            $negocio->entrega = true;
        }
        $entrega->save();
        static::atualizarEntregue($negocio);
        return $entrega;
    }

    public static function inativar(NegocioEntrega $entrega): NegocioEntrega
    {
        // a retirada pelo entregador e' o que marca o negocio entregue: com a entrega ao cliente
        // ativa, estornar so' a retirada nao desfaria nada (o cliente ja' recebeu)
        if ($entrega->etapa === NegocioEntrega::ETAPA_RETIRADA && NegocioEntrega::where('codnegocio', $entrega->codnegocio)
            ->where('etapa', NegocioEntrega::ETAPA_CLIENTE)
            ->whereIn('resultado', [NegocioEntrega::RESULTADO_ENTREGUE, NegocioEntrega::RESULTADO_PROBLEMA])
            ->whereNull('inativo')->exists()) {
            abort(422, 'Estorne antes a entrega ao cliente: a retirada pelo entregador só se estorna enquanto o cliente não recebeu.');
        }
        MgService::inativar($entrega);
        static::atualizarEntregue($entrega->Negocio);
        // estornar a entrega ao cliente depois da retirada nao muda o tblnegocio.entregue: a
        // requisicao volta a 'Saiu para entrega' pelo reprocessamento (idempotente)
        \Mg\Requisicao\RequisicaoProcessamentoService::aoMudarNegocio($entrega->codnegocio, 'entregue');
        return $entrega;
    }

    // tblnegocio.entregue = data do primeiro registro E/P ativo; sem nenhum, nulo
    public static function atualizarEntregue(Negocio $negocio): void
    {
        $entregue = DB::selectOne("
            select min(data) as data from tblnegocioentrega
            where codnegocio = :codnegocio and inativo is null and resultado in ('E', 'P')
        ", ['codnegocio' => $negocio->codnegocio])->data;
        $negocio->entregue = $entregue;
        if ($negocio->isDirty()) {
            $negocio->save();
            // requisicoes que ele atende ficam entregues (TASK-55)
            \Mg\Requisicao\RequisicaoProcessamentoService::aoMudarNegocio($negocio->codnegocio, 'entregue');
        }
    }

    // Negocios com entrega pendente: a fazer = a loja e' a origem; a receber = a loja e'
    // o destino. Pelos indices parciais (so' os pendentes), com a ultima tentativa.
    public static function pendentes(int $codestoquelocal, string $lado): array
    {
        $coluna = $lado === 'receber' ? 'n.codestoquelocaldestino' : 'n.codestoquelocal';
        return DB::select("
            select
                n.codnegocio, n.lancamento, n.valortotal, n.observacoes,
                n.codestoquelocal, el.sigla as siglaorigem, el.estoquelocal as origem,
                n.codestoquelocaldestino, eld.sigla as sigladestino, eld.estoquelocal as destino,
                n.codpessoa, p.fantasia, nat.naturezaoperacao,
                ult.resultado as ultimoresultado, ult.motivo as ultimomotivo, ult.data as ultimadata,
                (select count(*) from tblnegocioentrega ne
                  where ne.codnegocio = n.codnegocio and ne.inativo is null) as tentativas
            from tblnegocio n
            inner join tblestoquelocal el on (el.codestoquelocal = n.codestoquelocal)
            left join tblestoquelocal eld on (eld.codestoquelocal = n.codestoquelocaldestino)
            left join tblpessoa p on (p.codpessoa = n.codpessoa)
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            left join lateral (
                select ne.resultado, ne.motivo, ne.data from tblnegocioentrega ne
                where ne.codnegocio = n.codnegocio and ne.inativo is null
                order by ne.data desc, ne.codnegocioentrega desc
                limit 1
            ) ult on true
            where {$coluna} = :codestoquelocal
            and n.entrega
            and n.entregue is null
            and n.codnegociostatus = :fechado
            order by n.lancamento, n.codnegocio
            limit 500
        ", ['codestoquelocal' => $codestoquelocal, 'fechado' => NegocioService::STATUS_FECHADO]);
    }
}
