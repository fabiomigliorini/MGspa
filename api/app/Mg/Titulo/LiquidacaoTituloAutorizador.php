<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Mg\Portador\Portador;
use Mg\Usuario\UsuarioService;

class LiquidacaoTituloAutorizador
{
    private const GRUPOS_IRRESTRITOS = ['Administrador', 'Financeiro', 'Cobranca'];
    private const JANELA_CAIXA_MIN = 120;

    public static function temAcessoIrrestrito(int $codusuario): bool
    {
        foreach (self::GRUPOS_IRRESTRITOS as $g) {
            if (UsuarioService::temGrupo($codusuario, $g)) {
                return true;
            }
        }
        return false;
    }

    public static function filiaisRestritas(int $codusuario): ?array
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return null;
        }
        $caixa = UsuarioService::filiaisDoUsuarioNoGrupo($codusuario, 'Caixa');
        $gerente = UsuarioService::filiaisDoUsuarioNoGrupo($codusuario, 'Gerente');
        return array_values(array_unique(array_merge($caixa, $gerente)));
    }

    public static function podeVer(LiquidacaoTitulo $liq, int $codusuario): bool
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return true;
        }
        $codfilialPortador = optional($liq->Portador)->codfilial;
        if (!$codfilialPortador) {
            return false;
        }
        return in_array((int)$codfilialPortador, self::filiaisRestritas($codusuario), true);
    }

    public static function podeCriar(int $codusuario, int $codportador): bool
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return true;
        }
        $portador = Portador::find($codportador);
        if (!$portador || !$portador->codfilial) {
            return false;
        }
        return in_array((int)$portador->codfilial, self::filiaisRestritas($codusuario), true);
    }

    /**
     * Retorna null se autorizado, ou string com mensagem de erro.
     * $acao usado apenas para compor as mensagens (ex: 'estornar', 'editar').
     */
    public static function motivoBloqueioMutacao(LiquidacaoTitulo $liq, int $codusuario, string $acao = 'alterar'): ?string
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return null;
        }
        $codfilialPortador = (int)optional($liq->Portador)->codfilial;

        if (UsuarioService::temGrupo($codusuario, 'Gerente')) {
            $filiaisGerente = UsuarioService::filiaisDoUsuarioNoGrupo($codusuario, 'Gerente');
            if (in_array($codfilialPortador, $filiaisGerente, true)) {
                return null;
            }
        }

        if (UsuarioService::temGrupo($codusuario, 'Caixa')) {
            if ((int)$liq->codusuariocriacao !== $codusuario) {
                return "Caixa só pode {$acao} suas próprias liquidações.";
            }
            $minutos = Carbon::parse($liq->criacao)->diffInMinutes(Carbon::now());
            if ($minutos > self::JANELA_CAIXA_MIN) {
                return "Caixa só pode {$acao} suas próprias liquidações nas primeiras 2 horas.";
            }
            return null;
        }

        return 'Liquidação não pertence à sua filial.';
    }

    public static function motivoBloqueioEstorno(LiquidacaoTitulo $liq, int $codusuario): ?string
    {
        return self::motivoBloqueioMutacao($liq, $codusuario, 'estornar');
    }

    public static function motivoBloqueioEdicao(LiquidacaoTitulo $liq, int $codusuario): ?string
    {
        return self::motivoBloqueioMutacao($liq, $codusuario, 'editar');
    }
}
