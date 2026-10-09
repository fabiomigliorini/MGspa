<?php

namespace Mg\Conferencia;

use Carbon\Carbon;
use Mg\Caixa\CaixaService;
use Mg\Maquineta\Maquineta;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Negocio\Negocio;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoCorrecao;
use Mg\Pagamento\PagamentoService;
use Mg\Portador\PortadorMovimentoService;
use Mg\Portador\PortadorPeriodo;

/**
 * Correcao dos lancamentos na conferencia (M9 doc-3): o gerente acerta o
 * pagamento com a realidade (o caixa errou, a API da maquineta duplicou ou
 * mandou valor errado). Justificativa obrigatoria e o antes/depois na
 * tblpagamentocorrecao. Total da venda, itens e nota nao mudam: a venda que
 * ficar desbalanceada vira pendencia (VendaConferenciaService).
 */
class PagamentoCorrecaoService
{
    const CAMPOS = [
        'meio', 'principal', 'total', 'codmaquineta', 'codmaquinetalote', 'bandeira',
        'autorizacao', 'parcelas', 'codportadordestino', 'codportadorperiodo', 'estado', 'indevido',
    ];

    // meios que a correcao troca entre si (dinheiro e cartao)
    const MEIOS = [PagamentoService::MEIO_DINHEIRO, PagamentoService::MEIO_CREDITO, PagamentoService::MEIO_DEBITO];

    private static function foto(Pagamento $pag): array
    {
        $ret = [];
        foreach (static::CAMPOS as $c) {
            $ret[$c] = $pag->$c;
        }
        return $ret;
    }

    private static function registrar(Pagamento $pag, array $antes, string $justificativa): void
    {
        PagamentoCorrecao::create([
            'codpagamento' => $pag->codpagamento,
            'antes' => $antes,
            'depois' => static::foto($pag),
            'justificativa' => mb_substr($justificativa, 0, 300),
        ]);
    }

    private static function justificativa(?string $justificativa): string
    {
        $justificativa = trim($justificativa ?? '');
        if (mb_strlen($justificativa) < 5) {
            abort(422, 'Informe a justificativa da correção.');
        }
        return $justificativa;
    }

    public static function autorizar(Pagamento $pag): void
    {
        ConferenciaAutorizador::autorizar(ConferenciaService::filialDoPagamento($pag));
    }

    // a conferencia em que o pagamento esta' tem de estar aberta
    public static function exigirAberta(Pagamento $pag): void
    {
        if (!empty($pag->codmaquinetalote)) {
            MaquinetaLoteService::exigirNaoConferido($pag->MaquinetaLote);
        }
        if (!empty($pag->codportadorperiodo) && !empty($pag->PortadorPeriodo->fechamento)) {
            abort(422, 'O caixa deste dinheiro já foi fechado: reabra a sessão antes.');
        }
        if (!empty($pag->conferencia)) {
            abort(422, 'Este pagamento já foi conferido: reabra a conferência antes.');
        }
    }

    // valor e meio so' mudam em pagamento de venda ou avulso (o de titulo
    // tem as linhas do movimento: estorna e lanca de novo)
    private static function exigirSemTitulo(Pagamento $pag): void
    {
        if ($pag->MovimentoTituloS()->exists()) {
            abort(422, 'Pagamento de título: estorne pela listagem de pagamentos e lance de novo.');
        }
    }

    // sessao para o dinheiro da correcao: a do momento do pagamento, na
    // gaveta do PDV, ainda nao conferida
    private static function sessaoDoDinheiro(Pagamento $pag): PortadorPeriodo
    {
        $pdv = $pag->Pdv ?? optional($pag->Negocio)->Pdv;
        if (!$pdv || empty($pdv->codportador)) {
            abort(422, 'Dinheiro só em pagamento feito num PDV com gaveta.');
        }
        $sessao = CaixaService::sessaoDe($pdv->codportador, $pag->transacao);
        if (!$sessao) {
            abort(422, 'Não havia caixa aberto na gaveta do PDV quando o pagamento foi feito.');
        }
        if (!empty($sessao->conferencia)) {
            abort(422, 'O caixa daquele momento já foi fechado: reabra a sessão antes.');
        }
        return $sessao;
    }

    public static function corrigir(Pagamento $pag, array $dados, ?string $justificativa): Pagamento
    {
        $justificativa = static::justificativa($justificativa);
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Pagamento cancelado não se corrige.');
        }
        static::exigirAberta($pag);
        $antes = static::foto($pag);

