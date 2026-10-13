<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

/**
 * Quem mexe nos dispositivos (TASK-46): Administrador em todas as filiais;
 * Gerente na filial dele. A configuracao (impressora, maquineta...) o
 * proprio PDV tambem altera. Ativar/inativar continua so' Administrador.
 */
class PdvAutorizador
{
    public static function pode(?int $codfilial): bool
    {
        if (Autorizador::pode([])) {
            return true;
        }
        return !empty($codfilial) && Autorizador::pode(['Gerente'], $codfilial);
    }

    public static function autorizar(?int $codfilial): void
    {
        if (!static::pode($codfilial)) {
            abort(403, 'Só Administrador ou Gerente da filial do dispositivo!');
        }
    }

    // a requisicao vem do proprio dispositivo, e ele esta ativo
    public static function proprio(Pdv $pdv, ?string $uuid): bool
    {
        if (empty($uuid) || $uuid !== $pdv->uuid) {
            return false;
        }
        return (bool) PdvService::podeAcessar($uuid);
    }

    public static function autorizarProprio(Pdv $pdv, ?string $uuid): void
    {
        if (static::proprio($pdv, $uuid)) {
            return;
        }
        static::autorizar($pdv->codfilial);
    }

    // filiais cujos dispositivos o usuario ve; null = todas
    public static function filiais(): ?array
    {
        if (Autorizador::pode([])) {
            return null;
        }
        $regs = DB::select("
            select distinct guu.codfilial
            from tblgrupousuariousuario guu
            inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
            where guu.codusuario = :codusuario
            and gu.grupousuario = 'Gerente'
            and guu.codfilial is not null
        ", ['codusuario' => Auth::user()->codusuario]);
        return array_map(fn ($r) => (int) $r->codfilial, $regs);
    }
}
