<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mg\Caixa\CaixaItemService;
use Mg\Caixa\CaixaService;
use Mg\Cheque\Cheque;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Conferencia\ConferenciaService;
use Mg\Conferencia\PagamentoCorrecaoService;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoCorrecao;
use Mg\Pagamento\PagamentoService;
use Mg\Titulo\MovimentoTituloService;

/**
 * Alterar a data de um lancamento (TASK-204): a data, com hora, manda no
 * periodo, em qualquer portador (banco, especie, maquineta). Mudar a data leva
 * a linha para o periodo daquela data; alterar num lugar altera todos:
 *   Pagamento: a transacao, a sessao da gaveta, o periodo da maquineta, as
 *   linhas do razao (sincronizar) e os movimentos dos titulos (a liquidacao
 *   recalcula); o titulo que nasceu com ele (vale, adiantamento) leva a
 *   emissao junto. Negocio e NFC-e ficam com a data deles. Valores nao mudam.
 *   Cancelamento do cartao: a data e o periodo do cancelamento, sem mexer na
 *   venda.
 *   Movimento (ajuste, item, bordero, transferencia): a transacao e o periodo
 *   de cada linha; a transferencia muda as duas pontas juntas.
 * Periodo fechado (caixa, banco) ou conferido (maquineta) nao recebe nem perde
 * lancamento. Justificativa obrigatoria, com o antes/depois na trilha. So' o
 * gestor do portador (o cartao, quem confere a maquineta; no PDV, a gaveta
 * dele). Sem transacao interna: o controller abre.
 */
class LancamentoDataService
{
    // a data nova: nao no futuro, nem antes do razao (go-live)
    private static function exigirData(Carbon $data): Carbon
    {
        $data = $data->copy()->startOfMinute();
        if ($data->gt(Carbon::now())) {
            abort(422, 'A data não pode ser no futuro.');
        }
        if ($data->lt(ConferenciaService::inicio())) {
            abort(422, 'A data não pode ser antes do início do razão (' . ConferenciaService::inicio()->format('d/m/Y') . ').');
        }
        return $data;
    }

    // a data informada ao lancar (baixa de titulo, vale; contas e PDV), com
    // hora: sem data, agora; um pouco a frente (relogio do cliente), agora;
    // no futuro, recusa. O periodo de cada portador sai dela (a gaveta recusa
    // sessao fechada)
    public static function dataInformada(?string $transacao): Carbon
    {
        $agora = Carbon::now()->startOfSecond();
        if (empty($transacao)) {
            return $agora;
        }
        $data = Carbon::parse($transacao);
        if ($data->gt($agora->copy()->addMinutes(5))) {
            abort(422, 'A data não pode ser no futuro.');
        }
        return $data->gt($agora) ? $agora : $data;
    }

    private static function justificativa(?string $justificativa): string
    {
        $justificativa = trim($justificativa ?? '');
        if (mb_strlen($justificativa) < 5) {
            abort(422, 'Informe a justificativa da alteração da data.');
        }
        return mb_substr($justificativa, 0, 300);
    }

    private static function exigirNaoFechado(?PortadorPeriodo $periodo): void
    {
        if ($periodo && $periodo->fechado()) {
            abort(422, 'O ' . PortadorPeriodoService::descricao($periodo) . " de {$periodo->Portador->portador} já foi fechado: reabra antes.");
        }
    }

    // o periodo do portador que contem a data, nao fechado (na especie, a
    // sessao; nos demais, o da faixa ou o corrente)
    public static function periodoDaData(Portador $portador, Carbon $data): PortadorPeriodo
    {
        $periodo = $portador->ehCaixa()
            ? CaixaService::sessaoDoMomento($portador, $data)
            : PortadorPeriodoService::doMomento($portador, $data);
        static::exigirNaoFechado($periodo);
        return $periodo;
    }

    private static function travar(array $codportadores): void
    {
        $cods = array_values(array_unique(array_filter($codportadores)));
        sort($cods);
        Portador::whereIn('codportador', $cods)->orderBy('codportador')->lockForUpdate()->get();
    }

    // recalcula do periodo mais antigo mexido de cada portador em diante
    private static function recalcular(Collection $codperiodos): void
    {
        PortadorPeriodo::whereIn('codportadorperiodo', $codperiodos->filter()->unique()->values())
            ->orderBy('codportador')
            ->orderBy('inicio')
            ->get()
            ->unique('codportador')
            ->each(fn ($p) => PortadorPeriodoService::recalcular($p));
    }

