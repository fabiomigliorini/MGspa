<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Mg\Portador\Portador;
use Mg\Usuario\UsuarioService;

// Quem ve, cria e estorna recebimento/pagamento de titulo no contas
// (M6 doc-3, as regras da antiga liquidacao): Admin/Financeiro/Cobranca em
// tudo; Gerente e Caixa pela filial do pagamento (a do portador).
class PagamentoTituloAutorizador
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

    public static function filial(Pagamento $pag): ?int
    {
        return $pag->portadorDoPagamento()->codfilial ?? $pag->codfilial;
    }

    public static function podeVer(Pagamento $pag, int $codusuario): bool
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return true;
        }
        $codfilial = self::filial($pag);
        if (!$codfilial) {
            return false;
        }
        return in_array((int)$codfilial, self::filiaisRestritas($codusuario), true);
    }

    // encontro de contas sem dinheiro (sem portador) so' para irrestritos
    public static function podeCriar(int $codusuario, ?int $codportador): bool
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return true;
        }
        $portador = $codportador ? Portador::find($codportador) : null;
        if (!$portador || !$portador->codfilial) {
            return false;
        }
        return in_array((int)$portador->codfilial, self::filiaisRestritas($codusuario), true);
    }

    /**
     * Retorna null se autorizado, ou string com mensagem de erro.
     * $acao usado apenas para compor as mensagens (ex: 'estornar', 'editar').
     */
    public static function motivoBloqueioMutacao(Pagamento $pag, int $codusuario, string $acao = 'alterar'): ?string
    {
        if (self::temAcessoIrrestrito($codusuario)) {
            return null;
        }
        $codfilialPortador = (int)self::filial($pag);

        if (UsuarioService::temGrupo($codusuario, 'Gerente')) {
            $filiaisGerente = UsuarioService::filiaisDoUsuarioNoGrupo($codusuario, 'Gerente');
            if (in_array($codfilialPortador, $filiaisGerente, true)) {
                return null;
            }
        }

        if (UsuarioService::temGrupo($codusuario, 'Caixa')) {
            if ((int)$pag->codusuariocriacao !== $codusuario) {
                return "Caixa só pode {$acao} seus próprios recebimentos e pagamentos.";
            }
            $minutos = Carbon::parse($pag->criacao)->diffInMinutes(Carbon::now());
            if ($minutos > self::JANELA_CAIXA_MIN) {
                return "Caixa só pode {$acao} seus próprios recebimentos e pagamentos nas primeiras 2 horas.";
            }
            return null;
        }

        return 'Pagamento não pertence à sua filial.';
    }

    public static function motivoBloqueioEstorno(Pagamento $pag, int $codusuario): ?string
    {
        return self::motivoBloqueioMutacao($pag, $codusuario, 'estornar');
    }

    public static function motivoBloqueioEdicao(Pagamento $pag, int $codusuario): ?string
    {
        return self::motivoBloqueioMutacao($pag, $codusuario, 'editar');
    }
}
