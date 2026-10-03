<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pdv\Pdv;
use Mg\Portador\Portador;
use Mg\Portador\PortadorMovimentoService;
use Mg\Portador\PortadorPeriodo;
use Mg\Titulo\TituloService;
use Mg\Usuario\Autorizador;

/**
 * Sessao da gaveta (M9 doc-3): o caixa abre e fecha o dinheiro no PDV com
 * contagem; todo dinheiro que entra ou sai da gaveta fica na sessao aberta
 * (tblpagamento.codportadorperiodo); o gerente confere no contas.
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

    public static function ultimaSessao(int $codportador): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
    }

    // a gaveta do pagamento: o destino se for gaveta, senao a origem. Conta
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
            if ($portador && $portador->ehGaveta()) {
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
                if ($sessao && !$sessao->aberto()) {
                    abort(422, "O caixa {$portador->portador} daquele dinheiro já foi fechado ({$sessao->fim->format('d/m/Y H:i')}): o gerente precisa reabrir a sessão antes.");
                }
            }
            return;
        }
        if (!empty($pag->codportadorperiodo) && !$pag->isDirty(['codportadordestino', 'codportadororigem', 'meio'])) {
            return;
        }
        $sessao = static::sessaoAberta($portador->codportador);
        if (!$sessao) {
            abort(422, "Caixa {$portador->portador} fechado: abra o caixa no PDV antes de movimentar dinheiro.");
        }
        $pag->codportadorperiodo = $sessao->codportadorperiodo;
    }

    // gaveta que recebe o dinheiro do PDV agora: sem gaveta ou com o caixa
    // fechado o Dinheiro fica bloqueado (TASK-188 AC #34)
    public static function gavetaAberta(Pdv $pdv): Portador
    {
        $gaveta = static::gaveta($pdv);
        if (!static::sessaoAberta($gaveta->codportador)) {
            abort(422, "Caixa {$gaveta->portador} fechado: abra o caixa no PDV antes de receber em dinheiro.");
        }
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
    // Abre com a contagem (cedulas, moedas e o estoque dos itens C); o saldo
    // inicial e' o envelope (o que ficou da sessao anterior) e a diferenca
    // vira um ajuste. Fecha quem estiver com o dinheiro (caixa no PDV ou
    // gerente no contas): conta o que fica, um ajuste se o contado difere do
    // sistema, titulos de repasse dos itens. Fechar e' a conferencia: grava
    // `conferencia` e trava o razao; corrigir depois so' reabrindo.

    // cedulas e moedas da contagem (chave = valor, quantidade no jsonb)
    const CEDULAS = ['200', '100', '50', '20', '10', '5', '2'];
    const MOEDAS = ['1', '0.50', '0.25', '0.10', '0.05', '0.01'];

    // quem opera a gaveta: Caixa ou Gerente da filial, Financeiro, Admin
    public static function podeOperar(?int $codfilial): bool
    {
        return Autorizador::pode([])
            || Autorizador::pode(['Caixa', 'Gerente'], $codfilial)
            || ConferenciaAutorizador::pode($codfilial);
    }

    public static function autorizarOperar(?int $codfilial): void
    {
        if (!static::podeOperar($codfilial)) {
            abort(403, 'Caixa: só Caixa ou Gerente da filial, Financeiro ou Administrador!');
        }
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
        if ($sessao->aberto()) {
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

    // soma do estoque contado dos itens C ({codcaixaitem: valor})
    private static function somaItens(array $itens): float
    {
        return round(array_sum(array_map(fn ($v) => (float) $v, $itens)), 2);
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

    private static function ajustar(PortadorPeriodo $sessao, string $coluna, float $valor, Carbon $momento, ?int $codpdv): void
    {
        $pag = $sessao->$coluna ? Pagamento::find($sessao->$coluna) : null;
        $pag = static::pagamentoNaGaveta($sessao, $pag, $valor, [
            'motivo' => PagamentoService::MOTIVO_AJUSTE,
            'transacao' => $momento,
            'codpdv' => $pag->codpdv ?? $codpdv,
            'observacoes' => $coluna == 'codpagamentoabertura'
                ? 'Diferença na abertura do caixa'
                : 'Diferença no fechamento do caixa',
            'justificativa' => 'Contagem igual ao sistema',
        ]);
        if ($pag && $sessao->$coluna != $pag->codpagamento) {
            $sessao->$coluna = $pag->codpagamento;
            $sessao->save();
        }
    }

    public static function abrir(Portador $gaveta, ?array $contagem, array $itens, ?string $observacoes, ?int $codpdv = null): PortadorPeriodo
    {
        if (!$gaveta->ehGaveta()) {
            abort(422, "{$gaveta->portador} não é gaveta (portador em espécie vinculado a um PDV).");
        }
        static::autorizarOperar($gaveta->codfilial);
        Portador::where('codportador', $gaveta->codportador)->lockForUpdate()->first();
        if (static::sessaoAberta($gaveta->codportador)) {
            abort(422, "O caixa {$gaveta->portador} já está aberto.");
        }
        [$contagem, $moedas, $cedulas] = static::contagem($contagem);
        $envelope = static::envelope($gaveta->codportador);
        $agora = Carbon::now();
        $sessao = PortadorPeriodo::create([
            'codportador' => $gaveta->codportador,
            'inicio' => $agora,
            'codusuarioabertura' => Auth::user()->codusuario,
            'contagemabertura' => $contagem,
            'moedasabertura' => $moedas,
            'cedulasabertura' => $cedulas,
            'saldoinicial' => $envelope,
            'observacoes' => empty(trim($observacoes ?? '')) ? null : trim($observacoes),
        ]);
        $estoque = [];
        foreach (CaixaItemService::ativosDaFilial($gaveta->codfilial) as $item) {
            $valor = $item->ehContagem() ? round((float) ($itens[$item->codcaixaitem] ?? 0), 2) : null;
            if ($valor !== null && $valor < 0) {
                abort(422, "Contagem de {$item->item} não pode ser negativa.");
            }
            CaixaItemLancamento::create([
                'codportadorperiodo' => $sessao->codportadorperiodo,
                'codcaixaitem' => $item->codcaixaitem,
                'valorabertura' => $valor,
            ]);
            $estoque[] = $valor ?? 0;
        }
        $contado = round($moedas + $cedulas + array_sum($estoque), 2);
        static::ajustar($sessao, 'codpagamentoabertura', $contado - $envelope, $agora, $codpdv);
        return $sessao->fresh();
    }

    // salva o item na sessao aberta e mantem o pagamento entrada - saida
    public static function salvarItem(PortadorPeriodo $sessao, CaixaItem $item, array $dados, ?int $codpdv = null): CaixaItemLancamento
    {
        if (!$sessao->aberto()) {
            abort(422, 'Caixa fechado: reabra a sessão para mexer nos itens.');
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

    // lancamento avulso de entrada (E) ou saida (S) na sessao aberta
    public static function lancarAvulso(PortadorPeriodo $sessao, string $sentido, string $motivo, float $valor, string $observacoes, ?int $codpdv = null): Pagamento
    {
        if (!$sessao->aberto()) {
            abort(422, 'Caixa fechado: abra o caixa antes de lançar.');
        }
        return static::pagamentoNaGaveta($sessao, null, $sentido == 'E' ? $valor : -$valor, [
            'motivo' => $motivo,
            'codpdv' => $codpdv,
            'observacoes' => mb_substr(trim($observacoes), 0, 300),
        ]);
    }

    public static function ehAjuste(Pagamento $pag): bool
    {
        return PortadorPeriodo::where('codpagamentoabertura', $pag->codpagamento)
            ->orWhere('codpagamentofechamento', $pag->codpagamento)
            ->exists();
    }

    // so' quem lancou, com o caixa aberto; ajuste e item nao
    public static function cancelarAvulso(Pagamento $pag): Pagamento
    {
        $sessao = $pag->PortadorPeriodo;
        if (empty($pag->motivo) || !empty($pag->codcaixaitemlancamento) || static::ehAjuste($pag) || !$sessao) {
            abort(422, 'Só lançamento avulso do caixa se exclui por aqui.');
        }
        if ($pag->codusuariocriacao != Auth::user()->codusuario) {
            abort(403, 'Só quem lançou exclui o lançamento avulso.');
        }
        if (!$sessao->aberto()) {
            abort(422, 'Caixa fechado: o lançamento só se exclui com o caixa aberto.');
        }
        return PagamentoService::cancelar($pag, 'Lançamento avulso excluído pelo caixa');
    }

    // fecha a sessao contando o que fica na gaveta (o envelope de amanha)
    public static function fechar(PortadorPeriodo $sessao, ?array $contagem, array $itens, ?string $observacoes, ?int $codpdv = null): PortadorPeriodo
    {
        $gaveta = $sessao->Portador;
        static::autorizarOperar($gaveta->codfilial);
        Portador::where('codportador', $gaveta->codportador)->lockForUpdate()->first();
        $sessao->refresh();
        if (!$sessao->aberto()) {
            abort(422, "O caixa {$gaveta->portador} já está fechado.");
        }
        // decisao 22: transferencia chegando a confirmar trava; saindo, nao
        $chegando = PagamentoService::pendentes($gaveta)
            ->where('codportadordestino', $gaveta->codportador);
        if ($chegando->isNotEmpty()) {
            abort(422, "Há {$chegando->count()} transferência(s) chegando ao caixa {$gaveta->portador} a confirmar: confirme ou cancele antes de fechar.");
        }
        [$contagem, $moedas, $cedulas] = static::contagem($contagem);
        $estoque = 0.0;
        foreach (static::lancamentos($sessao) as $lanc) {
            if (!$lanc->CaixaItem->ehContagem()) {
                continue;
            }
            $valor = round((float) ($itens[$lanc->codcaixaitem] ?? 0), 2);
            if ($valor < 0) {
                abort(422, "Contagem de {$lanc->CaixaItem->item} não pode ser negativa.");
            }
            $lanc->valorfechamento = $valor;
            $lanc->save();
            $estoque += $valor;
        }
        $contado = round($moedas + $cedulas + $estoque, 2);
        $agora = Carbon::now();
        static::ajustar($sessao, 'codpagamentofechamento', $contado - static::dinheiro($sessao)['sistema'], $agora, $codpdv);
        static::titulosRepasse($sessao, $agora);
        $usuario = Auth::user()->codusuario;
        $sessao->fill([
            'fim' => $agora,
            'fechamento' => $agora,
            'codusuariofechamento' => $usuario,
            'contagemfechamento' => $contagem,
            'moedasfechamento' => $moedas,
            'cedulasfechamento' => $cedulas,
            'saldofinal' => $contado,
            'conferencia' => $agora,
            'codusuarioconferencia' => $usuario,
            'valorconferido' => $contado,
            'observacoes' => trim(($sessao->observacoes ? $sessao->observacoes . "\n" : '') . ($observacoes ?? '')) ?: null,
        ]);
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

    // o gerente reabre a ultima sessao da gaveta: estorna os titulos de
    // repasse (422 se ja' movimentados) e cancela o ajuste de fechamento
    // (o registro fica, e volta a valer no proximo fechar)
    public static function reabrir(PortadorPeriodo $sessao): PortadorPeriodo
    {
        if (!ConferenciaAutorizador::pode($sessao->Portador->codfilial)) {
            abort(403, 'Reabrir o caixa: só Gerente da filial, Financeiro ou Administrador!');
        }
        Portador::where('codportador', $sessao->codportador)->lockForUpdate()->first();
        $sessao->refresh();
        if ($sessao->aberto()) {
            abort(422, 'O caixa já está aberto.');
        }
        $ultima = static::ultimaSessao($sessao->codportador);
        if ($ultima->codportadorperiodo != $sessao->codportadorperiodo) {
            abort(422, 'Só a última sessão do caixa pode ser reaberta; feche a sessão atual antes.');
        }
        $lancs = static::lancamentos($sessao)->filter(fn ($l) => !empty($l->codtitulo));
        foreach ($lancs as $lanc) {
            $t = $lanc->Titulo;
            if (round((float) $t->valor, 2) != round((float) $t->saldo, 2)) {
                abort(422, "O título de repasse {$t->numero} ({$lanc->CaixaItem->item}) já foi agrupado ou pago: estorne no contas antes de reabrir o caixa.");
            }
        }
        $sessao->fill([
            'fim' => null,
            'fechamento' => null,
            'codusuariofechamento' => null,
            'conferencia' => null,
            'codusuarioconferencia' => null,
            'valorconferido' => null,
        ]);
        $sessao->save();
        foreach ($lancs as $lanc) {
            TituloService::estornar($lanc->Titulo, "Reabertura do caixa {$sessao->Portador->portador}");
            $lanc->codtitulo = null;
            $lanc->save();
        }
        if ($sessao->codpagamentofechamento) {
            $pag = Pagamento::find($sessao->codpagamentofechamento);
            if ($pag->estado != PagamentoService::ESTADO_CANCELADO) {
                PagamentoService::cancelar($pag, 'Reabertura do caixa');
            }
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
                    when p.codpagamento in (:abertura, :fechamento) then 'J'
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
            'abertura' => $sessao->codpagamentoabertura ?? 0,
            'fechamento' => $sessao->codpagamentofechamento ?? 0,
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
    public static function painel(PortadorPeriodo $sessao): array
    {
        $ajuste = function ($cod) {
            $pag = $cod ? Pagamento::find($cod) : null;
            if (!$pag || $pag->estado != PagamentoService::ESTADO_EFETIVADO) {
                return 0.0;
            }
            return round(empty($pag->codportadordestino) ? -$pag->total : $pag->total, 2);
        };
        $itens = static::lancamentos($sessao)->map(fn (CaixaItemLancamento $l) => [
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
        $avulsos = Pagamento::where('codportadorperiodo', $sessao->codportadorperiodo)
            ->whereNotNull('motivo')
            ->whereNull('codcaixaitemlancamento')
            ->whereNotIn('codpagamento', array_filter([$sessao->codpagamentoabertura, $sessao->codpagamentofechamento]) ?: [0])
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
                'podeExcluir' => $sessao->aberto()
                    && $p->estado == PagamentoService::ESTADO_EFETIVADO
                    && $p->codusuariocriacao == (Auth::user()->codusuario ?? null),
            ])->all();
        return [
            'dinheiro' => static::dinheiro($sessao),
            'informativo' => static::informativo($sessao),
            'ajusteabertura' => $ajuste($sessao->codpagamentoabertura),
            'ajustefechamento' => $sessao->aberto() ? 0.0 : $ajuste($sessao->codpagamentofechamento),
            'itens' => $itens,
            'avulsos' => $avulsos,
        ];
    }
}