    // gestor de cada portador cuja ponta muda (a gaveta do PDV, o proprio
    // PDV): quem administra so' um lado nao mexe no outro
    // `$livre`: a gaveta do PDV ja' resolvida (0 = nenhuma; null = do request)
    private static function podePortadores(array $codportadores, ?int $livre = null): bool
    {
        $cods = array_unique(array_filter($codportadores));
        if (empty($cods)) {
            return false;
        }
        $livre ??= PortadorAutorizador::livre();
        foreach ($cods as $cod) {
            if ($cod != $livre && !PortadorAutorizador::pode((int) $cod, PortadorUsuario::PAPEL_GESTOR)) {
                return false;
            }
        }
        return true;
    }

    // ==== pagamento ====

    // cartao: quem confere a maquineta (o cartao nao entra no razao do
    // portador); o resto, o gestor de cada portador do pagamento
    public static function podePagamento(Pagamento $pag, ?int $livre = null): bool
    {
        if (!empty($pag->codmaquineta) && ConferenciaAutorizador::pode(ConferenciaService::filialDoPagamento($pag))) {
            return true;
        }
        return static::podePortadores([$pag->codportadororigem, $pag->codportadordestino], $livre);
    }

    private static function autorizarPagamento(Pagamento $pag): void
    {
        if (!static::podePagamento($pag)) {
            abort(403, 'Alterar a data: só o gestor de cada portador do pagamento (no cartão, quem confere a maquineta).');
        }
    }

    // o que a data muda no pagamento, para a trilha
    private static function fotoPagamento(Pagamento $pag): array
    {
        return [
            'transacao' => optional($pag->transacao)->format('Y-m-d H:i:s'),
            'codportadorperiodo' => $pag->codportadorperiodo,
            'codmaquinetalote' => $pag->codmaquinetalote,
            'cancelamento' => optional($pag->cancelamento)->format('Y-m-d H:i:s'),
            'codmaquinetalotecancelamento' => $pag->codmaquinetalotecancelamento,
        ];
    }

    private static function registrarPagamento(Pagamento $pag, array $antes, string $justificativa): void
    {
        PagamentoCorrecao::create([
            'codpagamento' => $pag->codpagamento,
            'antes' => $antes,
            'depois' => static::fotoPagamento($pag),
            'justificativa' => $justificativa,
        ]);
    }

    // os periodos do portador onde o pagamento tem linha (antes de mudar)
    private static function periodosDoPagamento(Pagamento $pag): Collection
    {
        return PortadorMovimento::where('codpagamento', $pag->codpagamento)->whereNull('inativo')->pluck('codportadorperiodo');
    }

