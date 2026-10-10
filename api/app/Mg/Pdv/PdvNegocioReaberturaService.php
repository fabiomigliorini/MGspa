<?php

namespace Mg\Pdv;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Mg\Auditoria\AuditoriaService;
use Mg\Cheque\Cheque;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Conferencia\ConferenciaService;
use Mg\Conferencia\PagamentoCorrecaoService;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioParcela;
use Mg\Negocio\NegocioService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Portador\Portador;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorMovimentoService;
use Mg\Titulo\TituloService;

/**
 * Venda fechada reaberta pelo gerente (TASK-30). Sem status novo: a reaberta
 * e' a venda aberta com `reabertura` preenchida, e o F3 mantem lancamento,
 * codusuario e codpdv.
 *
 * O estado e' a verdade e o fechar reconcilia: a sincronizacao do PDV so'
 * troca o estado do pagamento (E/P -> C, C -> P) e da parcela (inativo); o
 * PdvNegocioService::fechar desfaz aqui o que foi cancelado (razao, lote,
 * baixa do vale, cheque, titulo) e efetiva o que e' novo. Tudo idempotente:
 * reabrir e fechar sem mexer nao muda nada.
 */
class PdvNegocioReaberturaService
{
    public static function reaberto(Negocio $negocio): bool
    {
        return $negocio->codnegociostatus == NegocioService::STATUS_ABERTO
            && !empty($negocio->reabertura);
    }

    // so' reabre; nao estorna nada (quem desfaz e' o F3)
    public static function reabrir(Negocio $negocio): Negocio
    {
        $negocio = Negocio::lockForUpdate()->findOrFail($negocio->codnegocio);
        if ($negocio->codnegociostatus != NegocioService::STATUS_FECHADO) {
            abort(422, 'Só negócio fechado pode ser reaberto!');
        }
        if ($negocio->NegocioValeS()->whereNotNull('codvalecompra')->exists()) {
            abort(422, 'Este negócio é um Vale Compras convertido do sistema antigo e não pode ser alterado!');
        }
        ConferenciaAutorizador::autorizar($negocio->codfilial);

        $negocio->codnegociostatus = NegocioService::STATUS_ABERTO;
        $negocio->reabertura = Carbon::now();
        $negocio->save();
        AuditoriaService::registrar(
            'tblnegocio',
            $negocio->codnegocio,
            AuditoriaService::TIPO_NEGOCIO_REABERTO,
            ['codnegociostatus' => NegocioService::STATUS_FECHADO],
            ['codnegociostatus' => NegocioService::STATUS_ABERTO]
        );
        return $negocio;
    }

    // ---- na sincronizacao: so' o estado muda ----

    // O pagamento manual que ja' existe muda de estado pelo PDV: Cancelar
    // (E/P -> C) e Reativar (C -> P). Valor nao muda. True quando o
    // pagamento ja' foi tratado aqui (o importar nao mexe mais nele); o P
    // sem troca segue o caminho do rascunho.
    public static function trocarEstado(Negocio $negocio, Pagamento $pag, ?string $novo): bool
    {
        if ($pag->ehIntegrado()) {
            return false;
        }
        $atual = $pag->estado;
        if (empty($novo) || $novo == $atual) {
            return $atual != PagamentoService::ESTADO_PENDENTE;
        }
        if ($novo == PagamentoService::ESTADO_CANCELADO) {
            $pag->estado = PagamentoService::ESTADO_CANCELADO;
            $pag->cancelamento = Carbon::now();
            $pag->codusuariocancelamento = Auth::user()->codusuario ?? null;
            $pag->justificativa = "Cancelado na reabertura da venda #{$negocio->codnegocio}";
            // registro indevido: o cartao sai do lote como se nunca tivesse
            // entrado (no F3)
            $pag->indevido = true;
            $pag->save();
            static::auditarEstado($pag, $atual, AuditoriaService::TIPO_PAGAMENTO_CANCELADO_REABERTURA);
            return true;
        }
        if ($novo == PagamentoService::ESTADO_PENDENTE && $atual == PagamentoService::ESTADO_CANCELADO) {
            $pag->estado = PagamentoService::ESTADO_PENDENTE;
            $pag->cancelamento = null;
            $pag->codusuariocancelamento = null;
            $pag->justificativa = null;
            $pag->indevido = false;
            $pag->save();
            static::auditarEstado($pag, $atual, AuditoriaService::TIPO_PAGAMENTO_REATIVADO);
            return true;
        }
        // E -> P nao existe: o efetivado so' sai cancelando
        return $atual != PagamentoService::ESTADO_PENDENTE;
    }

    private static function auditarEstado(Pagamento $pag, string $antes, int $tipo): void
    {
        AuditoriaService::registrar(
            'tblpagamento',
            $pag->codpagamento,
            $tipo,
            ['estado' => $antes],
            ['estado' => $pag->estado]
        );
    }

