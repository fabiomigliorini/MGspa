<?php

namespace Mg\Requisicao;

use Exception;
use Mg\Negocio\Negocio;

// O que o negocio do PDV carrega da requisicao (TASK-55, doc-6): so' a lista das
// requisicoes que ele atende. Gravar a lista e conferir a coerencia no Fechar; o
// calculo do atendido e' do RequisicaoProcessamentoService.
class RequisicaoNegocioService
{
    // a lista que veio do PDV (pelo uuid); requisicao inexistente recusa a sincronizacao,
    // a menos que o PDV ja' tenha tirado ela do negocio (ai' nao ha' o que gravar)
    public static function importar(Negocio $negocio, array $links): void
    {
        foreach ($links as $link) {
            if (empty($link['uuid']) || empty($link['codrequisicao'])) {
                continue;
            }
            if (!Requisicao::whereKey($link['codrequisicao'])->exists()) {
                if (!empty($link['inativo'])) {
                    continue;
                }
                $cod = str_pad($link['codrequisicao'], 8, '0', STR_PAD_LEFT);
                throw new Exception("A requisição #{$cod} não existe: tire ela do negócio!", 1);
            }
            $reg = NegocioRequisicao::firstOrNew(['uuid' => $link['uuid']]);
            if ($reg->exists && $reg->codnegocio != $negocio->codnegocio) {
                throw new Exception("Ligação com requisição de outro negócio ({$reg->codnegocio})!", 1);
            }
            $reg->codnegocio = $negocio->codnegocio;
            $reg->codrequisicao = $link['codrequisicao'];
            $reg->inativo = $link['inativo'] ?? null;
            if ($reg->isDirty()) {
                $reg->save();
            }
        }
    }

    // para o PDV (NegocioResource): a lista como ele manda
    public static function doNegocio(int $codnegocio): array
    {
        return NegocioRequisicao::where('codnegocio', $codnegocio)
            ->orderBy('codnegociorequisicao')
            ->get(['uuid', 'codrequisicao', 'inativo'])
            ->map(fn ($r) => [
                'uuid' => $r->uuid,
                'codrequisicao' => $r->codrequisicao,
                'inativo' => $r->inativo?->format('Y-m-d H:i:s'),
            ])
            ->all();
    }

    public static function requisicoesAtivas(int $codnegocio)
    {
        return Requisicao::with(['EstoqueLocalRequisitante', 'EstoqueLocalRequisitada'])
            ->whereIn('codrequisicao', NegocioRequisicao::where('codnegocio', $codnegocio)
                ->whereNull('inativo')->select('codrequisicao'))
            ->orderBy('criacao')
            ->get();
    }

    // No Fechar: cada requisicao ligada existe, esta' aberta, e' atendida pela loja do
    // negocio e vai para a loja destino dele; todas com o mesmo destino da mercadoria e,
    // na retirada/entrega, do mesmo cliente. Divergiu, recusa mostrando o que.
    public static function conferirCoerencia(Negocio $negocio): void
    {
        $reqs = static::requisicoesAtivas($negocio->codnegocio);
        if ($reqs->isEmpty()) {
            return;
        }
        $erros = [];
        if (!$negocio->NaturezaOperacao->transferencia) {
            $erros[] = 'o negócio de requisição tem que ser de transferência';
        }
        foreach ($reqs as $req) {
            $cod = '#' . str_pad($req->codrequisicao, 8, '0', STR_PAD_LEFT);
            if (in_array($req->status, [
                Requisicao::STATUS_CANCELADA,
                Requisicao::STATUS_TOTAL,
                Requisicao::STATUS_DIVERGENCIA,
                Requisicao::STATUS_COM_ENTREGADOR,
                Requisicao::STATUS_ENTREGUE,
            ])) {
                $erros[] = "a requisição {$cod} não aceita mais separação (situação: " . Requisicao::STATUS[$req->status] . ')';
            }
            if ($req->codestoquelocalrequisitada != $negocio->codestoquelocal) {
                $erros[] = "a requisição {$cod} é para "
                    . ($req->EstoqueLocalRequisitada?->estoquelocal ?? 'Compras')
                    . ' atender, e este negócio é de ' . $negocio->EstoqueLocal->estoquelocal;
            }
            if ($req->codestoquelocalrequisitante != $negocio->codestoquelocaldestino) {
                $erros[] = "a requisição {$cod} vai para {$req->EstoqueLocalRequisitante->estoquelocal}, "
                    . 'e a loja destino do negócio é ' . ($negocio->EstoqueLocalDestino?->estoquelocal ?? 'nenhuma');
            }
        }
        if ($reqs->pluck('destino')->unique()->count() > 1) {
            $erros[] = 'as requisições têm destinos da mercadoria diferentes (retira, entrega, reposição)';
        }
        $comCliente = $reqs->whereIn('destino', [Requisicao::DESTINO_RETIRA, Requisicao::DESTINO_ENTREGA]);
        if ($comCliente->count() > 1 && ($comCliente->pluck('codpessoa')->unique()->count() > 1 || $comCliente->whereNull('codpessoa')->isNotEmpty())) {
            $erros[] = 'retirada/entrega só junta requisições do mesmo cliente cadastrado';
        }
        if (!empty($erros)) {
            throw new Exception('Não dá para fechar: ' . implode('; ', array_unique($erros)) . '.', 1);
        }
    }
}