        $meio = isset($dados['meio']) ? (int) $dados['meio'] : $pag->meio;
        $mudaMeio = $meio != $pag->meio;
        $mudaValor = isset($dados['principal']) && round((float) $dados['principal'], 2) != round((float) $pag->principal, 2);
        if ($mudaMeio || $mudaValor) {
            static::exigirSemTitulo($pag);
        }
        if ($mudaMeio) {
            if (!in_array($meio, static::MEIOS) || !in_array($pag->meio, static::MEIOS)) {
                abort(422, 'A correção troca só entre dinheiro, crédito e débito.');
            }
            if ($meio == PagamentoService::MEIO_DINHEIRO) {
                $sessao = static::sessaoDoDinheiro($pag);
                $pag->codportadordestino = $sessao->codportador;
                $pag->codportadorperiodo = $sessao->codportadorperiodo;
                $pag->codmaquineta = null;
                $pag->bandeira = null;
                $pag->autorizacao = null;
                $pag->parcelas = null;
            } elseif ($pag->meio == PagamentoService::MEIO_DINHEIRO) {
                // era dinheiro de gaveta, virou cartao: sai da gaveta
                $pag->codportadordestino = null;
                $pag->codportadorperiodo = null;
            }
            $pag->meio = $meio;
        }
        $cartao = in_array($pag->meio, PagamentoService::MEIOS_CARTAO);

        if ($cartao) {
            if (array_key_exists('codmaquineta', $dados) && $dados['codmaquineta'] != $pag->codmaquineta) {
                $pag->codmaquineta = Maquineta::findOrFail((int) $dados['codmaquineta'])->codmaquineta;
                $pag->codmaquinetalote = null;
            }
            if (empty($pag->codmaquineta)) {
                abort(422, 'Cartão precisa da maquineta.');
            }
            foreach (['bandeira', 'autorizacao', 'parcelas'] as $c) {
                if (array_key_exists($c, $dados)) {
                    $pag->$c = $dados[$c] === '' ? null : $dados[$c];
                }
            }
        }

        if ($mudaValor) {
            $pag->principal = round((float) $dados['principal'], 2);
            $pag->total = PagamentoService::calcularTotal($pag);
        }
        PagamentoService::validar($pag);
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);
        static::registrar($pag, $antes, $justificativa);
        return $pag->fresh();
    }

    // registro indevido (a duplicata da API, o cartao que nao passou):
    // cancela e sai do lote como se nunca tivesse entrado. Nao e'
    // cancelamento na maquineta.
    public static function indevido(Pagamento $pag, ?string $justificativa): Pagamento
    {
        $justificativa = static::justificativa($justificativa);
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Pagamento já cancelado.');
        }
        static::exigirSemTitulo($pag);
        static::exigirAberta($pag);
        $antes = static::foto($pag);
        $pag->indevido = true;
        PagamentoService::cancelar($pag, "Registro indevido: {$justificativa}");
        static::registrar($pag, $antes, $justificativa);
        return $pag->fresh();
    }

    // o pagamento que o caixa esqueceu de registrar na venda
    public static function incluir(Negocio $negocio, array $dados, ?string $justificativa): Pagamento
    {
        $justificativa = static::justificativa($justificativa);
        if ($negocio->codnegociostatus != 2) {
            abort(422, 'Só em venda fechada.');
        }
        $meio = (int) ($dados['meio'] ?? 0);
        if (!in_array($meio, static::MEIOS)) {
            abort(422, 'Inclua dinheiro, crédito ou débito.');
        }
        $pag = new Pagamento();
        $pag->codnegocio = $negocio->codnegocio;
        $pag->codpdv = $negocio->codpdv;
        $pag->transacao = $negocio->lancamento;
        if ($meio == PagamentoService::MEIO_DINHEIRO) {
            $sessao = static::sessaoDoDinheiro($pag);
            $dados['codportadordestino'] = $sessao->codportador;
            $dados['codportadorperiodo'] = $sessao->codportadorperiodo;
        } elseif (empty($dados['codmaquineta'])) {
            abort(422, 'Cartão precisa da maquineta.');
        }
        PagamentoService::preencher($pag, [
            'codnegocio' => $negocio->codnegocio,
            'codfilial' => $negocio->codfilial,
            'codpdv' => $negocio->codpdv,
            'meio' => $meio,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'efetivacao' => Carbon::now(),
            'transacao' => $negocio->lancamento,
            'principal' => $dados['principal'] ?? 0,
            'codmaquineta' => $dados['codmaquineta'] ?? null,
            'bandeira' => $dados['bandeira'] ?? null,
            'autorizacao' => $dados['autorizacao'] ?? null,
            'parcelas' => $dados['parcelas'] ?? null,
            'codportadordestino' => $dados['codportadordestino'] ?? null,
            'codportadorperiodo' => $dados['codportadorperiodo'] ?? null,
            'observacoes' => mb_substr("Incluído na conferência: {$justificativa}", 0, 300),
        ]);
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);
        if (!empty($pag->codmaquinetalote)) {
            MaquinetaLoteService::exigirNaoConferido($pag->MaquinetaLote);
        }
        static::registrar($pag, [], $justificativa);
        return $pag->fresh();
    }
}
