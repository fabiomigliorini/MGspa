<?php

namespace Mg\Pagamento;

use Mg\Portador\Portador;
use Mg\Portador\PortadorAutorizador;
use Mg\Portador\PortadorUsuario;
use Mg\Usuario\UsuarioService;

// Quem ve e quem mexe em pagamento de titulo, vale e adiantamento (contas e
// PDV). Mexer no dinheiro e' pelo PAPEL DO USUARIO NO PORTADOR (conceito do
// Fabio, 09/10/2026; doc-4): o dinheiro entra num portador = depositante;
// sai, desamarra, cancela ou corrige = operador; alterar data = gestor (no
// alterar data). O PDV so' pre-seleciona o portador dele, nao da' permissao.
// Ver a listagem continua pela filial (Admin/Financeiro/Cobranca em tudo;
// Gerente e Caixa nas filiais deles).
class PagamentoTituloAutorizador
{
    private const GRUPOS_IRRESTRITOS = ['Administrador', 'Financeiro', 'Cobranca'];

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
        // transferencia (M11): a filial de qualquer dos dois lados
        $filiais = array_filter([
            self::filial($pag),
            optional($pag->PortadorOrigem)->codfilial,
        ]);
        if (empty($filiais)) {
            return false;
        }
        return !empty(array_intersect(array_map('intval', $filiais), self::filiaisRestritas($codusuario)));
    }

    // o papel que o usuario precisa ter no portador, ou o motivo de nao poder
    public static function motivoPapel(int $codusuario, ?Portador $portador, string $papel, string $acao): ?string
    {
        if (!$portador) {
            return null;
        }
        if (PortadorAutorizador::pode($portador->codportador, $papel, $codusuario)) {
            return null;
        }
        return "{$acao} em {$portador->portador}: só "
            . mb_strtolower(PortadorUsuario::PAPEIS[$papel])
            . ($papel == PortadorUsuario::PAPEL_GESTOR ? '' : ' ou acima')
            . ' do portador.';
    }

    // A forma da baixa/vale: entra dinheiro no portador = depositante; sai =
    // operador; encontro de contas e compensacao = operador no Encontro de
    // Contas; pagamento que ja' existe = pelo sentido dele
    public static function motivoBloqueioForma(int $codusuario, array $forma, bool $entrada, ?\Mg\Pdv\Pdv $pdv = null): ?string
    {
        $portador = PagamentoTituloService::portadorPrevisto($forma, $entrada, $pdv);
        $papel = $entrada ? PortadorUsuario::PAPEL_DEPOSITANTE : PortadorUsuario::PAPEL_OPERADOR;
        if ((int) ($forma['meio'] ?? 0) == PagamentoService::MEIO_COMPENSACAO) {
            $papel = PortadorUsuario::PAPEL_OPERADOR;
        }
        return static::motivoPapel($codusuario, $portador, $papel, $entrada ? 'Receber' : 'Pagar');
    }

    // Baixa de titulos (contas e PDV): pela forma; titulos que se anulam =
    // encontro de contas
    public static function motivoBloqueioBaixa(int $codusuario, array $dados, ?\Mg\Pdv\Pdv $pdv = null): ?string
    {
        $liquido = PagamentoTituloService::liquido($dados['titulos'] ?? []);
        $formas = array_values($dados['pagamentos'] ?? []);
        if (abs($liquido) < 0.005 || empty($formas)) {
            return static::motivoPapel(
                $codusuario,
                Portador::find(Portador::ENCONTRO_CONTAS),
                PortadorUsuario::PAPEL_OPERADOR,
                'Encontro de contas'
            );
        }
        foreach ($formas as $f) {
            $motivo = static::motivoBloqueioForma($codusuario, $f, $liquido < 0, $pdv);
            if ($motivo !== null) {
                return $motivo;
            }
        }
        return null;
    }

    // Vale/adiantamento (contas e PDV): pela forma, no sentido do tipo
    public static function motivoBloqueioAdiantamento(int $codusuario, array $dados, ?\Mg\Pdv\Pdv $pdv = null): ?string
    {
        $tipo = \Mg\Titulo\TipoTitulo::find((int) ($dados['codtipotitulo'] ?? 0));
        $entrada = $tipo ? !$tipo->ehReceber() : true;
        foreach (array_values($dados['pagamentos'] ?? []) as $f) {
            $motivo = static::motivoBloqueioForma($codusuario, $f, $entrada, $pdv);
            if ($motivo !== null) {
                return $motivo;
            }
        }
        return null;
    }

    // Desamarrar, cancelar, corrigir pelo lapis, devolver: operador do
    // portador do pagamento (o encontro de contas antigo, sem portador, e' do
    // Encontro de Contas)
    public static function motivoBloqueioMutacao(Pagamento $pag, int $codusuario, string $acao = 'Alterar'): ?string
    {
        $portador = $pag->portadorDoPagamento() ?? Portador::find(Portador::ENCONTRO_CONTAS);
        return static::motivoPapel($codusuario, $portador, PortadorUsuario::PAPEL_OPERADOR, $acao);
    }

    public static function motivoBloqueioEstorno(Pagamento $pag, int $codusuario): ?string
    {
        return self::motivoBloqueioMutacao($pag, $codusuario, 'Desamarrar ou cancelar');
    }

    public static function motivoBloqueioEdicao(Pagamento $pag, int $codusuario): ?string
    {
        return self::motivoBloqueioMutacao($pag, $codusuario, 'Corrigir');
    }
}