    // `$autorizado`: quem chama ja' autorizou com a regra dele (o lapis do
    // recebimento, PagamentoTituloAutorizador)
    public static function alterarPagamento(Pagamento $pag, Carbon $data, ?string $justificativa, bool $autorizado = false): Pagamento
    {
        $justificativa = static::justificativa($justificativa);
        $data = static::exigirData($data);
        if (!$autorizado) {
            static::autorizarPagamento($pag);
        }
        static::travar([$pag->codportadororigem, $pag->codportadordestino]);
        // o Conferir da maquineta trava a maquineta: nao entra cartao no
        // periodo que esta' sendo conferido
        if (!empty($pag->codmaquineta)) {
            MaquinetaLoteService::travar($pag->codmaquineta);
        }
        $pag->refresh();
        if ($pag->estado != PagamentoService::ESTADO_EFETIVADO) {
            abort(422, 'Só o pagamento efetivado muda de data.');
        }
        // de onde sai: a sessao, o periodo da maquineta e a conferencia abertos
        PagamentoCorrecaoService::exigirAberta($pag);
        if ($pag->transacao && $pag->transacao->format('Y-m-d H:i') == $data->format('Y-m-d H:i')) {
            return $pag;
        }
        $antes = static::fotoPagamento($pag);

        $pag->transacao = $data;
        // a sessao da gaveta e o periodo da maquineta seguem a data
        if (!empty($pag->codportadorperiodo)) {
            $caixa = PortadorPeriodo::findOrFail($pag->codportadorperiodo)->Portador;
            $pag->codportadorperiodo = CaixaService::sessaoDoMomento($caixa, $data)->codportadorperiodo;
        }
        if (!empty($pag->codmaquinetalote) && !empty($pag->codmaquineta)) {
            $pag->codmaquinetalote = MaquinetaLoteService::daData($pag->codmaquineta, $data)->codmaquinetalote;
        }
        PagamentoService::validar($pag);
        $pag->save();
        // as linhas do razao (recusa periodo fechado, de onde sai e para onde vai)
        PortadorMovimentoService::sincronizar($pag);

        // os titulos: a data do movimento (a liquidacao recalcula); o que
        // nasceu com o pagamento (vale, adiantamento) leva a emissao junto,
        // sem passar do vencimento nem de baixa ja' feita nele
        $dia = $data->format('Y-m-d');
        foreach ($pag->MovimentoTituloS as $mov) {
            $titulo = $mov->Titulo;
            if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
                if ($titulo->vencimento && $titulo->vencimento->format('Y-m-d') < $dia) {
                    abort(422, "O título {$titulo->numero} vence em {$titulo->vencimento->format('d/m/Y')}: a emissão não pode ser depois.");
                }
                // baixa de outro pagamento, que nao foi estornada
                $baixa = $titulo->MovimentoTituloS()
                    ->where(fn ($q) => $q->whereNull('codpagamento')->orWhere('codpagamento', '<>', $pag->codpagamento))
                    ->whereNull('codmovimentotituloestorno')
                    ->whereNotIn('codtipomovimentotitulo', MovimentoTituloService::TIPOS_ESTORNO)
                    ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('tblmovimentotitulo as e')
                        ->whereColumn('e.codmovimentotituloestorno', 'tblmovimentotitulo.codmovimentotitulo'))
                    ->min('transacao');
                if ($baixa && Carbon::parse($baixa)->format('Y-m-d') < $dia) {
                    abort(422, "O título {$titulo->numero} já tem baixa em " . Carbon::parse($baixa)->format('d/m/Y') . ': a emissão não pode ser depois.');
                }
                $titulo->transacao = $dia;
                $titulo->emissao = $dia;
                $titulo->save();
            }
            $mov->transacao = $dia;
            $mov->save();
            MovimentoTituloService::recalcular($titulo);
        }
        // o cheque recebido leva a data junto
        Cheque::where('codpagamento', $pag->codpagamento)->update(['transacao' => $data]);

        static::registrarPagamento($pag, $antes, $justificativa);
        return $pag->fresh();
    }

    // a data do cancelamento do cartao: o periodo do cancelamento segue a
    // data, sem mexer na venda
    public static function alterarCancelamento(Pagamento $pag, Carbon $data, ?string $justificativa): Pagamento
    {
        $justificativa = static::justificativa($justificativa);
        $data = static::exigirData($data);
        static::autorizarPagamento($pag);
        if (!empty($pag->codmaquineta)) {
            MaquinetaLoteService::travar($pag->codmaquineta);
        }
        $pag->refresh();
        if ($pag->estado != PagamentoService::ESTADO_CANCELADO || empty($pag->codmaquinetalotecancelamento)) {
            abort(422, 'Só o cancelamento do cartão na maquineta tem data própria.');
        }
        if ($pag->transacao && $data->lt($pag->transacao->copy()->startOfMinute())) {
            abort(422, 'O cancelamento não pode ser antes do pagamento.');
        }
        MaquinetaLoteService::exigirNaoConferido($pag->MaquinetaLoteCancelamento);
        $antes = static::fotoPagamento($pag);
        $pag->cancelamento = $data;
        $pag->codmaquinetalotecancelamento = MaquinetaLoteService::daData($pag->codmaquineta, $data)->codmaquinetalote;
        $pag->save();
        static::registrarPagamento($pag, $antes, $justificativa);
        return $pag->fresh();
    }

    // ==== movimento do portador ====

    // as linhas que mudam juntas: a transferencia, as duas pontas
    private static function linhas(PortadorMovimento $mov): array
    {
        return $mov->tipo == PortadorMovimento::TIPO_TRANSFERENCIA
            ? PortadorLancamentoService::lados($mov)
            : [$mov];
    }

    public static function podeMovimento(PortadorMovimento $mov, ?int $livre = null): bool
    {
        if ($mov->tipo == PortadorMovimento::TIPO_PAGAMENTO) {
            return $mov->Pagamento && static::podePagamento($mov->Pagamento, $livre);
        }
        $cods = [$mov->codportador];
        if ($mov->tipo == PortadorMovimento::TIPO_TRANSFERENCIA) {
            $cods[] = optional($mov->Par)->codportador;
        }
        return static::podePortadores($cods, $livre);
    }

    // devolve os codportadorperiodo mexidos (de onde saiu e para onde foi)
    public static function alterarMovimento(PortadorMovimento $mov, Carbon $data, ?string $justificativa): Collection
    {
        if ($mov->tipo == PortadorMovimento::TIPO_PAGAMENTO) {
            $pag = $mov->Pagamento;
            $antes = static::periodosDoPagamento($pag);
            static::alterarPagamento($pag, $data, $justificativa);
            return $antes->merge(static::periodosDoPagamento($pag));
        }
        $justificativa = static::justificativa($justificativa);
        $data = static::exigirData($data);
        if (!static::podeMovimento($mov)) {
            abort(403, 'Alterar a data: só o gestor do portador.');
        }
        $linhas = static::linhas($mov);
        static::travar(array_map(fn ($l) => $l->codportador, $linhas));
        $mexidos = collect();
        foreach ($linhas as $l) {
            $l->refresh();
            if ($l->estado == PortadorMovimento::ESTADO_CANCELADO) {
                abort(422, 'Lançamento cancelado não muda de data.');
            }
            static::exigirNaoFechado($l->PortadorPeriodo);
        }
        foreach ($linhas as $l) {
            $antes = ['transacao' => $l->transacao->format('Y-m-d H:i:s'), 'codportadorperiodo' => $l->codportadorperiodo];
            $mexidos->push($l->codportadorperiodo);
            $destino = static::periodoDaData($l->Portador, $data);
            // a saida de item so' vai para onde o item esta' no caixa, como ao lancar
            if ($l->tipo == PortadorMovimento::TIPO_ITEM && $l->valor < 0 && $destino->codportadorperiodo != $l->codportadorperiodo) {
                PortadorLancamentoService::exigirDisponivel($destino, $l->CaixaItem, $l->itens ?? []);
            }
            $l->transacao = $data;
            $l->codportadorperiodo = $destino->codportadorperiodo;
            $l->save();
            $mexidos->push($l->codportadorperiodo);
            PortadorMovimentoCorrecao::create([
                'codportadormovimento' => $l->codportadormovimento,
                'antes' => $antes,
                'depois' => ['transacao' => $l->transacao->format('Y-m-d H:i:s'), 'codportadorperiodo' => $l->codportadorperiodo],
                'justificativa' => $justificativa,
            ]);
        }
        static::recalcular($mexidos);
        if (in_array($mov->tipo, [PortadorMovimento::TIPO_ITEM, PortadorMovimento::TIPO_MAQUINETA])) {
            CaixaItemService::recalcularSaldos($mov->codportador);
        }
        return $mexidos->unique()->values();
    }

    // a ultima data alterada de cada pagamento, para mostrar na linha:
    // [codpagamento => {de, usuario, justificativa}]; `campo`: transacao ou
    // cancelamento
    public static function alteracoesPagamento(array $codpagamentos, string $campo = 'transacao'): array
    {
        if (empty($codpagamentos)) {
            return [];
        }
        return PagamentoCorrecao::with('UsuarioCriacao:codusuario,usuario')
            ->whereIn('codpagamento', array_values(array_unique($codpagamentos)))
            ->whereRaw("antes->>? is distinct from depois->>?", [$campo, $campo])
            ->orderBy('codpagamentocorrecao')
            ->get()
            ->keyBy('codpagamento')
            ->map(fn ($c) => static::alteracao($c, $campo))
            ->all();
    }

    // a ultima data alterada de cada movimento: [codportadormovimento => {...}]
    public static function alteracoesMovimento(array $codportadormovimentos): array
    {
        if (empty($codportadormovimentos)) {
            return [];
        }
        return PortadorMovimentoCorrecao::with('UsuarioCriacao:codusuario,usuario')
            ->whereIn('codportadormovimento', array_values(array_unique($codportadormovimentos)))
            ->orderBy('codportadormovimentocorrecao')
            ->get()
            ->keyBy('codportadormovimento')
            ->map(fn ($c) => static::alteracao($c, 'transacao'))
            ->all();
    }

    private static function alteracao($correcao, string $campo): array
    {
        return [
            'de' => $correcao->antes[$campo] ?? null,
            'usuario' => optional($correcao->UsuarioCriacao)->usuario,
            'justificativa' => $correcao->justificativa,
        ];
    }
}
