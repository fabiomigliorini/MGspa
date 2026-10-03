<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaAutorizador;
use Mg\Pdv\Pdv;
use Mg\Portador\Portador;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorMovimentoService;
use Mg\Portador\PortadorPeriodo;
use Mg\Portador\PortadorPeriodoService;
use Mg\Titulo\TituloService;

/**
 * Sessao do caixa (M9 doc-3): todo portador em especie (gaveta, cofre,
 * troco, Caixa Financeiro) abre, so' movimenta aberto e fecha; todo
 * dinheiro que entra ou sai fica na sessao aberta
 * (tblpagamento.codportadorperiodo). A contagem e' a parte
 * (PortadorPeriodoService::contar) e fechar exige a do fechamento batendo
 * com o saldo final; a diferenca o gerente acerta com um lancamento de
 * ajuste. A gaveta (com PDV) tem ainda os itens do caixa.
 */
class CaixaService
{
    public static function gaveta(Pdv $pdv): Portador
    {
        $portador = $pdv->Portador;
        if (!$portador || $portador->tipo !== Portador::TIPO_ESPECIE) {
            abort(422, 'Este PDV não tem gaveta (portador em espécie): peça ao administrador para vincular em Configurações → PDV.');
        }
        return $portador;
    }

    public static function sessaoAberta(int $codportador): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)->whereNull('fim')->first();
    }

    // R12 (doc-4): uma regra so' para movimentar o caixa sem sessao aberta
    public static function naoAberta(Portador $gaveta): void
    {
        abort(422, "Caixa não aberto ({$gaveta->portador}): abra o caixa antes de movimentar.");
    }

    public static function exigirAberta(Portador $gaveta): PortadorPeriodo
    {
        $sessao = static::sessaoAberta($gaveta->codportador);
        if (!$sessao) {
            static::naoAberta($gaveta);
        }
        return $sessao;
    }

    // a sessao do pagamento: a gravada nele ou, no lancado antes de o
    // portador ser caixa (avulso do periodo, M12), a da linha do razao
    public static function sessaoDoPagamento(Pagamento $pag): ?PortadorPeriodo
    {
        if ($pag->PortadorPeriodo) {
            return $pag->PortadorPeriodo;
        }
        $linha = PortadorMovimento::where('codpagamento', $pag->codpagamento)
            ->whereNull('inativo')
            ->with('PortadorPeriodo.Portador')
            ->get()
            ->first(fn ($l) => $l->PortadorPeriodo->Portador->ehCaixa());
        return optional($linha)->PortadorPeriodo;
    }

    public static function ultimaSessao(int $codportador): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
    }

    // o caixa do pagamento: o destino se for caixa, senao a origem. Conta
    // o dinheiro e, desde o M11, a transferencia (os dois lados preenchidos,
    // inclusive deposito da gaveta no banco)
    public static function gavetaDoPagamento(Pagamento $pag): ?Portador
    {
        $transferencia = !empty($pag->codportadororigem) && !empty($pag->codportadordestino);
        if ($pag->meio != PagamentoService::MEIO_DINHEIRO && !$transferencia) {
            return null;
        }
        foreach ([$pag->codportadordestino, $pag->codportadororigem] as $codportador) {
            if (empty($codportador)) {
                continue;
            }
            $portador = Portador::find($codportador);
            if ($portador && $portador->ehCaixa()) {
                return $portador;
            }
        }
        return null;
    }

    // chamado no saving do Pagamento (via ConferenciaService::vincular):
    // dinheiro de gaveta cai na sessao aberta; sem sessao, 422. Na
    // transferencia gaveta -> gaveta fica a sessao do destino; a da origem o
    // razao acha pela data (sessaoDe)
    public static function vincular(Pagamento $pag): void
    {
        $portador = static::gavetaDoPagamento($pag);
        if (!$portador) {
            $pag->codportadorperiodo = null;
            return;
        }
        // a correcao da conferencia escolhe a sessao (a do momento do
        // pagamento, ainda nao conferida)
        if ($pag->isDirty('codportadorperiodo') && !empty($pag->codportadorperiodo)) {
            return;
        }
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            // cancelar dinheiro de sessao que o caixa ja fechou: so' reabrindo
            // (o registro indevido da conferencia passa: a sessao nao foi
            // conferida)
            if ($pag->isDirty('estado') && !$pag->indevido && !empty($pag->codportadorperiodo)) {
                $sessao = PortadorPeriodo::find($pag->codportadorperiodo);
                if ($sessao && !empty($sessao->fechamento)) {
                    abort(422, "O caixa {$portador->portador} daquele dinheiro já foi fechado ({$sessao->fim->format('d/m/Y H:i')}): o gerente precisa reabrir a sessão antes.");
                }
            }
            return;
        }
        if (!empty($pag->codportadorperiodo) && !$pag->isDirty(['codportadordestino', 'codportadororigem', 'meio'])) {
            return;
        }
        $momento = $pag->transacao ? Carbon::parse($pag->transacao) : Carbon::now();
        $pag->codportadorperiodo = static::sessaoDoMomento($portador, $momento)->codportadorperiodo;
    }

    // a sessao do caixa que contem o momento, nao fechada: e' nela que o
    // dinheiro daquela data cai (agora = a aberta)
    public static function sessaoDoMomento(Portador $caixa, Carbon $momento): PortadorPeriodo
    {
        $sessao = static::sessaoDe($caixa->codportador, $momento);
        if (!$sessao) {
            if ($momento->gte(Carbon::now()->subMinute())) {
                static::naoAberta($caixa);
            }
            abort(422, "Não há sessão do caixa {$caixa->portador} em {$momento->format('d/m/Y H:i')}.");
        }
        if (!empty($sessao->fechamento)) {
            abort(422, "A sessão do caixa {$caixa->portador} de {$momento->format('d/m/Y H:i')} já foi fechada: reabra antes de lançar nela.");
        }
        return $sessao;
    }

    // gaveta que recebe o dinheiro do PDV agora: sem gaveta ou com o caixa
    // fechado o Dinheiro fica bloqueado (TASK-188 AC #34)
    public static function gavetaAberta(Pdv $pdv): Portador
    {
        $gaveta = static::gaveta($pdv);
        static::exigirAberta($gaveta);
        return $gaveta;
    }

    // sessao da gaveta em que estava o momento (a do pagamento corrigido)
    public static function sessaoDe(int $codportador, $momento): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)
            ->where('inicio', '<=', $momento)
            ->where(function ($q) use ($momento) {
                $q->whereNull('fim')->orWhere('fim', '>=', $momento);
            })
            ->orderBy('inicio', 'desc')
            ->first();
    }

    // ==== Sessao numa tela so' (M13 doc-3) ====
    //
    // Abre com o saldo final da anterior e a contagem final dela como
    // contagem inicial; fecha quem estiver com o dinheiro, com a contagem
    // final batendo com o saldo final (a diferenca se acerta com um avulso de
    // ajuste) e os titulos de repasse dos itens. Fechado, o razao trava;
    // corrigir depois so' reabrindo.

    // cedulas e moedas da contagem (chave = valor, quantidade no jsonb)
    const CEDULAS = ['200', '100', '50', '20', '10', '5', '2'];
    const MOEDAS = ['1', '0.50', '0.25', '0.10', '0.05', '0.01'];

    // quem opera o caixa: quem opera o portador (gaveta: Caixa ou Gerente da
    // filial; cofre e troco: Gerente; Caixa Financeiro: Financeiro) e quem
    // confere a filial
    public static function podeOperar(Portador $caixa): bool
    {
        return TransferenciaAutorizador::podeOperar($caixa)
            || ConferenciaAutorizador::pode($caixa->codfilial);
    }

    public static function autorizarOperar(Portador $caixa): void
    {
        if (!static::podeOperar($caixa)) {
            abort(403, "Caixa {$caixa->portador}: só " . TransferenciaAutorizador::quemOpera($caixa) . ', quem confere a filial ou Administrador!');
        }
    }

    // o dinheiro de uma contagem {face: quantidade}
    public static function totalContagem(?array $contagem): float
    {
        $total = 0.0;
        foreach ($contagem ?? [] as $face => $qtd) {
            $total += (int) $qtd * (float) $face;
        }
        return round($total, 2);
    }

    // quantidades por cedula/moeda -> [contagem limpa, moedas, cedulas]
    public static function contagem(?array $qtds): array
    {
        $limpa = [];
        $totais = ['moedas' => 0.0, 'cedulas' => 0.0];
        foreach (['cedulas' => static::CEDULAS, 'moedas' => static::MOEDAS] as $tipo => $valores) {
            foreach ($valores as $valor) {
                $qtd = (int) ($qtds[$valor] ?? 0);
                if ($qtd < 0) {
                    abort(422, 'Quantidade de cédula ou moeda não pode ser negativa.');
                }
                if ($qtd > 0) {
                    $limpa[$valor] = $qtd;
                    $totais[$tipo] += $qtd * (float) $valor;
                }
            }
        }
        return [$limpa, round($totais['moedas'], 2), round($totais['cedulas'], 2)];
    }

    // o que ficou na gaveta ao fechar a sessao anterior (0 na primeira)
    public static function envelope(int $codportador): float
    {
        $ultima = static::ultimaSessao($codportador);
        return round((float) ($ultima->saldofinal ?? 0), 2);
    }

    // lancamentos dos itens da sessao, criando os que faltam (item ativo
    // da filial cadastrado depois da abertura)
    public static function lancamentos(PortadorPeriodo $sessao)
    {
        if ($sessao->aberto() && $sessao->Portador->ehGaveta()) {
            $tem = CaixaItemLancamento::where('codportadorperiodo', $sessao->codportadorperiodo)->pluck('codcaixaitem')->all();
            foreach (CaixaItemService::ativosDaFilial($sessao->Portador->codfilial) as $item) {
                if (!in_array($item->codcaixaitem, $tem)) {
                    CaixaItemLancamento::create([
                        'codportadorperiodo' => $sessao->codportadorperiodo,
                        'codcaixaitem' => $item->codcaixaitem,
                        'valorabertura' => $item->ehContagem() ? 0 : null,
                    ]);
                }
            }
        }
        return CaixaItemLancamento::where('codportadorperiodo', $sessao->codportadorperiodo)
            ->with(['CaixaItem', 'Titulo:codtitulo,numero,valor,saldo,codtipotitulo'])
            ->get()
            ->sortBy(fn ($l) => [$l->CaixaItem->ordem, $l->CaixaItem->item])
            ->values();
    }

    // o pagamento em dinheiro da sessao que a gaveta mantem (ajuste e item):
    // valor com sinal (positivo entrou); zero cancela; sempre o mesmo
    // registro (cancelado volta a valer)
    public static function pagamentoNaGaveta(PortadorPeriodo $sessao, ?Pagamento $pag, float $valor, array $dados): ?Pagamento
    {
        $valor = round($valor, 2);
        if ($valor == 0) {
            if ($pag && $pag->estado != PagamentoService::ESTADO_CANCELADO) {
                PagamentoService::cancelar($pag, $dados['justificativa'] ?? 'Zerado no caixa');
            }
            return $pag;
        }
        unset($dados['justificativa']);
        $pag = $pag ?? new Pagamento();
        $agora = Carbon::now();
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            $pag->cancelamento = null;
            $pag->codusuariocancelamento = null;
            $pag->justificativa = null;
        }
        PagamentoService::preencher($pag, array_merge([
            'codportadordestino' => $valor > 0 ? $sessao->codportador : null,
            'codportadororigem' => $valor < 0 ? $sessao->codportador : null,
            'meio' => PagamentoService::MEIO_DINHEIRO,
            'principal' => abs($valor),
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'efetivacao' => $pag->efetivacao ?? $agora,
            'codusuarioefetivacao' => $pag->codusuarioefetivacao ?? (Auth::user()->codusuario ?? null),
            'codportadorperiodo' => $sessao->codportadorperiodo,
            'codfilial' => $sessao->Portador->codfilial,
        ], $dados));
        $pag->save();
        PortadorMovimentoService::sincronizar($pag);
        return $pag;
    }

    // abre a sessao: saldo inicial = o que ficou da anterior (envelope). A
    // contagem vem junto quando o PDV conta na abertura; senao, nasce com a
    // do fechamento da anterior (o dinheiro e' o mesmo) e se corrige ao lado
    // do saldo inicial. Diferenca so' aparece (o ajuste e' lancamento)
    public static function abrir(Portador $gaveta, ?array $contagem, array $itens, ?string $observacoes, ?int $codpdv = null, ?Carbon $inicio = null): PortadorPeriodo
    {
        if (!$gaveta->ehCaixa()) {
            abort(422, "{$gaveta->portador} não é caixa (portador em espécie).");
        }
        static::autorizarOperar($gaveta);
        Portador::where('codportador', $gaveta->codportador)->lockForUpdate()->first();
        if (static::sessaoAberta($gaveta->codportador)) {
            abort(422, "O caixa {$gaveta->portador} já está aberto.");
        }
        $envelope = static::envelope($gaveta->codportador);
        $anterior = static::ultimaSessao($gaveta->codportador);
        $inicio = $inicio ?? Carbon::now()->startOfSecond();
        static::exigirDatas($gaveta->codportador, null, $inicio, null);
        $sessao = PortadorPeriodo::create([
            'codportador' => $gaveta->codportador,
            'inicio' => $inicio,
            'codusuarioabertura' => Auth::user()->codusuario,
            'saldoinicial' => $envelope,
            'saldofinal' => $envelope,
            'observacoes' => empty(trim($observacoes ?? '')) ? null : trim($observacoes),
        ]);
        // os itens do caixa da gaveta (estoque zerado; a contagem preenche)
        static::lancamentos($sessao);
        if ($contagem === null && $anterior && $anterior->contagemfinal !== null) {
            $contagem = $anterior->contagemfinal;
            $itens = static::lancamentos($anterior)
                ->filter(fn ($l) => $l->CaixaItem->ehContagem())
                ->mapWithKeys(fn ($l) => [$l->codcaixaitem => (float) $l->valorfechamento])
                ->all();
        }
        if ($contagem !== null) {
            PortadorPeriodoService::contar($sessao, 'inicial', $contagem, $itens);
        }
        PortadorPeriodoService::recalcular($sessao);
        return $sessao->fresh();
    }

    // salva o item na sessao aberta e mantem o pagamento entrada - saida
    public static function salvarItem(PortadorPeriodo $sessao, CaixaItem $item, array $dados, ?int $codpdv = null): CaixaItemLancamento
    {
        if (!empty($sessao->fechamento)) {
            static::naoAberta($sessao->Portador);
        }
        $lanc = CaixaItemLancamento::firstOrNew([
            'codportadorperiodo' => $sessao->codportadorperiodo,
            'codcaixaitem' => $item->codcaixaitem,
        ]);
        foreach (['valorentrada', 'valorsaida'] as $col) {
            $lanc->$col = round((float) ($dados[$col] ?? 0), 2);
        }
        $lanc->valorvendido = $item->ehContagem() ? null : (isset($dados['valorvendido']) ? round((float) $dados['valorvendido'], 2) : null);
        if ($item->ehContagem() && $lanc->valorabertura === null) {
            $lanc->valorabertura = 0;
        }
        $lanc->observacoes = empty(trim($dados['observacoes'] ?? '')) ? null : trim($dados['observacoes']);
        $lanc->save();
        $pag = static::pagamentoNaGaveta($sessao, $lanc->Pagamento, $lanc->valorentrada - $lanc->valorsaida, [
            'codcaixaitemlancamento' => $lanc->codcaixaitemlancamento,
            'codpdv' => $lanc->Pagamento->codpdv ?? $codpdv,
            'codpessoa' => $item->codpessoa,
            'observacoes' => $item->item . ($lanc->observacoes ? " · {$lanc->observacoes}" : ''),
            'justificativa' => 'Item do caixa zerado',
        ]);
        if ($pag && $lanc->codpagamento != $pag->codpagamento) {
            $lanc->codpagamento = $pag->codpagamento;
            $lanc->save();
        }
        return $lanc->fresh(['CaixaItem']);
    }

    // lancamento avulso de entrada (E) ou saida (S) na sessao nao fechada
    // A data fica dentro da sessao: do inicio ao fim (aberta, ate' agora);
    // sem data, o fim (reaberta) ou agora
    public static function lancarAvulso(PortadorPeriodo $sessao, string $sentido, string $motivo, float $valor, string $observacoes, ?int $codpdv = null, ?Carbon $transacao = null): Pagamento
    {
        if (!empty($sessao->fechamento)) {
            static::naoAberta($sessao->Portador);
        }
        $ate = $sessao->fim ?? Carbon::now();
        $transacao = $transacao ?? $ate;
        if ($transacao->lt($sessao->inicio) || $transacao->gt($ate)) {
            abort(422, 'A data precisa estar dentro da ' . PortadorPeriodoService::descricao($sessao)
                . ' (de ' . $sessao->inicio->format('d/m/Y H:i') . ' a ' . $ate->format('d/m/Y H:i') . ').');
        }
        return static::pagamentoNaGaveta($sessao, null, $sentido == 'E' ? $valor : -$valor, [
            'transacao' => $transacao,
            'motivo' => $motivo,
            'codpdv' => $codpdv,
            'observacoes' => mb_substr(trim($observacoes), 0, 300),
        ]);
    }

    // so' quem lancou, com o caixa nao fechado; item nao
    public static function cancelarAvulso(Pagamento $pag): Pagamento
    {
        $sessao = static::sessaoDoPagamento($pag);
        if (empty($pag->motivo) || !empty($pag->codcaixaitemlancamento) || !$sessao) {
            abort(422, 'Só lançamento avulso do caixa se exclui por aqui.');
        }
        if ($pag->codusuariocriacao != Auth::user()->codusuario) {
            abort(403, 'Só quem lançou exclui o lançamento avulso.');
        }
        if (!empty($sessao->fechamento)) {
            static::naoAberta($sessao->Portador);
        }
        return PagamentoService::cancelar($pag, 'Lançamento avulso excluído pelo caixa');
    }

    // fecha a sessao: a contagem do fechamento (a do PDV vem junto; no
    // contas, contada antes ao lado do saldo final) precisa bater com o saldo
    // final. Reaberta (ja' tem fim) fecha no mesmo fim. Fechar e' a
    // conferencia: o razao trava
    public static function fechar(PortadorPeriodo $sessao, ?array $contagem, array $itens, ?string $observacoes, ?int $codpdv = null, ?Carbon $fim = null): PortadorPeriodo
    {
        $gaveta = $sessao->Portador;
        static::autorizarOperar($gaveta);
        Portador::where('codportador', $gaveta->codportador)->lockForUpdate()->first();
        $sessao->refresh();
        if (!empty($sessao->fechamento)) {
            abort(422, "O caixa {$gaveta->portador} já está fechado.");
        }
        // decisao 22: transferencia chegando a confirmar trava; saindo, nao
        $chegando = PagamentoService::pendentes($gaveta)
            ->where('codportadordestino', $gaveta->codportador);
        if ($chegando->isNotEmpty()) {
            abort(422, "Há {$chegando->count()} transferência(s) chegando ao caixa {$gaveta->portador} a confirmar: confirme ou cancele antes de fechar.");
        }
        if ($contagem !== null) {
            PortadorPeriodoService::contar($sessao, 'final', $contagem, $itens);
        }
        // timestamp(0) no banco: sem fracao
        $agora = Carbon::now()->startOfSecond();
        $fim = $sessao->fim ?? $fim ?? $agora;
        static::exigirDatas($sessao->codportador, $sessao->codportadorperiodo, $sessao->inicio, $fim);
        $saldo = PortadorPeriodoService::exigirContagemBate($sessao, $fim);
        static::titulosRepasse($sessao, $fim);
        $usuario = Auth::user()->codusuario;
        $sessao->fill([
            'fim' => $fim,
            'fechamento' => $agora,
            'codusuariofechamento' => $usuario,
            'saldofinal' => $saldo,
        ]);
        if ($observacoes !== null) {
            $sessao->observacoes = trim($observacoes) ?: null;
        }
        $sessao->save();
        return $sessao;
    }

    // um titulo por item com parceiro e liquido <> 0: positivo devemos
    // (Duplicata a Pagar), negativo o parceiro deve (Duplicata a Receber)
    private static function titulosRepasse(PortadorPeriodo $sessao, Carbon $momento): void
    {
        foreach (static::lancamentos($sessao) as $lanc) {
            $item = $lanc->CaixaItem;
            $liquido = $lanc->liquido();
            if ($liquido == 0 || empty($item->codpessoa) || empty($item->codcontacontabil)) {
                continue;
            }
            $titulo = TituloService::criar([
                'codtipotitulo' => $liquido > 0 ? TituloService::TIPO_DUPLICATA_PAGAR : TituloService::TIPO_DUPLICATA_RECEBER,
                'codfilial' => $sessao->Portador->codfilial,
                'codpessoa' => $item->codpessoa,
                'codcontacontabil' => $item->codcontacontabil,
                'numero' => $momento->format('Y-m-d') . '-P' . $sessao->codportadorperiodo,
                'sufixo' => true,
                'transacao' => $momento->toDateString(),
                'emissao' => $momento->toDateString(),
                'vencimento' => $momento->toDateString(),
                'valor' => abs($liquido),
                'observacao' => "Repasse {$item->item} · {$sessao->Portador->portador} sessão {$sessao->codportadorperiodo}",
            ]);
            $lanc->codtitulo = $titulo->codtitulo;
            $lanc->save();
        }
    }

    // corrige o inicio, as observacoes e, na sessao reaberta, o fim (a
    // aberta nao tem fim ainda; o fechado nao se mexe)
    public static function editarDatas(PortadorPeriodo $sessao, Carbon $inicio, ?Carbon $fim, ?string $observacoes = null): PortadorPeriodo
    {
        static::autorizarOperar($sessao->Portador);
        Portador::where('codportador', $sessao->codportador)->lockForUpdate()->first();
        $sessao->refresh();
        if (!empty($sessao->fechamento)) {
            abort(422, 'Caixa fechado: reabra para mudar o início e o fim.');
        }
        $fim = empty($sessao->fim) ? null : ($fim ?? $sessao->fim);
        static::exigirDatas($sessao->codportador, $sessao->codportadorperiodo, $inicio, $fim);
        $sessao->inicio = $inicio;
        $sessao->fim = $fim;
        $sessao->observacoes = trim($observacoes ?? '') ?: null;
        $sessao->save();
        return $sessao;
    }

    // inicio e fim da sessao: nada no futuro, sem invadir outra sessao do
    // caixa (o fim de uma pode ser o inicio da seguinte) e sem deixar
    // lancamento de fora
    private static function exigirDatas(int $codportador, ?int $codportadorperiodo, Carbon $inicio, ?Carbon $fim): void
    {
        $f = fn (Carbon $d) => $d->format('d/m/Y H:i');
        $agora = Carbon::now();
        if ($inicio->gt($agora) || ($fim && $fim->gt($agora))) {
            abort(422, 'Início e fim não podem ser no futuro.');
        }
        if ($fim && $fim->lt($inicio)) {
            abort(422, 'O fim não pode ser antes do início.');
        }
        $outra = PortadorPeriodo::where('codportador', $codportador)
            ->when($codportadorperiodo, fn ($q) => $q->where('codportadorperiodo', '!=', $codportadorperiodo))
            ->where(fn ($q) => $q->whereNull('fim')->orWhere('fim', '>', $inicio))
            ->when($fim, fn ($q) => $q->where('inicio', '<', $fim))
            ->orderBy('inicio')
            ->first();
        if ($outra) {
            abort(422, 'Invade a ' . PortadorPeriodoService::descricao($outra)
                . ($outra->fim ? ' (até ' . $f($outra->fim) . ')' : ', que está aberta') . '.');
        }
        if (!$codportadorperiodo) {
            return;
        }
        $fora = PortadorMovimento::where('codportadorperiodo', $codportadorperiodo)
            ->whereNull('inativo')
            ->where(fn ($q) => $q->where('transacao', '<', $inicio)
                ->when($fim, fn ($q) => $q->orWhere('transacao', '>', $fim)))
            ->orderBy('transacao')
            ->first();
        if ($fora) {
            abort(422, "Há lançamento em {$f($fora->transacao)}, fora do início e do fim.");
        }
    }

    // o gerente reabre do mais novo para o mais antigo: a ultima sessao volta
    // a ficar aberta (aceita movimento); uma anterior so' destrava o razao
    // para corrigir (mantem o fim). Estorna os titulos de repasse (422 se ja'
    // movimentados)
    public static function reabrir(PortadorPeriodo $sessao): PortadorPeriodo
    {
        if (!ConferenciaAutorizador::pode($sessao->Portador->codfilial)) {
            abort(403, 'Reabrir o caixa: só Gerente da filial, Financeiro ou Administrador!');
        }
        Portador::where('codportador', $sessao->codportador)->lockForUpdate()->first();
        $sessao->refresh();
        if (empty($sessao->fechamento)) {
            abort(422, 'O caixa já está aberto.');
        }
        $posterior = PortadorPeriodo::where('codportador', $sessao->codportador)
            ->where('inicio', '>', $sessao->inicio)
            ->whereNotNull('fechamento')
            ->orderBy('inicio', 'desc')
            ->first();
        if ($posterior) {
            abort(422, 'Reabra antes a ' . PortadorPeriodoService::descricao($posterior) . ' (reabre-se do mais novo para o mais antigo).');
        }
        $ultima = static::ultimaSessao($sessao->codportador)->codportadorperiodo == $sessao->codportadorperiodo;
        $lancs = static::lancamentos($sessao)->filter(fn ($l) => !empty($l->codtitulo));
        foreach ($lancs as $lanc) {
            $t = $lanc->Titulo;
            if (round((float) $t->valor, 2) != round((float) $t->saldo, 2)) {
                abort(422, "O título de repasse {$t->numero} ({$lanc->CaixaItem->item}) já foi agrupado ou pago: estorne no contas antes de reabrir o caixa.");
            }
        }
        $sessao->fill([
            'fim' => $ultima ? null : $sessao->fim,
            'fechamento' => null,
            'codusuariofechamento' => null,
        ]);
        $sessao->save();
        foreach ($lancs as $lanc) {
            TituloService::estornar($lanc->Titulo, "Reabertura do caixa {$sessao->Portador->portador}");
            $lanc->codtitulo = null;
            $lanc->save();
        }
        return $sessao->fresh();
    }

    // dinheiro do sistema na sessao: saldo inicial (envelope) + entradas -
    // saidas, por documento (venda, item do caixa, ajuste, titulo,
    // transferencia, avulso). Transferencia (M11) entra pelo razao, que tem
    // a sessao de cada lado, e conta desde o registro, ainda a confirmar
    // (decisao 13)
    public static function dinheiro(PortadorPeriodo $sessao): array
    {
        $regs = DB::select("
            select
                case
                    when p.codnegocio is not null then 'V'
                    when p.codcaixaitemlancamento is not null then 'I'
                    when exists (select 1 from tblmovimentotitulo mt where mt.codpagamento = p.codpagamento) then 'T'
                    when p.codportadororigem is not null and p.codportadordestino is not null then 'X'
                    else 'A'
                end as documento,
                sum(case when p.codportadordestino = :portador1 then p.total else 0 end) as entrada,
                sum(case when p.codportadororigem = :portador2 then p.total else 0 end) as saida,
                count(*) as quantidade
            from tblpagamento p
            where (
                (p.codportadorperiodo = :sessao1 and p.estado = 'E')
                or exists (
                    select 1 from tblportadormovimento pm
                    where pm.codpagamento = p.codpagamento
                    and pm.codportadorperiodo = :sessao2
                    and pm.inativo is null
                )
            )
            group by 1
        ", [
            'portador1' => $sessao->codportador,
            'portador2' => $sessao->codportador,
            'sessao1' => $sessao->codportadorperiodo,
            'sessao2' => $sessao->codportadorperiodo,
        ]);
        $ret = [
            'saldoinicial' => (float) $sessao->saldoinicial,
            'entrada' => 0.0,
            'saida' => 0.0,
            'documentos' => [],
        ];
        foreach ($regs as $r) {
            $ret['documentos'][] = [
                'documento' => $r->documento,
                'entrada' => (float) $r->entrada,
                'saida' => (float) $r->saida,
                'quantidade' => (int) $r->quantidade,
            ];
            $ret['entrada'] = round($ret['entrada'] + $r->entrada, 2);
            $ret['saida'] = round($ret['saida'] + $r->saida, 2);
        }
        $ret['sistema'] = round($ret['saldoinicial'] + $ret['entrada'] - $ret['saida'], 2);
        return $ret;
    }

    // o que os PDVs da gaveta movimentaram na janela da sessao, fora o
    // dinheiro: so' informacao no bordero do caixa
    public static function informativo(PortadorPeriodo $sessao): array
    {
        $fim = $sessao->fim ?? Carbon::now();
        $regs = DB::select("
            select p.meio, sum(case when p.codpagamentoorigem is null then p.total else -p.total end) as valor, count(*) as quantidade
            from tblpagamento p
            inner join tblpdv pdv on (pdv.codpdv = p.codpdv)
            where pdv.codportador = :portador
            and p.transacao between :inicio and :fim
            and p.estado = 'E'
            and p.meio <> 1
            group by p.meio
            order by p.meio
        ", [
            'portador' => $sessao->codportador,
            'inicio' => $sessao->inicio,
            'fim' => $fim,
        ]);
        $meios = array_map(fn ($r) => [
            'meio' => (int) $r->meio,
            'descricao' => PagamentoService::MEIOS[$r->meio] ?? 'Outros',
            'valor' => (float) $r->valor,
            'quantidade' => (int) $r->quantidade,
        ], $regs);
        $prazo = DB::selectOne("
            select coalesce(sum(np.valor), 0) as valor, count(*) as quantidade
            from tblnegocioparcela np
            inner join tblnegocio n on (n.codnegocio = np.codnegocio)
            inner join tblpdv pdv on (pdv.codpdv = n.codpdv)
            where pdv.codportador = :portador
            and n.codnegociostatus = 2
            and n.lancamento between :inicio and :fim
        ", [
            'portador' => $sessao->codportador,
            'inicio' => $sessao->inicio,
            'fim' => $fim,
        ]);
        $maquinetas = DB::select("
            select m.apelido as maquineta, count(*) as quantidade
            from tblpagamento p
            inner join tblpdv pdv on (pdv.codpdv = p.codpdv)
            inner join tblmaquineta m on (m.codmaquineta = p.codmaquineta)
            where pdv.codportador = :portador
            and p.transacao between :inicio and :fim
            and p.estado = 'E'
            and p.meio in (3, 4)
            group by m.apelido
            order by m.apelido
        ", [
            'portador' => $sessao->codportador,
            'inicio' => $sessao->inicio,
            'fim' => $fim,
        ]);
        return [
            'meios' => $meios,
            'prazo' => ['valor' => (float) $prazo->valor, 'quantidade' => (int) $prazo->quantidade],
            'maquinetas' => array_map(fn ($r) => ['maquineta' => $r->maquineta, 'quantidade' => (int) $r->quantidade], $maquinetas),
        ];
    }

    // tudo que a tela do caixa mostra da sessao (M13): dinheiro do sistema,
    // contagens, itens, ajustes, avulsos, transferencias e o informativo
    // os itens da sessao como a tela do caixa e o periodo mostram
    public static function itens(PortadorPeriodo $sessao): array
    {
        return static::lancamentos($sessao)->map(fn (CaixaItemLancamento $l) => [
            'codcaixaitemlancamento' => $l->codcaixaitemlancamento,
            'codcaixaitem' => $l->codcaixaitem,
            'item' => $l->CaixaItem->item,
            'modo' => $l->CaixaItem->modo,
            'parceiro' => !empty($l->CaixaItem->codpessoa),
            'valorabertura' => $l->valorabertura,
            'valorentrada' => $l->valorentrada,
            'valorsaida' => $l->valorsaida,
            'valorvendido' => $l->valorvendido,
            'valorfechamento' => $l->valorfechamento,
            'liquido' => $l->valorfechamento === null && $l->CaixaItem->ehContagem() ? null : $l->liquido(),
            'observacoes' => $l->observacoes,
            'codpagamento' => $l->codpagamento,
            'codtitulo' => $l->codtitulo,
            'titulo' => optional($l->Titulo)->numero,
        ])->all();
    }

    public static function painel(PortadorPeriodo $sessao): array
    {
        $avulsos = Pagamento::where('codportadorperiodo', $sessao->codportadorperiodo)
            ->whereNotNull('motivo')
            ->whereNull('codcaixaitemlancamento')
            ->with('UsuarioCriacao:codusuario,usuario')
            ->orderBy('transacao')
            ->get()
            ->map(fn (Pagamento $p) => [
                'codpagamento' => $p->codpagamento,
                'transacao' => $p->transacao,
                'motivo' => $p->motivo,
                'motivodescricao' => PagamentoService::MOTIVOS[$p->motivo] ?? null,
                'valor' => round(empty($p->codportadordestino) ? -$p->total : $p->total, 2),
                'estado' => $p->estado,
                'observacoes' => $p->observacoes,
                'justificativa' => $p->justificativa,
                'usuariocriacao' => optional($p->UsuarioCriacao)->usuario,
                'podeExcluir' => empty($sessao->fechamento)
                    && $p->estado == PagamentoService::ESTADO_EFETIVADO
                    && $p->codusuariocriacao == (Auth::user()->codusuario ?? null),
            ])->all();
        return [
            'dinheiro' => static::dinheiro($sessao),
            'informativo' => static::informativo($sessao),
            'itens' => static::itens($sessao),
            'avulsos' => $avulsos,
        ];
    }
}
