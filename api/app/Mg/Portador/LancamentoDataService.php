<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mg\Auditoria\AuditoriaService;
use Mg\Caixa\CaixaItemService;
use Mg\Caixa\CaixaService;
use Mg\Cheque\Cheque;
use Mg\Conferencia\ConferenciaService;
use Mg\Conferencia\PagamentoCorrecaoService;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Ocorrencia\OcorrenciaService;
use Mg\Pagamento\Pagamento;
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
 * lancamento. Justificativa obrigatoria, com o antes/depois na auditoria. So' o
 * gestor do portador (o cartao, quem confere a maquineta; no PDV, a gaveta
 * dele). Sem transacao interna: o controller abre.
 */
class LancamentoDataService
{
    // tolerancia do relogio do aparelho: ate' 5 minutos a frente vira agora
    const TOLERANCIA_FUTURO_MIN = 5;

    // nao no futuro (a mesma tolerancia em todo lugar) nem antes do razao
    private static function exigirLimites(Carbon $data): Carbon
    {
        $agora = Carbon::now();
        if ($data->gt($agora->copy()->addMinutes(static::TOLERANCIA_FUTURO_MIN))) {
            abort(422, 'A data não pode ser no futuro.');
        }
        if ($data->gt($agora)) {
            $data = $agora->copy();
        }
        if ($data->lt(ConferenciaService::inicio())) {
            abort(422, 'A data não pode ser antes do início do razão (' . ConferenciaService::inicio()->format('d/m/Y') . ').');
        }
        return $data;
    }

    // a data nova do alterar data
    private static function exigirData(Carbon $data): Carbon
    {
        return static::exigirLimites($data->copy()->startOfMinute());
    }

