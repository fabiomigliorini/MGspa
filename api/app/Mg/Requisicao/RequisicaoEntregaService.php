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
// reposicao e' a loja que pediu dando o recebido, sem codigo. Entrega no endereco tem dois
// passos: a loja que atende registra a retirada pelo entregador da loja que pediu (etapa C,
// sem codigo, e' o que marca o negocio entregue) e a loja que pediu, a entrega ao cliente.
class RequisicaoEntregaService
{
    const JUSTIFICATIVA_MINIMA = 15;

    // partes fechadas da requisicao que o filtro aceita, com as requisicoes de cada uma
    protected static function partes(Requisicao $req, callable $filtro): array
    {
        $calc = RequisicaoProcessamentoService::calcular([$req->codrequisicao]);
        return array_values(array_map(fn ($n) => [
            'codnegocio' => $n['codnegocio'],
            'lancamento' => $n['lancamento'],
            'requisicoes' => $n['requisicoes'],
        ], array_filter($calc['negocios'], fn ($n) => in_array($req->codrequisicao, $n['requisicoes'])
            && $n['codnegociostatus'] == NegocioService::STATUS_FECHADO
            && $filtro($n))));
    }

    // partes do Entregar/Recebido: sem entrega; na entrega no endereco, as ja' retiradas pelo
    // entregador e ainda nao entregues ao cliente
    public static function partesPendentes(Requisicao $req): array
    {
        if (static::emDoisPassos($req)) {
            return static::partes($req, fn ($n) => !empty($n['entregue']) && empty($n['entreguecliente']));
        }
        return static::partes($req, fn ($n) => empty($n['entregue']));
    }

    // partes que o entregador ainda nao retirou (so' na entrega no endereco)
    public static function partesARetirar(Requisicao $req): array
    {
        if (!static::emDoisPassos($req)) {
            return [];
        }
        return static::partes($req, fn ($n) => empty($n['entregue']));
    }

    public static function emDoisPassos(Requisicao $req): bool
    {
        return $req->destino === Requisicao::DESTINO_ENTREGA;
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

    // cliente retira: a loja que atende entrega ao cliente; entrega no endereco: a loja que
    // pediu entrega (o entregador e' dela); reposicao: a loja que pediu da' o recebido (quem
    // enviou nao confirma o proprio recebido). Gerente nao faz o servico do outro lado; so' o
    // Administrador age em qualquer PDV (28/10/2026)
    public static function podeEntregar(Requisicao $req, ?Pdv $pdv): bool
    {
        if (in_array($req->status, [Requisicao::STATUS_CANCELADA, Requisicao::STATUS_ENTREGUE])) {
            return false;
        }
        $lado = $req->destino === Requisicao::DESTINO_RETIRA
            ? RequisicaoService::ehRequisitada($req, $pdv)
            : RequisicaoService::ehRequisitante($req, $pdv);
        return ($lado || RequisicaoService::ehAdministrador()) && !empty(static::partesPendentes($req));
    }

    // entrega no endereco: a loja que atende registra a retirada pelo entregador da loja que pediu
    public static function podeRetirar(Requisicao $req, ?Pdv $pdv): bool
    {
        if (!static::emDoisPassos($req)
            || in_array($req->status, [Requisicao::STATUS_CANCELADA, Requisicao::STATUS_ENTREGUE])) {
            return false;
        }
        return (RequisicaoService::ehRequisitada($req, $pdv) || RequisicaoService::ehAdministrador())
            && !empty(static::partesARetirar($req));
    }

    public static function retirar(Requisicao $req, array $dados, ?Pdv $pdv): array
    {
        if (!static::podeRetirar($req, $pdv)) {
            abort(403, 'A retirada pelo entregador é registrada pela loja que atende, com parte separada a retirar!');
        }
        $partes = static::partesARetirar($req);
        if (!empty($dados['codnegocios'])) {
            $partes = array_values(array_filter($partes, fn ($p) => in_array($p['codnegocio'], $dados['codnegocios'])));
        }
        if (empty($partes)) {
            abort(422, 'Nenhuma parte separada a retirar!');
        }
        $registros = [];
        foreach ($partes as $parte) {
            $registros[] = NegocioEntregaService::registrar(Negocio::lockForUpdate()->findOrFail($parte['codnegocio']), [
                'etapa' => NegocioEntrega::ETAPA_RETIRADA,
                'resultado' => NegocioEntrega::RESULTADO_ENTREGUE,
                'recebedor' => $dados['recebedor'] ?? null,
                'observacao' => $dados['observacao'] ?? null,
                'codrequisicao' => $req->codrequisicao,
            ]);
        }
        RequisicaoProcessamentoService::processar($req);
        return $registros;
    }

    public static function entregar(Requisicao $req, array $dados, ?Pdv $pdv): array
    {
        if (!static::podeEntregar($req, $pdv)) {
            abort(403, match ($req->destino) {
                Requisicao::DESTINO_RETIRA => 'A entrega ao cliente é registrada pela loja que atende, com parte separada pendente!',
                Requisicao::DESTINO_ENTREGA => 'A entrega ao cliente é registrada pela loja que pediu, com parte já retirada pelo entregador!',
                default => 'O recebido da reposição é registrado pela loja que pediu, com parte separada pendente!',
            });
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
                'etapa' => NegocioEntrega::ETAPA_CLIENTE,
                'codigoconferido' => $conferido,
                'justificativa' => ($entregou && static::comCodigo($req) && !$conferido) ? $justificativa : null,
            ]);
        }
        RequisicaoProcessamentoService::processar($req);
        return $registros;
    }
}
