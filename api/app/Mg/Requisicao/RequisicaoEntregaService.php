<?php

namespace Mg\Requisicao;

use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioEntrega;
use Mg\Negocio\NegocioEntregaService;
use Mg\Negocio\NegocioService;
use Mg\Pdv\Pdv;

// Entrega pela requisicao (TASK-55, doc-6): cada parte (negocio fechado ainda sem
// entrega) ganha um registro de entrega do negocio. Retirada/entrega ao cliente pede o
// codigo de retirada de todas as requisicoes daquele negocio, ou justificativa sem ele;
// reposicao e' a loja que pediu dando o recebido, sem codigo.
class RequisicaoEntregaService
{
    const JUSTIFICATIVA_MINIMA = 15;

    // partes fechadas ainda sem entrega, com as requisicoes de cada uma
    public static function partesPendentes(Requisicao $req): array
    {
        $calc = RequisicaoProcessamentoService::calcular([$req->codrequisicao]);
        return array_values(array_map(fn ($n) => [
            'codnegocio' => $n['codnegocio'],
            'lancamento' => $n['lancamento'],
            'requisicoes' => $n['requisicoes'],
        ], array_filter($calc['negocios'], fn ($n) => in_array($req->codrequisicao, $n['requisicoes'])
            && $n['codnegociostatus'] == NegocioService::STATUS_FECHADO
            && empty($n['entregue']))));
    }

    public static function comCodigo(Requisicao $req): bool
    {
        return in_array($req->destino, [Requisicao::DESTINO_RETIRA, Requisicao::DESTINO_ENTREGA]);
    }

    // o codigo com o meio escondido (773982 -> 77XX82): vai na via de separacao e na
    // tela, para conferir a caixa com o do cliente; o inteiro so' na via do cliente, e a
    // entrega continua pedindo o codigo inteiro
    public static function codigoRetiradaMascarado(Requisicao $req): ?string
    {
        if (!static::comCodigo($req) || empty($req->codigoretirada)) {
            return null;
        }
        $codigo = (string) $req->codigoretirada;
        return substr($codigo, 0, 2) . str_repeat('X', strlen($codigo) - 4) . substr($codigo, -2);
    }

    // retirada/entrega: a loja que atende (ou Gerente); reposicao: a loja que pediu (ou Gerente)
    public static function podeEntregar(Requisicao $req, ?Pdv $pdv): bool
    {
        if (in_array($req->status, [Requisicao::STATUS_CANCELADA, Requisicao::STATUS_ENTREGUE])) {
            return false;
        }
        $lado = static::comCodigo($req)
            ? RequisicaoService::ehRequisitada($req, $pdv)
            : RequisicaoService::ehRequisitante($req, $pdv);
        return ($lado || RequisicaoService::ehGerente()) && !empty(static::partesPendentes($req));
    }

    public static function entregar(Requisicao $req, array $dados, ?Pdv $pdv): array
    {
        if (!static::podeEntregar($req, $pdv)) {
            abort(403, static::comCodigo($req)
                ? 'A entrega ao cliente é registrada pela loja que atende (ou Gerente), com parte separada pendente!'
                : 'O recebido da reposição é registrado pela loja que pediu (ou Gerente), com parte separada pendente!');
        }
        $partes = static::partesPendentes($req);
        if (!empty($dados['codnegocios'])) {
            $partes = array_values(array_filter($partes, fn ($p) => in_array($p['codnegocio'], $dados['codnegocios'])));
        }
        if (empty($partes)) {
            abort(422, 'Nenhuma parte separada pendente de entrega!');
        }
        $entregou = in_array($dados['resultado'], [NegocioEntrega::RESULTADO_ENTREGUE, NegocioEntrega::RESULTADO_PROBLEMA]);
        $justificativa = trim((string) ($dados['justificativa'] ?? ''));
        $codigos = $dados['codigos'] ?? [];

        $registros = [];
        foreach ($partes as $parte) {
            $conferido = false;
            if ($entregou && static::comCodigo($req)) {
                // o codigo de cada requisicao do negocio
                $faltam = [];
                foreach (Requisicao::whereIn('codrequisicao', $parte['requisicoes'])->get() as $r) {
                    $informado = (string) ($codigos[$r->codrequisicao] ?? '');
                    if ($informado === '' || !hash_equals((string) $r->codigoretirada, $informado)) {
                        $faltam[] = '#' . str_pad($r->codrequisicao, 8, '0', STR_PAD_LEFT);
                    }
                }
                if (empty($faltam)) {
                    $conferido = true;
                } elseif (mb_strlen($justificativa) < static::JUSTIFICATIVA_MINIMA) {
                    abort(422, 'Código de retirada errado ou faltando (' . implode(', ', $faltam)
                        . '). Sem o código, justifique (mínimo ' . static::JUSTIFICATIVA_MINIMA . ' letras).');
                }
            }
            $registros[] = NegocioEntregaService::registrar(Negocio::lockForUpdate()->findOrFail($parte['codnegocio']), [
                'resultado' => $dados['resultado'],
                'motivo' => $dados['motivo'] ?? null,
                'recebedor' => $dados['recebedor'] ?? null,
                'observacao' => $dados['observacao'] ?? null,
                'codrequisicao' => $req->codrequisicao,
                'codigoconferido' => $conferido,
                'justificativa' => ($entregou && static::comCodigo($req) && !$conferido) ? $justificativa : null,
            ]);
        }
        RequisicaoProcessamentoService::processar($req);
        return $registros;
    }
}