    // a data informada ao lancar (baixa de titulo, vale; contas e PDV), com
    // hora: e' a do fato. Sem data, agora; um pouco a frente (relogio do
    // aparelho), agora; no futuro ou antes do inicio do razao, recusa. O
    // periodo de cada portador sai dela: periodo fechado e gaveta sem sessao
    // naquela hora tambem recusam (PortadorMovimentoService, CaixaService)
    public static function dataInformada(?string $transacao): Carbon
    {
        if (empty($transacao)) {
            return Carbon::now()->startOfSecond();
        }
        return static::exigirLimites(Carbon::parse($transacao));
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

    // o papel em cada portador cuja ponta muda: quem administra so' um lado
    // nao mexe no outro. Gestor altera com o periodo aberto ou pendente;
    // operador so' com o periodo de onde sai e o para onde vai abertos (Fabio,
    // 10/10/2026). Fechado, ninguem.
    // `$livre`: a gaveta do PDV ja' resolvida (0 = nenhuma; null = do request)
    private static function podePortadores(array $codportadores, ?int $livre = null, string $papel = PortadorUsuario::PAPEL_GESTOR): bool
    {
        $cods = array_unique(array_filter($codportadores));
        if (empty($cods)) {
            return false;
        }
        $livre ??= PortadorAutorizador::livre();
        foreach ($cods as $cod) {
            if ($cod != $livre && !PortadorAutorizador::pode((int) $cod, $papel)) {
                return false;
            }
        }
        return true;
    }

    // periodo do portador ou da maquineta sem fim (aberto, nao pendente)
    private static function aberto($periodo): bool
    {
        return $periodo !== null && $periodo->aberto();
    }

    const MOTIVO_OPERADOR = 'Alterar a data: o operador só altera com o período de onde sai e o para onde vai abertos; com período pendente, só o gestor.';

    // ==== pagamento ====

    // os portadores do pagamento; no cartao (que nao entra no razao do
    // portador), o da adquirente da maquineta
    private static function portadoresDoPagamento(Pagamento $pag): array
    {
        $portadores = [$pag->codportadororigem, $pag->codportadordestino];
        if (!empty($pag->codmaquineta)) {
            $portadores[] = optional(\Mg\Pagamento\PagamentoTituloService::portadorDaMaquineta($pag->Maquineta))->codportador;
        }
        return array_values(array_filter($portadores));
    }

    // os periodos em que o pagamento esta' hoje: as linhas do razao, a sessao
    // da gaveta e o periodo da maquineta (o do cancelamento, no cancelamento)
    private static function periodosAtuaisDoPagamento(Pagamento $pag, bool $cancelamento = false): array
    {
        if ($cancelamento) {
            return [$pag->MaquinetaLoteCancelamento];
        }
        $ret = PortadorPeriodo::whereIn('codportadorperiodo', static::periodosDoPagamento($pag))->get()->all();
        if (!empty($pag->codportadorperiodo)) {
            $ret[] = PortadorPeriodo::find($pag->codportadorperiodo);
        }
        if (!empty($pag->codmaquinetalote)) {
            $ret[] = $pag->MaquinetaLote;
        }
        return $ret;
    }

    // os periodos para onde o pagamento iria na data nova
    private static function periodosNaData(Pagamento $pag, Carbon $data, bool $cancelamento = false): array
    {
        if ($cancelamento) {
            return [MaquinetaLoteService::daData($pag->codmaquineta, $data)];
        }
        $ret = [];
        foreach (array_filter([$pag->codportadororigem, $pag->codportadordestino]) as $cod) {
            if (in_array((int) $pag->meio, PortadorMovimentoService::MEIOS)) {
                $ret[] = static::periodoDaData(Portador::findOrFail($cod), $data);
            }
        }
        if (!empty($pag->codportadorperiodo)) {
            $ret[] = CaixaService::sessaoDoMomento(PortadorPeriodo::findOrFail($pag->codportadorperiodo)->Portador, $data);
        }
        if (!empty($pag->codmaquinetalote) && !empty($pag->codmaquineta)) {
            $ret[] = MaquinetaLoteService::daData($pag->codmaquineta, $data);
        }
        return $ret;
    }

    // pode alterar a data (o botao na tela): gestor; ou operador com os
    // periodos em que o pagamento esta' abertos (o destino confere ao gravar)
    public static function podePagamento(Pagamento $pag, ?int $livre = null): bool
    {
        $portadores = static::portadoresDoPagamento($pag);
        if (static::podePortadores($portadores, $livre)) {
            return true;
        }
        return static::podePortadores($portadores, $livre, PortadorUsuario::PAPEL_OPERADOR)
            && collect(static::periodosAtuaisDoPagamento($pag))->every(fn ($p) => static::aberto($p));
    }

    // ao gravar: gestor; ou operador com a origem e o destino abertos
    private static function autorizarPagamento(Pagamento $pag, Carbon $data, bool $cancelamento = false): void
    {
        $portadores = static::portadoresDoPagamento($pag);
        if (static::podePortadores($portadores)) {
            return;
        }
        if (!static::podePortadores($portadores, null, PortadorUsuario::PAPEL_OPERADOR)) {
            abort(403, 'Alterar a data: só o operador ou o gestor de cada portador do pagamento (no cartão, o da adquirente).');
        }
        $periodos = array_merge(
            static::periodosAtuaisDoPagamento($pag, $cancelamento),
            static::periodosNaData($pag, $data, $cancelamento)
        );
        if (!collect($periodos)->every(fn ($p) => static::aberto($p))) {
            abort(403, static::MOTIVO_OPERADOR);
        }
    }

    // o que a data muda no pagamento, para a auditoria
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

    private static function registrarPagamento(Pagamento $pag, int $tipo, array $antes, string $justificativa): void
    {
        $aud = AuditoriaService::registrar('tblpagamento', $pag->codpagamento, $tipo, $antes, static::fotoPagamento($pag), $justificativa);
        // em PDV monitorado vai para o gerente conferir (TASK-205)
        OcorrenciaService::correcao([$aud]);
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
            static::autorizarPagamento($pag, $data);
        }
        // a devolucao (cancelamento no cartao, devolucao de PIX) nao vem antes
        // do pagamento original, nem o original depois da devolucao
        if (!empty($pag->codpagamentoorigem)) {
            $original = Pagamento::find($pag->codpagamentoorigem);
            if ($original && $original->transacao && $data->lt($original->transacao->copy()->startOfMinute())) {
                abort(422, 'A devolução não pode ser antes do pagamento original (' . $original->transacao->format('d/m/Y H:i') . ').');
            }
        }
        $primeiraDevolucao = Pagamento::where('codpagamentoorigem', $pag->codpagamento)
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)
            ->min('transacao');
        if ($primeiraDevolucao && $data->gt(Carbon::parse($primeiraDevolucao))) {
            abort(422, 'O pagamento não pode ficar depois da devolução dele (' . Carbon::parse($primeiraDevolucao)->format('d/m/Y H:i') . ').');
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

        static::registrarPagamento($pag, AuditoriaService::TIPO_DATA_ALTERADA, $antes, $justificativa);
        return $pag->fresh();
    }

