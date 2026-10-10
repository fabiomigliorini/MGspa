<?php

namespace Mg\Portador;

use Illuminate\Support\Facades\Auth;
use Mg\Pdv\Pdv;
use Mg\Usuario\Autorizador;

/**
 * O papel do usuario em cada portador (tblportadorusuario; doc-4,
 * redefinicao do dinheiro). Administrador e' gestor em todos. Cada papel
 * inclui o de baixo: depositante < operador < gestor.
 *   ver e movimentar (ajuste, transferir saindo, abrir, contar, fechar): operador
 *   ser destino de transferencia: depositante
 *   confirmar transferencia chegando, reabrir, datas, dividir, unificar,
 *   a lista de usuarios: gestor
 * O PDV tambem passa por aqui: o caixa precisa do papel na gaveta.
 */
class PortadorAutorizador
{
    const NIVEL = [
        PortadorUsuario::PAPEL_DEPOSITANTE => 1,
        PortadorUsuario::PAPEL_OPERADOR => 2,
        PortadorUsuario::PAPEL_GESTOR => 3,
    ];

    // {codusuario: {codportador: papel}} lembrado no request
    private static array $papeis = [];
    private static array $admin = [];

    private static function codusuario(?int $codusuario): ?int
    {
        return $codusuario ?? (Auth::user()->codusuario ?? null);
    }

    // Era a gaveta do PDV que pedia (codpdv no request), livre de papel. Saiu
    // (Fabio, 09/10/2026): toda permissao e' o papel do usuario no portador; o
    // PDV so' pre-seleciona a gaveta. Fica null para os chamadores.
    public static function livre(): ?int
    {
        return null;
    }

    public static function admin(?int $codusuario = null): bool
    {
        $codusuario = static::codusuario($codusuario);
        if (!$codusuario) {
            return false;
        }
        return static::$admin[$codusuario] ??= Autorizador::pode([], null, $codusuario);
    }

    // {codportador: papel} do usuario (sem o admin)
    public static function papeis(?int $codusuario = null): array
    {
        $codusuario = static::codusuario($codusuario);
        if (!$codusuario) {
            return [];
        }
        return static::$papeis[$codusuario] ??= PortadorUsuario::where('codusuario', $codusuario)
            ->pluck('papel', 'codportador')
            ->mapWithKeys(fn ($p, $c) => [(int) $c => $p])
            ->all();
    }

    // depois de mexer na lista
    public static function esquecer(): void
    {
        static::$papeis = [];
    }

    public static function papel(int $codportador, ?int $codusuario = null): ?string
    {
        if (static::admin($codusuario)) {
            return PortadorUsuario::PAPEL_GESTOR;
        }
        return static::papeis($codusuario)[$codportador] ?? null;
    }

    public static function pode(int $codportador, string $papel, ?int $codusuario = null): bool
    {
        $meu = static::papel($codportador, $codusuario);
        return $meu !== null && static::NIVEL[$meu] >= static::NIVEL[$papel];
    }

    public static function autorizar(Portador $portador, string $papel, string $acao): void
    {
        if (!static::pode($portador->codportador, $papel)) {
            abort(403, "{$acao} em {$portador->portador}: só "
                . mb_strtolower(PortadorUsuario::PAPEIS[$papel])
                . ($papel == PortadorUsuario::PAPEL_GESTOR ? '' : ' ou gestor')
                . ' do portador.');
        }
    }

    // ver o portador (painel, tela): operador
    public static function podeVer(Portador $portador): bool
    {
        return static::pode($portador->codportador, PortadorUsuario::PAPEL_OPERADOR);
    }

    // os codportador em que o usuario tem pelo menos o papel; null = todos
    // (Administrador)
    public static function codportadores(string $papel = PortadorUsuario::PAPEL_OPERADOR): ?array
    {
        if (static::admin()) {
            return null;
        }
        return array_keys(array_filter(
            static::papeis(),
            fn ($p) => static::NIVEL[$p] >= static::NIVEL[$papel]
        ));
    }
}
