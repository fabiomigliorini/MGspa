<?php

namespace Mg\Pagamento;

use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use Mg\Titulo\MovimentoTituloService;
use Mg\Titulo\TipoTituloService;
use Mg\Titulo\TituloValeService;

/**
 * O recibo do pagamento (TASK-216), um so' layout para o A5 e para o cupom da termica, no jeito do
 * bloco de papel: sai do PAGAMENTO, seja qual for a origem (baixa, vale, adiantamento, venda,
 * acerto de RH). Quem recebe o dinheiro declara e assina: entrou (o cliente pagou), a loja; saiu
 * (a loja pagou), a contraparte; no encontro de contas, os dois. Cada modelo tem o seu texto:
 *
 *   vale        VALE do colaborador, com a nota promissoria (A5 em pe)
 *   adiantamento   ADIANTAMENTO a fornecedor
 *   pagamento   a loja pagou titulos
 *   recebimento o cliente pagou titulos
 *   encontro    ENCONTRO DE CONTAS: titulos a receber e a pagar no mesmo pagamento
 *   venda       pagamento de venda, sem titulo
 *   generico    o que nao e' de nenhum modelo (sobra livre, devolucao de pagamento...)
 *
 * O adiantamento de cliente nao tem recibo: o papel dele e' o vale de credito do titulo
 * (TituloValeService). O "Referente a" escrito no pagamento sai como esta'; vazio, nao sai; a
 * observacao e' interna. Um recebimento com varias formas (varios pagamentos) sai num recibo so'.
 */