    // A parcela que ja' virou titulo sai (inativo) e volta pelo PDV
    public static function trocarInativo(NegocioParcela $np, ?string $inativo): void
    {
        $sai = !empty($inativo);
        if ($sai == !empty($np->inativo)) {
            return;
        }
        $antes = $np->getRawOriginal('inativo');
        $np->inativo = $sai ? Carbon::now() : null;
        $np->save();
        AuditoriaService::registrar(
            'tblnegocioparcela',
            $np->codnegocioparcela,
            $sai ? AuditoriaService::TIPO_PARCELA_INATIVADA_REABERTURA : AuditoriaService::TIPO_PARCELA_REATIVADA,
            ['inativo' => $antes],
            ['inativo' => $np->getRawOriginal('inativo')]
        );
    }

    // Onde o dinheiro da venda entrou: o portador do dinheiro que ela ja'
    // tem; sem dinheiro, a gaveta do PDV da venda. O dinheiro que entra ou
    // sai na reaberta vai para la', na data da venda.
    public static function portadorDinheiro(Negocio $negocio): ?int
    {
        $cod = $negocio->PagamentoS()
            ->where('meio', PagamentoService::MEIO_DINHEIRO)
            ->whereNotNull('codportadordestino')
            ->orderBy('codpagamento')
            ->value('codportadordestino');
        if ($cod) {
            return (int) $cod;
        }
        $gaveta = optional($negocio->Pdv)->Portador;
        if (!$gaveta || $gaveta->tipo !== Portador::TIPO_ESPECIE) {
            return null;
        }
        return $gaveta->codportador;
    }

    // ---- no F3: o que foi cancelado sai dos outros lugares ----

    public static function desfazerCancelados(Negocio $negocio): void
    {
        $cancelados = $negocio->PagamentoS()
            ->where('estado', PagamentoService::ESTADO_CANCELADO)
            ->where('indevido', true)
            ->orderBy('codpagamento')
            ->get();
        foreach ($cancelados as $pag) {
            static::desfazerPagamento($pag);
        }
        static::estornarParcelasInativas($negocio);
    }

    // Idempotente: o C ja' desfeito (sem efeito vivo) passa reto, mesmo que
    // o periodo dele tenha fechado depois
    public static function desfazerPagamento(Pagamento $pag): void
    {
        if ($pag->ehIntegrado() || !static::efeitoVivo($pag)) {
            return;
        }
        // lote conferido, sessao fechada ou pagamento conferido: reabra antes
        PagamentoCorrecaoService::exigirAberta($pag);
        ConferenciaService::vincular($pag);
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);
        PdvNegocioPrazoService::estornarBaixaVale($pag);
        PdvNegocioChequeService::cancelarDoPagamento($pag);
    }

    // o C ainda esta' em algum lugar: razao, lote, baixa do vale ou cheque
    private static function efeitoVivo(Pagamento $pag): bool
    {
        return PortadorMovimento::where('codpagamento', $pag->codpagamento)
                ->where('tipo', PortadorMovimento::TIPO_PAGAMENTO)
                ->whereNull('inativo')
                ->exists()
            || !empty($pag->codmaquinetalote)
            || PdvNegocioPrazoService::amortizacaoAtiva($pag) !== null
            || Cheque::where('codpagamento', $pag->codpagamento)->whereNull('cancelamento')->exists();
    }

    // titulo da parcela inativa: estorna (o movimentado recusa, com o numero)
    public static function estornarParcelasInativas(Negocio $negocio): void
    {
        $inativas = $negocio->NegocioParcelaTodasS()
            ->whereNotNull('inativo')
            ->whereNotNull('codtitulo')
            ->orderBy('codnegocioparcela')
            ->get();
        foreach ($inativas as $np) {
            $titulo = $np->Titulo;
            if (!$titulo || !empty($titulo->estornado)) {
                continue;
            }
            if (round((float) $titulo->valor, 2) != round((float) $titulo->saldo, 2)) {
                abort(422, "O título {$titulo->numero} já foi movimentado: estorne a baixa no Contas antes de tirar a parcela.");
            }
            TituloService::estornar($titulo, "Parcela tirada na reabertura da venda #{$negocio->codnegocio}");
        }
    }

    // Pagamento novo (ou reativado) da reaberta, ao efetivar no F3: o cartao
    // vai para o periodo da maquineta da data dele; conferido recusa
    // (TASK-204: a data manda no periodo)
    public static function loteDaData(Pagamento $pag): void
    {
        if (!in_array($pag->meio, PagamentoService::MEIOS_CARTAO) || empty($pag->codmaquineta)) {
            return;
        }
        if (!empty($pag->codmaquinetalote)) {
            return;
        }
        $pag->codmaquinetalote = MaquinetaLoteService::daData(
            $pag->codmaquineta,
            Carbon::parse($pag->transacao)
        )->codmaquinetalote;
    }
}
