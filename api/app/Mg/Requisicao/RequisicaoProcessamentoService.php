<?php

namespace Mg\Requisicao;

// Processo idempotente da requisicao (TASK-55, doc-6): e' o unico que grava o
// status. Roda a cada acao da requisicao e a cada evento dos negocios ligados
// a ela, e no botao Reprocessar. A distribuicao do atendido entra na etapa 9.
class RequisicaoProcessamentoService
{
    // negocios de transferencia ligados a requisicao que nao estao cancelados
    public static function negociosAtivos(Requisicao $req): array
    {
        return [];
    }

    public static function processar(Requisicao $req): Requisicao
    {
        $status = !empty($req->cancelamento)
            ? Requisicao::STATUS_CANCELADA
            : Requisicao::STATUS_AGUARDANDO;
        if ($req->status !== $status) {
            $req->status = $status;
            $req->save();
        }
        return $req;
    }
}