    // a data do cancelamento do cartao: o periodo do cancelamento segue a
    // data, sem mexer na venda
    public static function alterarCancelamento(Pagamento $pag, Carbon $data, ?string $justificativa): Pagamento
    {
        $justificativa = static::justificativa($justificativa);
        $data = static::exigirData($data);
        static::autorizarPagamento($pag, $data, true);
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
        static::registrarPagamento($pag, AuditoriaService::TIPO_DATA_CANCELAMENTO_ALTERADA, $antes, $justificativa);
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

    // a entrada de item que sai do periodo: o disponivel do item la' (saldo
    // inicial + entradas - saidas, com ela) tem de cobrir o que ela leva
    private static function exigirEntradaLivre(PortadorMovimento $entrada): void
    {
        $periodo = $entrada->PortadorPeriodo;
        $disponivel = CaixaItemService::disponivel($periodo, PortadorPeriodoService::anterior($periodo))[$entrada->codcaixaitem] ?? [];
        $chave = fn ($l) => number_format((float) $l['preco'], 2, '.', '') . '|' . mb_strtolower($l['descricao'] ?? '');
        $tem = [];
        foreach ($disponivel as $l) {
            $tem[$chave($l)] = $l['quantidade'];
        }
        foreach ($entrada->itens ?? [] as $l) {
            if (($tem[$chave($l)] ?? 0) < $l['quantidade']) {
                $nome = trim(optional($entrada->CaixaItem)->item . ' ' . ($l['descricao'] ?? '') . ' ' . number_format((float) $l['preco'], 2, ',', '.'));
                abort(422, "A saída de {$nome} deste período usa esta entrada: mova ou cancele a saída antes.");
            }
        }
    }

    // pode alterar a data (o botao na tela): gestor; ou operador com os
    // periodos das linhas abertos (o destino confere ao gravar)
    public static function podeMovimento(PortadorMovimento $mov, ?int $livre = null): bool
    {
        if ($mov->tipo == PortadorMovimento::TIPO_PAGAMENTO) {
            return $mov->Pagamento && static::podePagamento($mov->Pagamento, $livre);
        }
        $cods = [$mov->codportador];
        if ($mov->tipo == PortadorMovimento::TIPO_TRANSFERENCIA) {
            $cods[] = optional($mov->Par)->codportador;
        }
        if (static::podePortadores($cods, $livre)) {
            return true;
        }
        return static::podePortadores($cods, $livre, PortadorUsuario::PAPEL_OPERADOR)
            && collect(static::linhas($mov))->every(fn ($l) => static::aberto($l->PortadorPeriodo));
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
        $linhas = static::linhas($mov);
        // gestor; ou operador com o periodo de onde sai e o para onde vai abertos
        $cods = array_map(fn ($l) => $l->codportador, $linhas);
        if (!static::podePortadores($cods)) {
            if (!static::podePortadores($cods, null, PortadorUsuario::PAPEL_OPERADOR)) {
                abort(403, 'Alterar a data: só o operador ou o gestor do portador.');
            }
            foreach ($linhas as $l) {
                if (!static::aberto($l->PortadorPeriodo) || !static::aberto(static::periodoDaData($l->Portador, $data))) {
                    abort(403, static::MOTIVO_OPERADOR);
                }
            }
        }
        static::travar(array_map(fn ($l) => $l->codportador, $linhas));
        $mexidos = collect();
        $auditorias = [];
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
            // a entrada de item so' sai do periodo se a saida que ja' saiu
            // dele continua coberta (o disponivel la' cobre o que ela leva)
            if ($l->tipo == PortadorMovimento::TIPO_ITEM && $l->valor > 0 && $destino->codportadorperiodo != $l->codportadorperiodo) {
                static::exigirEntradaLivre($l);
            }
            $l->transacao = $data;
            $l->codportadorperiodo = $destino->codportadorperiodo;
            $l->save();
            $mexidos->push($l->codportadorperiodo);
            $auditorias[] = AuditoriaService::registrar(
                'tblportadormovimento',
                $l->codportadormovimento,
                AuditoriaService::TIPO_DATA_ALTERADA,
                $antes,
                ['transacao' => $l->transacao->format('Y-m-d H:i:s'), 'codportadorperiodo' => $l->codportadorperiodo],
                $justificativa
            );
        }
        // as duas pontas da transferencia sao uma correcao so' (TASK-205)
        OcorrenciaService::correcao($auditorias);
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
        $tipo = $campo == 'cancelamento'
            ? AuditoriaService::TIPO_DATA_CANCELAMENTO_ALTERADA
            : AuditoriaService::TIPO_DATA_ALTERADA;
        return array_map(
            fn ($a) => static::alteracao($a, $campo),
            AuditoriaService::ultimas('tblpagamento', $codpagamentos, $tipo)
        );
    }

    // a ultima data alterada de cada movimento: [codportadormovimento => {...}]
    public static function alteracoesMovimento(array $codportadormovimentos): array
    {
        return array_map(
            fn ($a) => static::alteracao($a, 'transacao'),
            AuditoriaService::ultimas('tblportadormovimento', $codportadormovimentos, AuditoriaService::TIPO_DATA_ALTERADA)
        );
    }

    private static function alteracao($auditoria, string $campo): array
    {
        return [
            'de' => $auditoria->antes[$campo] ?? null,
            'usuario' => $auditoria->usuariocriacao,
            'justificativa' => $auditoria->justificativa,
        ];
    }
}
