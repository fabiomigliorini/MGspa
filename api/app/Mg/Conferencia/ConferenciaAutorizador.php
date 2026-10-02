<?php

namespace Mg\Conferencia;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

/**
 * Quem confere o que o caixa movimentou (M9 doc-3): Financeiro e
 * Administrador em todas as filiais; Gerente na filial dele.
 */
class ConferenciaAutorizador
{
    public static function irrestrito(): bool
    {
        return Autorizador::pode(['Financeiro']);
    }

    public static function pode(?int $codfilial): bool
    {
        if (static::irrestrito()) {
            return true;
        }
        return !empty($codfilial) && Autorizador::pode(['Gerente'], $codfilial);
    }

    public static function autorizar(?int $codfilial): void
    {
        if (!static::pode($codfilial)) {
            abort(403, 'Só Financeiro, Administrador ou Gerente da filial!');
        }
    }

    // maquineta compartilhada aparece em todas as filiais: qualquer gerente
    // confere
    public static function autorizarMaquineta(\Mg\Maquineta\Maquineta $maquineta): void
    {
        if (!$maquineta->compartilhada) {
            static::autorizar($maquineta->codfilial);
            return;
        }
        if (static::filiais() === []) {
            abort(403, 'Só Financeiro, Administrador ou Gerente!');
        }
    }

    // filiais em que o usuario confere; null = todas
    public static function filiais(): ?array
    {
        if (static::irrestrito()) {
            return null;
        }
        $regs = DB::select("
            select distinct guu.codfilial
            from tblgrupousuariousuario guu
            inner join tblgrupousuario gu on (gu.codgrupousuario = guu.codgrupousuario)
            where guu.codusuario = :codusuario
            and gu.grupousuario = 'Gerente'
        ", ['codusuario' => Auth::user()->codusuario]);
        return array_map(fn ($r) => (int) $r->codfilial, $regs);
    }
}
