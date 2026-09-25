<?php

namespace Mg\Pdv;

use Carbon\Carbon;
use Exception;

use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioVale;
use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloService;

/**
 * O credito do vale compras vendido dentro do negocio.
 *
 * O saldo continua sendo um titulo tipo 3 (Vale Compras) a credito, conta
 * contabil 83, em nome do favorecido (decisao 1 do plano). Nada do resgate
 * muda: quem bipa o "VAL{codtitulo}" no wizard Receber continua achando o
 * mesmo titulo que sempre achou, e os ~766 papeis ja' impressos seguem
 * valendo.
 *
 * O titulo fica SOLTO -- so' referenciado por tblnegociovale.codtitulo,
 * nunca pendurado em tblnegocioformapagamento. O motivo e' concreto:
 * PdvNegocioService::negocioFechado() reescreve codpessoa, codtipotitulo e
 * codcontacontabil de TODO titulo pendurado num pagamento, a cada PUT de
 * negocio fechado. Um simples "trocar o cliente do negocio" transformaria o
 * credito da escola num titulo do cliente, com outro tipo e outra conta.
 */
class PdvNegocioValeService
{
    // Conta contabil "Credito Vale" -- a mesma que o MGLara usa desde 2015.
    const CODCONTACONTABIL_CREDITO_VALE = 83;

    // Vales ativos do negocio, na mesma ordem que a tela nomeia "Vale A",
    // "Vale B" (o PDV ordena por criacao, e o codnegociovale segue a
    // insercao).
    public static function valesAtivos(Negocio $negocio)
    {
        return $negocio->NegocioValeS()
            ->whereNull('inativo')
            ->orderBy('codnegociovale')
            ->get();
    }

    /**
     * Validacoes de fechamento especificas do vale.
     *
     * Negocio sem vale passa reto por aqui.
     */
    public static function validarFechamento(Negocio $negocio)
    {
        $vales = static::valesAtivos($negocio);
        if ($vales->isEmpty()) {
            return;
        }

        // Sem financeiro nao existe titulo, e sem titulo nao existe credito:
        // o cliente pagaria o vale e nao levaria nada.
        if (!$negocio->NaturezaOperacao->financeiro) {
            throw new Exception('A Natureza de Operação deste negócio não gera financeiro, então não é possível emitir o crédito do Vale Compras!', 1);
        }

        foreach ($vales as $i => $vale) {
            if ($vale->valorvale <= 0) {
                $letra = static::letra($i);
                throw new Exception("O Vale {$letra} está zerado! Informe os itens ou o valor avulso, ou exclua o vale.", 1);
            }
        }
    }

    /**
     * Emite o credito de cada vale do negocio.
     *
     * Roda DENTRO da transacao do fechar(). Idempotente por construcao: so'
     * emite para vale com codtitulo nulo, e o unique index
     * unq_tblnegociovale_codtitulo e' a rede se dois fechamentos correrem
     * juntos.
     *
     * O valor do credito e' a FACE (valorvale), nunca a fatia paga
     * (valortotal): o desconto de cabecalho e' negociacao com o cliente, a
     * escola recebe o que esta' escrito no papel (decisao 5c).
     */
    public static function emitirCreditos(Negocio $negocio)
    {
        foreach (static::valesAtivos($negocio) as $i => $vale) {
            if (!empty($vale->codtitulo)) {
                continue;
            }
            $vale->codtitulo = static::emitirCredito($negocio, $vale, static::letra($i))->codtitulo;
            $vale->save();
        }
    }

    public static function emitirCredito(Negocio $negocio, NegocioVale $vale, $letra)
    {
        $emissao = Carbon::now();

        $titulo = new Titulo();
        $titulo->codfilial = $negocio->codfilial;
        $titulo->codtipotitulo = TituloService::TIPO_VALE;
        $titulo->codcontacontabil = static::CODCONTACONTABIL_CREDITO_VALE;
        // o credito e' da ESCOLA (ou do Consumidor, no vale ao portador),
        // nao de quem pagou
        $titulo->codpessoa = $vale->codpessoafavorecido;
        $titulo->credito = $vale->valorvale;
        $titulo->numero = 'N' . str_pad($negocio->codnegocio, 8, '0', STR_PAD_LEFT) . "-VAL{$letra}";
        $titulo->emissao = $emissao;
        $titulo->transacao = $emissao;
        $titulo->sistema = $emissao;
        // validade de 1 ano, informativa (decisao 8): sem job de expiracao,
        // o saldo continua resgatavel depois da data
        $titulo->vencimento = $vale->validade ?? (clone $emissao)->addYear();
        $titulo->vencimentooriginal = $titulo->vencimento;
        $titulo->boleto = false;
        $titulo->gerencial = true;
        $titulo->codportador = Portador::CARTEIRA;
        $titulo->save();

        return $titulo;
    }

    /**
     * O negocio pode ser cancelado do ponto de vista do vale?
     *
     * Roda ANTES de qualquer estorno, para quem cancela receber a mensagem
     * sem que meio cancelamento tenha sido tentado. Vale ja' usado nao
     * volta: o credito virou mercadoria na mao de alguem.
     */
    public static function validarCancelamento(Negocio $negocio)
    {
        foreach (static::valesAtivos($negocio) as $i => $vale) {
            $titulo = $vale->Titulo;
            if (empty($titulo) || !empty($titulo->estornado)) {
                continue;
            }
            if (static::foiMovimentado($titulo)) {
                $letra = static::letra($i);
                $usado = formataNumero(abs((float) $titulo->credito) - abs((float) $titulo->saldo), 2);
                throw new Exception("O Vale {$letra} (#{$titulo->codtitulo}) já foi usado em compras: R$ {$usado} do crédito já saiu. Impossível cancelar este negócio!", 1);
            }
        }
    }

    /**
     * Estorna os creditos dos vales no cancelamento do negocio.
     *
     * O titulo do vale e' solto, entao o loop de
     * PdvNegocioService::cancelar() -- que varre os titulos pendurados nos
     * pagamentos -- nunca o alcanca. Sem este metodo, cancelar o negocio
     * deixaria o credito vivo: a escola ficaria com um vale que ninguem
     * pagou.
     */
    public static function estornarCreditos(Negocio $negocio)
    {
        static::validarCancelamento($negocio);

        foreach (static::valesAtivos($negocio) as $vale) {
            $titulo = $vale->Titulo;
            if (empty($titulo) || !empty($titulo->estornado)) {
                continue;
            }
            TituloService::estornar($titulo);
        }
    }

    // Titulo movimentado = vale ja' resgatado, no todo ou em parte. Mesma
    // conta que o TituloService::estornar() faz antes de deixar estornar.
    public static function foiMovimentado(Titulo $titulo)
    {
        return round((float) $titulo->debito - (float) $titulo->credito, 2) != round((float) $titulo->saldo, 2);
    }

    // "Vale A", "Vale B", ... e depois de Z cai no numero, que e' melhor do
    // que repetir letra num negocio absurdo com 27 vales.
    public static function letra($i)
    {
        return $i < 26 ? chr(ord('A') + $i) : (string) ($i + 1);
    }
}