class PagamentoReciboService
{
    const MESES = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto',
        'setembro', 'outubro', 'novembro', 'dezembro'];

    public static function carregar(array $codpagamentos): Collection
    {
        $pags = Pagamento::with([
            'Pessoa.Cidade.Estado',
            'Negocio.Pessoa.Cidade.Estado',
            'Filial.Pessoa.Cidade.Estado',
            'Maquineta:codmaquineta,apelido',
            'UsuarioCriacao:codusuario,usuario',
            'MovimentoTituloS.Titulo.TipoTitulo:codtipotitulo,tipotitulo',
            'MovimentoTituloS.Titulo.PeriodoColaboradorS.ColaboradorRubricaS',
        ])->whereIn('codpagamento', $codpagamentos)->orderBy('codpagamento')->get();
        if ($pags->isEmpty()) {
            abort(404, 'Pagamento não encontrado!');
        }
        if ($pags->every(fn($p) => $p->estado == PagamentoService::ESTADO_CANCELADO)) {
            abort(422, 'Pagamento cancelado: não tem recibo.');
        }
        return $pags;
    }

    public static function montar(Collection $pags): array
    {
        $primeiro = $pags->first();
        // o acerto de RH: o beneficio nao e' titulo; monta do acerto (o mesmo documento)
        if (!empty($primeiro->codperiodocolaboradoracerto)) {
            return static::montarAcerto($primeiro);
        }
        $entrada = PagamentoListaService::operacao($primeiro) != 'DB';
        // o sinal das baixas no sentido do recibo: recebimento baixa com total negativo
        $sinal = $entrada ? -1 : 1;

        $linhas = [];
        $lancados = [];
        $codtipotitulo = null;
        foreach ($pags as $pag) {
            foreach (PagamentoTituloService::baixas($pag) as $mov) {
                $titulo = $mov->Titulo;
                if (!$titulo) {
                    continue;
                }
                $l = &$linhas[$titulo->codtitulo];
                $l = $l ?? ['titulo' => $titulo, 'lancado' => null, 'juros' => 0, 'multa' => 0, 'desconto' => 0, 'total' => 0];
                // vale colaborador / adiantamento: o pagamento nasceu com o titulo
                if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
                    $lancados[$titulo->TipoTitulo->tipotitulo ?? ''] = true;
                    $codtipotitulo = (int) $titulo->codtipotitulo;
                    $l['lancado'] = $titulo->TipoTitulo->tipotitulo ?? '';
                }
                // juros, multa e desconto vem na linha da baixa (positivos); os tipos
                // 400/401/500 so' aparecem em historico que nao foi convertido
                switch ((int) $mov->codtipomovimentotitulo) {
                    case MovimentoTituloService::TIPO_JUROS:
                        $l['juros'] -= $sinal * $mov->principal;
                        break;
                    case MovimentoTituloService::TIPO_MULTA:
                        $l['multa'] -= $sinal * $mov->principal;
                        break;
                    case MovimentoTituloService::TIPO_DESCONTO:
                        $l['desconto'] += $sinal * $mov->principal;
                        break;
                    default:
                        $l['total'] += $sinal * $mov->total;
                        $l['juros'] += $mov->juros;
                        $l['multa'] += $mov->multa;
                        $l['desconto'] += $mov->desconto;
                }
                unset($l);
            }
        }
        $linhas = array_values(array_filter($linhas, fn($l) => round($l['total'], 2) != 0));
        foreach ($linhas as &$l) {
            // o titulo que a contraparte pagou a loja (a receber) ou que a loja pagou (a pagar);
            // 'total' esta' no sentido do recibo, 'valor' e' o da linha, sempre positivo
            $l['sentido'] = $sinal * $l['total'] < 0 ? 'receber' : 'pagar';
            $l['valor'] = abs($l['total']);
            $l['principal'] = $l['valor'] - $l['juros'] - $l['multa'] + $l['desconto'];
            $l['descricao'] = $l['titulo']->numero . ($l['lancado'] ? ' (' . $l['lancado'] . ')' : '');
            $l['vencimento'] = $l['titulo']->vencimento?->format('d/m/Y');
            // titulo de folha (acerto de RH): as rubricas do colaborador, quando a baixa e' o
            // beneficio inteiro (dividido em varios acertos, a quebra enganaria o total)
            $rubricas = $l['titulo']->PeriodoColaboradorS->flatMap->ColaboradorRubricaS
                ->where('valorcalculado', '>', 0)->values();
            $l['rubricas'] = round((float) $rubricas->sum('valorcalculado'), 2) == round($l['principal'], 2)
                ? $rubricas : collect();
        }
        unset($l);

        // a filial do pagamento: a do PDV; fora dele, a do portador (no cartao, a da maquineta)
        $empresa = $primeiro->Filial?->Pessoa;
        $pessoa = $primeiro->Pessoa ?? $primeiro->Negocio?->Pessoa;

        $baixados = collect($linhas)->whereNull('lancado')->count();
        $baixa = $baixados > 0;
        $total = $pags->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->sum('total');
        // o que entrou e nao esta amarrado a titulo: a tabela fecha com o valor do recibo
        $livre = $linhas ? round($total - collect($linhas)->sum('total'), 2) : 0;
        $mesmoSentido = collect($linhas)->every(fn($l) => $l['total'] > 0);

        // o pagamento inteiro e' o lancamento (sem baixa junto, sem sobra livre)
        $soLancamento = !$baixa && $livre == 0 && $linhas;
        // adiantamento de cliente: o papel dele e' o vale de credito do titulo (o caixa bipa para
        // usar), enquanto tiver saldo; usado todo, sai o recibo generico
        $valeCredito = $soLancamento && $codtipotitulo == TipoTituloService::TIPO_ADIANTAMENTO_CLIENTE
            && (float) ($linhas[0]['titulo']->saldo ?? 0) < 0
            ? (int) $linhas[0]['titulo']->codtitulo : null;
        $modelo = match (true) {
            $soLancamento && $codtipotitulo == TipoTituloService::TIPO_VALE_COLABORADOR => 'vale',
            $soLancamento && $codtipotitulo == TipoTituloService::TIPO_ADIANTAMENTO_FORNECEDOR => 'adiantamento',
            $baixa && !$lancados && !$mesmoSentido => 'encontro',
            $baixa && !$lancados && !$entrada => 'pagamento',
            $baixa && !$lancados && $entrada => 'recebimento',
            !$linhas && !empty($primeiro->codnegocio) => 'venda',
            default => 'generico',
        };

        $cidade = $empresa?->Cidade;
        $vencimento = in_array($modelo, ['vale', 'adiantamento']) ? ($linhas[0]['titulo']->vencimento ?? null) : null;
        // a contraparte pessoa fisica fala no singular
        $singular = (bool) ($pessoa->fisica ?? false);
        $titulos = $baixados > 1 ? 'dos títulos abaixo' : 'do título abaixo';
        $da = fn($p) => ($p->pessoa ?? '') . (($d = static::documento($p)) ? ', ' . $d : '');

        // quem recebeu fala: a loja (entrou) ou a contraparte (saiu); sem a outra parte, sem o "de"
        $recebeu = $entrada
            ? 'Recebemos' . ($pessoa ? ' de ' . $da($pessoa) : '')
            : ($singular ? 'Recebi' : 'Recebemos') . ($empresa ? ' de ' . $da($empresa) : '');
        $quitacao = ', pelo que ' . ($entrada || !$singular ? 'damos' : 'dou') . ' quitação do valor recebido.';

        $encontro = $modelo == 'encontro' ? static::encontro($linhas, $total, $empresa, $pessoa, $da) : null;

        $declaracao = match ($modelo) {
            // vale: confissao de divida e desconto em qualquer empresa do grupo (o colaborador pode
            // ser registrado num CNPJ e pegar o vale em outro, ou ser terceirizado)
            'vale' => 'Recebi de ' . $da($empresa)
                . ', a importância acima a título de vale (adiantamento), que reconheço como dívida minha.'
                . ' Autorizo o desconto deste valor de quaisquer valores que eu tenha a receber desta empresa'
                . ' ou de empresas do mesmo grupo econômico (salário, férias, 13º salário, verbas rescisórias,'
                . ' comissões ou pagamento por serviços prestados) e, não havendo desconto, comprometo-me a'
                . ' devolvê-lo' . ($vencimento ? ' até ' . $vencimento->format('d/m/Y') : '') . '.',
            // adiantamento a fornecedor: de produto ou de servico, abatido no que a loja (ou o
            // grupo) ficar devendo; sem o fornecimento, devolve
            'adiantamento' => $recebeu
                . ', a importância acima a título de adiantamento pelo fornecimento de produtos ou pela'
                . ' prestação de serviços, a ser abatida dos valores que ' . ($singular ? 'me' : 'nos')
                . ' forem devidos por esses fornecimentos ou serviços, por esta empresa ou por empresas do'
                . ' mesmo grupo econômico. Não havendo o fornecimento ou o serviço, '
                . ($singular ? 'comprometo-me' : 'comprometemo-nos') . ' a devolver o valor'
                . ($vencimento ? ' até ' . $vencimento->format('d/m/Y') : '') . '.',
            'pagamento', 'recebimento' => $recebeu . ', a importância acima, referente ao pagamento ' . $titulos . $quitacao,
            'encontro' => $encontro['declaracao'],
            'venda' => $recebeu . ', a importância acima, referente à venda ' . formataCodigo($primeiro->codnegocio) . $quitacao,
            default => $recebeu . ', a importância acima' . $quitacao,
        };

        // as tabelas dos titulos: no encontro, a receber e a pagar com subtotal; nos outros, uma
        // so', com o total so' com mais de uma linha
        $tabelas = [];
        if ($modelo == 'encontro') {
            $tabelas = $encontro['tabelas'];
        } elseif ($linhas && !in_array($modelo, ['vale', 'adiantamento'])) {
            $tabelas[] = [
                'rotulo' => count($linhas) > 1 ? 'Títulos' : 'Título',
                'linhas' => $linhas,
                'livre' => $livre,
                'total' => count($linhas) > 1 || $livre != 0 ? $total : null,
            ];
        }

        return static::comum($pags) + [
            'modelo' => $modelo,
            'vale' => $modelo == 'vale',
            'valeCredito' => $valeCredito,
            'titulo' => match ($modelo) {
                'vale' => 'VALE',
                'adiantamento' => 'ADIANTAMENTO',
                'encontro' => 'ENCONTRO DE CONTAS',
                default => 'RECIBO',
            },
            'vencimento' => $vencimento?->format('d/m/Y'),
            'total' => $total,
            // no encontro, o compensado e a diferenca paga (as duas caixas do cabecalho)
            'valores' => $encontro
                ? [['rotulo' => 'Compensado', 'valor' => $encontro['compensado']], ['rotulo' => 'Diferença paga', 'valor' => $total]]
                : [['rotulo' => 'Valor', 'valor' => $total]],
            'extensos' => $encontro
                ? [
                    'Compensado: ' . formataValorPorExtenso((float) $encontro['compensado'], true),
                    'Diferença paga: ' . ($total != 0 ? formataValorPorExtenso((float) $total, true) : 'sem diferença'),
                ]
                : [formataValorPorExtenso((float) $total, true)],
            'rotuloDe' => match ($modelo) {
                'vale' => 'Nome',
                'adiantamento' => 'Fornecedor',
                'pagamento' => 'Favorecido',
                'recebimento' => 'Cliente',
                'encontro' => 'Pessoa',
                default => $entrada ? 'Cliente' : 'Favorecido',
            },
            // a contraparte (no vale, o colaborador); o codigo so' de quem pagou a loja
            'de' => static::parte($pessoa, $entrada),
            'declaracao' => $declaracao,
            'tabelas' => $tabelas,
            'saldo' => $encontro['saldo'] ?? null,
            // a garantia do vale, no A5 e no cupom
            'promissoria' => $modelo == 'vale' && $vencimento ? [
                'vencimento' => $vencimento->format('d/m/Y'),
                'data' => ($vencimento->day == 1 ? 'Ao primeiro dia' : 'Aos ' . static::numeroPorExtenso($vencimento->day) . ' dias')
                    . ' do mês de ' . static::MESES[$vencimento->month]
                    . ' de ' . static::numeroPorExtenso($vencimento->year),
                'valor' => formataValorPorExtenso((float) $total),
                'pagavel' => $cidade ? $cidade->cidade . '/' . ($cidade->Estado->sigla ?? '') : '',
            ] : null,
            // quem recebeu assina; no encontro, os dois
            'assinantes' => $encontro
                ? [static::parte($empresa, false), static::parte($pessoa, false)]
                : [static::parte($entrada ? $empresa : $pessoa, false)],
        ];
    }

    // o que todo recibo tem: o numero, as formas, o "Referente a" e o local e a data
    protected static function comum(Collection $pags): array
    {
        $primeiro = $pags->first();
        $cidade = $primeiro->Filial?->Pessoa?->Cidade;
        return [
            'pags' => $pags,
            'primeiro' => $primeiro,
            'numero' => formataCodigo($primeiro->codpagamento),
            'referentes' => $pags->pluck('referente')->map(fn($o) => trim((string) $o))->filter()->unique()->values()->all(),
            'formas' => $pags->map(fn($p) => implode(' · ', array_filter([
                PagamentoService::descricao($p),
                $p->Maquineta?->apelido,
                $p->parcelas > 1 ? $p->parcelas . 'x' : null,
                $p->estado == PagamentoService::ESTADO_CANCELADO ? 'ESTORNADO' : null,
            ])) . ($pags->count() > 1 ? ' R$ ' . formataNumero($p->total) : ''))->all(),
            // a autorizacao vai miuda, embaixo da forma (o E2E do PIX e' comprido)
            'autorizacoes' => $pags->pluck('autorizacao')->filter()->map(fn($a) => 'aut. ' . $a)->values()->all(),
            'transacao' => $primeiro->transacao,
            'localData' => ($cidade ? $cidade->cidade . '/' . ($cidade->Estado->sigla ?? '') . ', ' : '')
                . $primeiro->transacao->format('d') . ' de ' . static::MESES[$primeiro->transacao->month]
                . ' de ' . $primeiro->transacao->format('Y'),
        ];
    }

    // O acerto de RH: o beneficio (as rubricas) que a loja entregou e os creditos pagos ao
    // colaborador, contra os vales descontados dele. Os titulos saem pelo liquido de todos os
    // movimentos do acerto (os ajustes de quem reativou o acerto, 930, se anulam). So' um lado,
    // recibo de quem recebeu; os dois, encontro de contas. Sem valor, nao tem recibo.
    protected static function montarAcerto(Pagamento $pag): array
    {
        $ev = \Mg\Rh\PeriodoColaboradorAcerto::with([
            'PeriodoColaborador.Colaborador.Pessoa.Cidade.Estado',
            'PeriodoColaborador.Colaborador.Filial.Pessoa.Cidade.Estado',
            'PeriodoColaborador.ColaboradorRubricaS',
            'MovimentoTituloS.Titulo',
        ])->findOrFail($pag->codperiodocolaboradoracerto);
        $pc = $ev->PeriodoColaborador;
        $pessoa = $pc?->Colaborador?->Pessoa ?? $pag->Pessoa;
        $empresa = $pag->Filial?->Pessoa ?? $pc?->Colaborador?->Filial?->Pessoa;

        $porTitulo = [];
        foreach ($ev->MovimentoTituloS as $mov) {
            $porTitulo[$mov->codtitulo]['titulo'] = $mov->Titulo;
            $porTitulo[$mov->codtitulo]['bruto'] = ($porTitulo[$mov->codtitulo]['bruto'] ?? 0) + (float) $mov->total;
        }
        $linha = fn($descricao, $vencimento, $valor, $sentido, $rubricas = null) => [
            'descricao' => $descricao, 'vencimento' => $vencimento, 'lancado' => null, 'sentido' => $sentido,
            'principal' => $valor, 'juros' => 0, 'multa' => 0, 'desconto' => 0, 'valor' => $valor, 'total' => $valor,
            'rubricas' => $rubricas ?? collect(),
        ];
        $receber = [];
        $pagar = [];
        // o beneficio: as rubricas, quando o acerto entrega o beneficio inteiro (dividido em
        // varios acertos, a quebra enganaria o total)
        if ((float) $ev->rubricas > 0) {
            $rubricas = $pc?->ColaboradorRubricaS?->where('valorcalculado', '>', 0)->values() ?? collect();
            $inteiro = round((float) $rubricas->sum('valorcalculado'), 2) == round((float) $ev->rubricas, 2);
            $pagar[] = $linha('Benefício (' . $ev->forma_descricao . ')', null, (float) $ev->rubricas, 'pagar',
                $inteiro ? $rubricas : null);
        }
        // cada lado so' pelos totais do acerto, como o RH sempre imprimiu: os vales so' com desconto
        // (debitos) e os creditos so' com credito pago
        foreach ($porTitulo as $t) {
            $v = round($t['bruto'], 2);
            if ($v < 0 && (float) $ev->debitos > 0) {
                $receber[] = $linha($t['titulo']->numero, $t['titulo']->vencimento?->format('d/m/Y'), -$v, 'receber');
            } elseif ($v > 0 && (float) $ev->creditos > 0) {
                $pagar[] = $linha($t['titulo']->numero, $t['titulo']->vencimento?->format('d/m/Y'), $v, 'pagar');
            }
        }
        if (!$receber && !$pagar) {
            abort(422, 'Acerto sem valores: não tem recibo.');
        }
        $somaPagar = round(collect($pagar)->sum('valor'), 2);
        $somaReceber = round(collect($receber)->sum('valor'), 2);
        $total = round(abs($somaPagar - $somaReceber), 2);
        $singular = (bool) ($pessoa->fisica ?? true);
        $da = fn($p) => ($p->pessoa ?? '') . (($d = static::documento($p)) ? ', ' . $d : '');
        $itens = count($pagar) + count($receber) > 1;

        if ($pagar && $receber) {
            $encontro = static::encontro(array_merge($receber, $pagar), $total, $empresa, $pessoa, $da);
            $modelo = 'encontro';
            $declaracao = $encontro['declaracao'];
            $tabelas = $encontro['tabelas'];
        } elseif ($pagar) {
            // a loja pagou o colaborador: ele declara e assina
            $modelo = 'pagamento';
            $beneficio = (float) $ev->rubricas > 0;
            $declaracao = ($singular ? 'Recebi de ' : 'Recebemos de ') . $da($empresa) . ', a importância acima, referente '
                . ($beneficio ? 'ao benefício abaixo discriminado' : ($itens ? 'ao pagamento dos títulos abaixo' : 'ao pagamento do título abaixo'))
                . ', pelo que ' . ($singular ? 'dou' : 'damos') . ' quitação do valor recebido.';
            $tabelas = [['rotulo' => $beneficio ? 'Benefício' : ($itens ? 'Títulos' : 'Título'), 'linhas' => $pagar, 'livre' => 0,
                'total' => count($pagar) > 1 ? $somaPagar : null]];
        } else {
            // o colaborador pagou (vales descontados): a loja declara e assina
            $modelo = 'recebimento';
            $declaracao = 'Recebemos de ' . $da($pessoa) . ', a importância acima, referente ao pagamento '
                . ($itens ? 'dos títulos abaixo' : 'do título abaixo') . ', pelo que damos quitação do valor recebido.';
            $tabelas = [['rotulo' => $itens ? 'Títulos' : 'Título', 'linhas' => $receber, 'livre' => 0,
                'total' => count($receber) > 1 ? $somaReceber : null]];
        }

        return static::comum(collect([$pag])) + [
            'modelo' => $modelo,
            'vale' => false,
            'valeCredito' => null,
            'titulo' => $modelo == 'encontro' ? 'ENCONTRO DE CONTAS' : 'RECIBO',
            'vencimento' => null,
            'total' => $total,
            'valores' => $modelo == 'encontro'
                ? [['rotulo' => 'Compensado', 'valor' => $encontro['compensado']], ['rotulo' => 'Diferença paga', 'valor' => $total]]
                : [['rotulo' => 'Valor', 'valor' => $total]],
            'extensos' => $modelo == 'encontro'
                ? [
                    'Compensado: ' . formataValorPorExtenso((float) $encontro['compensado'], true),
                    'Diferença paga: ' . ($total != 0 ? formataValorPorExtenso((float) $total, true) : 'sem diferença'),
                ]
                : [formataValorPorExtenso((float) $total, true)],
            'rotuloDe' => 'Colaborador',
            'de' => static::parte($pessoa, false),
            'declaracao' => $declaracao,
            'tabelas' => $tabelas,
            'saldo' => $encontro['saldo'] ?? null,
            'promissoria' => null,
            'assinantes' => match ($modelo) {
                'encontro' => [static::parte($empresa, false), static::parte($pessoa, false)],
                'pagamento' => [static::parte($pessoa, false)],
                default => [static::parte($empresa, false)],
            },
        ];
    }

    // Encontro de contas: o que cada lado pagou ao outro em titulos se compensa; a diferenca e' o
    // dinheiro que passou de mao (o total do pagamento). Uma declaracao conjunta, os dois assinam.
    protected static function encontro(array $linhas, float $diferenca, $empresa, $pessoa, \Closure $da): array
    {
        $receber = array_values(array_filter($linhas, fn($l) => $l['sentido'] == 'receber'));
        $pagar = array_values(array_filter($linhas, fn($l) => $l['sentido'] == 'pagar'));
        $somaReceber = round(collect($receber)->sum('valor'), 2);
        $somaPagar = round(collect($pagar)->sum('valor'), 2);
        $compensado = min($somaReceber, $somaPagar);
        // quem pagou a diferenca: quem devia mais em titulos
        [$pagou, $recebeu] = $somaPagar > $somaReceber ? [$empresa, $pessoa] : [$pessoa, $empresa];
        $nome = fn($p) => $p->pessoa ?? '';
        // o nome que termina em ponto (S.A., Ltda.) nao ganha outro no fim da frase
        $fim = fn($p) => rtrim($nome($p), '.') . '.';

        return [
            'compensado' => $compensado,
            'declaracao' => 'As partes, ' . $da($empresa) . ', e ' . $da($pessoa)
                . ', compensam entre si os títulos abaixo, no valor de R$ ' . formataNumero($compensado) . '.'
                . ($diferenca != 0
                    ? ' A diferença de R$ ' . formataNumero($diferenca) . ' foi paga por ' . $nome($pagou)
                        . ' a ' . $fim($recebeu)
                    : ' Não houve diferença a pagar.')
                . ' Cada parte dá quitação dos títulos que recebeu.',
            'tabelas' => [
                // o subtotal so' com mais de um titulo, como nas outras tabelas
                ['rotulo' => 'A receber (pagos por ' . $nome($pessoa) . ')', 'linhas' => $receber, 'livre' => 0,
                    'total' => count($receber) > 1 ? $somaReceber : null],
                ['rotulo' => 'A pagar (pagos por ' . $nome($empresa) . ')', 'linhas' => $pagar, 'livre' => 0,
                    'total' => count($pagar) > 1 ? $somaPagar : null],
            ],
            'saldo' => $diferenca != 0
                ? 'Diferença de R$ ' . formataNumero($diferenca) . ' paga por ' . $nome($pagou) . ' a ' . $nome($recebeu)
                : 'Sem diferença: os títulos se compensaram',
        ];
    }

    // nome, codigo, documento e endereco de quem paga, recebe ou assina
    protected static function parte($p, bool $comCodigo): ?array
    {
        if (!$p) {
            return null;
        }
        $cidade = $p->Cidade;
        return [
            'nome' => $p->pessoa,
            'codigo' => $comCodigo ? formataCodigo($p->codpessoa) : null,
            'documento' => static::documento($p),
            'endereco' => implode(' - ', array_filter([
                implode(', ', array_filter([$p->endereco, $p->numero, $p->complemento, $p->bairro])),
                $cidade ? $cidade->cidade . '/' . ($cidade->Estado->sigla ?? '') : null,
            ])),
        ];
    }

    // Quanto sobra (pt) na ultima linha de "<texto> <negrito> a", quebrando por palavra como o
    // Dompdf, para o branco da promissoria ir ate' o fim da linha (o Dompdf nao preenche o resto
    // da linha sozinho). Sobrando menos que $minimo, null: o branco vai inteiro na linha de baixo.
    public static function brancoDaLinha(string $texto, string $negrito, float $largura, float $tamanho, float $minimo = 70): ?float
    {
        static $fm = null;
        $fm ??= (new Dompdf())->getFontMetrics();
        $normal = $fm->getFont('helvetica', 'normal');
        $bold = $fm->getFont('helvetica', 'bold');
        $espaco = $fm->getTextWidth(' ', $normal, $tamanho);
        $palavras = array_merge(
            array_map(fn($p) => [$p, $normal], preg_split('/\s+/u', trim($texto))),
            array_map(fn($p) => [$p, $bold], preg_split('/\s+/u', trim($negrito))),
            [['a', $normal]]
        );
        $linha = 0;
        foreach ($palavras as [$palavra, $fonte]) {
            $w = $fm->getTextWidth($palavra, $fonte, $tamanho);
            $linha = ($linha > 0 && $linha + $espaco + $w <= $largura) ? $linha + $espaco + $w : $w;
        }
        // o espaco depois do "a" e uma folga para a diferenca de arredondamento
        $sobra = $largura - $linha - $espaco - 4;
        return $sobra >= $minimo ? round($sobra, 1) : null;
    }

    // o numero por extenso, sem a moeda (o dia e o ano da promissoria)
    protected static function numeroPorExtenso(int $n): string
    {
        return preg_replace('/ (reais|real)$/', '', formataValorPorExtenso($n));
    }

    // sem documento (Consumidor), sem ele
    protected static function documento($p): ?string
    {
        return $p && (int) $p->cnpj
            ? ($p->fisica ? 'CPF ' : 'CNPJ ') . formataCnpjCpf($p->cnpj, $p->fisica)
            : null;
    }

    // A5 deitado; o vale, A5 em pe com a promissoria; o adiantamento de cliente, o vale de credito
    public static function pdf(int $codpagamento): string
    {
        $r = static::montar(static::carregar([$codpagamento]));
        if ($r['valeCredito']) {
            return TituloValeService::pdfTitulos([$r['valeCredito']]);
        }
        return static::render(view('pagamento.documento', ['recibos' => [$r]])->render());
    }

    // varios pagamentos, um recibo por pagamento, no mesmo PDF (os recibos do acerto de RH); o que
    // nao tem recibo (acerto sem valor, credito de cliente) fica de fora; nenhum, vazio
    public static function pdfLote(array $codpagamentos): string
    {
        $recibos = collect($codpagamentos)->map(function ($cod) {
            try {
                return static::montar(static::carregar([$cod]));
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                return null;
            }
        })->filter(fn($r) => $r && !$r['valeCredito'])->values()->all();
        return $recibos ? static::render(view('pagamento.documento', compact('recibos'))->render()) : '';
    }

    // bobina 80mm
    public static function pdfTermica(array $codpagamentos): string
    {
        $r = static::montar(static::carregar($codpagamentos));
        if ($r['valeCredito']) {
            return TituloValeService::pdfTitulos([$r['valeCredito']]);
        }
        return static::render(view('pagamento.documento-termica', compact('r'))->render(), [0.0, 0.0, 226.77, 841.89]);
    }

    protected static function render(string $html, ?array $papel = null): string
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        if ($papel) {
            $dompdf->setPaper($papel, 'portrait');
        }
        $dompdf->render();
        return $dompdf->output();
    }
}
