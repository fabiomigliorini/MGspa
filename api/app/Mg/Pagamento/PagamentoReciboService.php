<?php

namespace Mg\Pagamento;

use Dompdf\Dompdf;
use Illuminate\Support\Collection;
use Mg\Titulo\MovimentoTituloService;
use Mg\Titulo\TipoTituloService;

/**
 * O recibo do pagamento, um so' para o A5 (contas e PDV) e para o cupom da termica
 * (TASK-216), no jeito do bloco de papel: VALE para o vale colaborador, RECIBO para o resto.
 * Quem recebe o dinheiro assina: entrou (o cliente pagou), a loja ("Recebemos de"); saiu (a
 * loja pagou), a contraparte ("Recebi(emos) de"). O "Referente a" escrito no pagamento sai no
 * recibo; a observacao e' interna. Um recebimento com varias formas
 * (varios pagamentos) sai num recibo so'.
 */
class PagamentoReciboService
{
    // o "Referente a" dos adiantamentos, pelo tipo do titulo lancado com o pagamento
    const REFERENTE_TIPO = [
        TipoTituloService::TIPO_ADIANTAMENTO_FORNECEDOR => 'Adiantamento, a ser abatido em fornecimentos futuros',
        TipoTituloService::TIPO_ADIANTAMENTO_CLIENTE => 'Adiantamento, a ser abatido em compras futuras',
    ];

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
                // vale colaborador / adiantamento: o pagamento nasceu com o titulo
                if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
                    $lancados[$titulo->TipoTitulo->tipotitulo ?? ''] = true;
                    $codtipotitulo = (int) $titulo->codtipotitulo;
                }
                $l = &$linhas[$titulo->codtitulo];
                $l = $l ?? ['titulo' => $titulo, 'lancado' => null, 'juros' => 0, 'multa' => 0, 'desconto' => 0, 'total' => 0];
                if ($mov->codtipomovimentotitulo == MovimentoTituloService::TIPO_IMPLANTACAO) {
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
            $l['principal'] = $l['total'] - $l['juros'] - $l['multa'] + $l['desconto'];
            // titulo de folha: detalhado pelas rubricas do colaborador
            $l['rubricas'] = $l['titulo']->PeriodoColaboradorS
                ->flatMap->ColaboradorRubricaS
                ->where('valorcalculado', '>', 0)
                ->values();
        }
        unset($l);

        // a filial do pagamento: a do PDV; fora dele, a do portador (no cartao, a da maquineta)
        $empresa = $primeiro->Filial?->Pessoa;
        $pessoa = $primeiro->Pessoa ?? $primeiro->Negocio?->Pessoa;

        // baixa de titulos: a tabela (principal, juros, multa, desconto, total) entra no recibo;
        // so' o vale/adiantamento lancado, o titulo e' o proprio lancamento e fica no rodape
        $baixados = collect($linhas)->whereNull('lancado')->count();
        $baixa = $baixados > 0;
        $total = $pags->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->sum('total');
        // o que entrou e nao esta amarrado a titulo: o detalhe fecha com o valor do recibo
        $livre = $linhas ? round($total - collect($linhas)->sum('total'), 2) : 0;
        // o pagamento inteiro e' o lancamento (sem baixa de titulo junto, sem sobra livre): layout
        // pagamento/documento; senao, o recibo com a tabela
        $soLancamento = !$baixa && $livre == 0;
        $vale = $soLancamento && $codtipotitulo == TipoTituloService::TIPO_VALE_COLABORADOR;
        $fornecedor = $soLancamento && $codtipotitulo == TipoTituloService::TIPO_ADIANTAMENTO_FORNECEDOR;
        $adiantamento = $vale || $fornecedor;
        // pagamento de titulos: a loja pagou, todos no mesmo sentido (titulo a receber junto e'
        // encontro de contas, outro modelo); quem recebeu declara a quitacao e assina
        $pagamentoTitulos = $baixa && !$entrada && !$lancados
            && collect($linhas)->every(fn($l) => $l['total'] > 0);
        // o que alguem assina no layout pagamento/documento (A5 e cupom)
        $documento = $adiantamento || $pagamentoTitulos;
        // adiantamento de cliente: o papel dele e' o vale de credito do titulo (o caixa bipa para
        // usar), enquanto tiver saldo; usado todo, sai o recibo
        $valeCredito = $soLancamento && $codtipotitulo == TipoTituloService::TIPO_ADIANTAMENTO_CLIENTE
            && (float) ($linhas[0]['titulo']->saldo ?? 0) < 0
            ? (int) $linhas[0]['titulo']->codtitulo : null;
        $referente = $adiantamento ? null : (implode(' · ', array_filter([
            $baixa
                ? ($entrada ? 'Recebimento' : 'Pagamento') . ($baixados > 1 ? ' dos títulos abaixo' : ' do título abaixo')
                : null,
            $lancados ? (static::REFERENTE_TIPO[$codtipotitulo] ?? implode(' / ', array_keys($lancados))) : null,
            !$linhas && !empty($primeiro->codnegocio) ? 'Venda ' . formataCodigo($primeiro->codnegocio) : null,
        ])) ?: null);

        // o "Referente a" escrito no pagamento (a observacao e' interna e nao sai no recibo)
        $referentes = $pags->pluck('referente')->map(fn($o) => trim((string) $o))
            ->filter()->unique()->values()->all();

        $cidade = $empresa?->Cidade;
        $vencimento = $adiantamento ? ($linhas[0]['titulo']->vencimento ?? null) : null;
        $ate = $vencimento ? ' até ' . $vencimento->format('d/m/Y') : '';
        // o fornecedor (ou o favorecido) pessoa fisica fala no singular
        $singular = (bool) ($pessoa->fisica ?? false);
        $umTitulo = count($linhas) == 1;
        $formas = $pags->map(fn($p) => implode(' · ', array_filter([
            PagamentoService::descricao($p),
            $p->Maquineta?->apelido,
            $p->parcelas > 1 ? $p->parcelas . 'x' : null,
            $p->estado == PagamentoService::ESTADO_CANCELADO ? 'ESTORNADO' : null,
        ])) . ($pags->count() > 1 ? ' R$ ' . formataNumero($p->total) : ''))->all();
        // a autorizacao vai miuda, embaixo da forma (o E2E do PIX e' comprido)
        $autorizacoes = $pags->pluck('autorizacao')->filter()->map(fn($a) => 'aut. ' . $a)->values()->all();

        return [
            'pags' => $pags,
            'primeiro' => $primeiro,
            'vale' => $vale,
            'adiantamento' => $adiantamento,
            'documento' => $documento,
            'valeCredito' => $valeCredito,
            'baixa' => $baixa,
            'titulo' => $vale ? 'VALE' : ($fornecedor ? 'ADIANTAMENTO' : 'RECIBO'),
            'vencimento' => $vencimento?->format('d/m/Y'),
            'numero' => formataCodigo($primeiro->codpagamento),
            'total' => $total,
            'extenso' => formataValorPorExtenso((float) $total, true),
            // RECIBO: quem pagou; VALE, ADIANTAMENTO e pagamento de titulos: quem recebeu e assina
            'rotuloDe' => $vale ? 'Nome' : ($fornecedor ? 'Fornecedor' : ($pagamentoTitulos ? 'Favorecido'
                : ($entrada ? 'Recebemos de' : 'Recebi(emos) de'))),
            'de' => static::parte($documento || $entrada ? $pessoa : $empresa, $entrada),
            'referente' => $referente,
            'referentes' => $referentes,
            // vale: confissao de divida e desconto em qualquer empresa do grupo (o colaborador pode
            // ser registrado num CNPJ e pegar o vale em outro, ou ser terceirizado)
            'declaracao' => $vale
                ? 'Recebi de ' . ($empresa->pessoa ?? '')
                    . (($d = static::documento($empresa)) ? ', ' . $d : '')
                    . ', a importância acima a título de vale (adiantamento), que reconheço como dívida minha.'
                    . ' Autorizo o desconto deste valor de quaisquer valores que eu tenha a receber desta empresa'
                    . ' ou de empresas do mesmo grupo econômico (salário, férias, 13º salário, verbas rescisórias,'
                    . ' comissões ou pagamento por serviços prestados) e, não havendo desconto, comprometo-me a'
                    . ' devolvê-lo' . $ate . '.'
                : ($fornecedor
                    // adiantamento a fornecedor: de produto ou de servico, abatido no que a loja (ou o
                    // grupo) ficar devendo; sem o fornecimento, devolve
                    ? ($singular ? 'Recebi de ' : 'Recebemos de ') . ($empresa->pessoa ?? '')
                        . (($d = static::documento($empresa)) ? ', ' . $d : '')
                        . ', a importância acima a título de adiantamento pelo fornecimento de produtos ou pela'
                        . ' prestação de serviços, a ser abatida dos valores que ' . ($singular ? 'me' : 'nos')
                        . ' forem devidos por esses fornecimentos ou serviços, por esta empresa ou por empresas do'
                        . ' mesmo grupo econômico. Não havendo o fornecimento ou o serviço, '
                        . ($singular ? 'comprometo-me' : 'comprometemo-nos') . ' a devolver o valor' . $ate . '.'
                    : ($pagamentoTitulos
                        // pagamento de titulos: quem recebeu declara a quitacao do que recebeu
                        ? ($singular ? 'Recebi de ' : 'Recebemos de ') . ($empresa->pessoa ?? '')
                            . (($d = static::documento($empresa)) ? ', ' . $d : '')
                            . ', a importância acima, referente ao pagamento '
                            . ($umTitulo ? 'do título abaixo' : 'dos títulos abaixo') . ', pelo que '
                            . ($singular ? 'dou' : 'damos') . ' quitação do valor recebido.'
                        : null)),
            // a garantia do vale, no A5 e no cupom
            'promissoria' => $vale && $vencimento ? [
                'vencimento' => $vencimento->format('d/m/Y'),
                'data' => ($vencimento->day == 1 ? 'Ao primeiro dia' : 'Aos ' . static::numeroPorExtenso($vencimento->day) . ' dias')
                    . ' do mês de ' . static::MESES[$vencimento->month]
                    . ' de ' . static::numeroPorExtenso($vencimento->year),
                'valor' => formataValorPorExtenso((float) $total),
                'pagavel' => $cidade ? $cidade->cidade . '/' . ($cidade->Estado->sigla ?? '') : '',
            ] : null,
            'formas' => $formas,
            'autorizacoes' => $autorizacoes,
            'assinante' => static::parte($entrada ? $empresa : $pessoa, false),
            'livre' => $livre,
            'linhas' => $linhas,
            'transacao' => $primeiro->transacao,
            'localData' => ($cidade ? $cidade->cidade . '/' . ($cidade->Estado->sigla ?? '') . ', ' : '')
                . $primeiro->transacao->format('d') . ' de ' . static::MESES[$primeiro->transacao->month]
                . ' de ' . $primeiro->transacao->format('Y'),
            'operador' => $primeiro->usuariocriacao ?? '',
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

    // A5 deitado, como o recibo de sempre do contas; o vale, A5 em pe
    public static function pdf(int $codpagamento): string
    {
        $r = static::montar(static::carregar([$codpagamento]));
        if ($r['valeCredito']) {
            return \Mg\Titulo\TituloValeService::pdfTitulos([$r['valeCredito']]);
        }
        // vale, adiantamento a fornecedor e pagamento de titulos: pagamento/documento (o vale com a
        // promissoria, A5 em pe)
        return static::render(view($r['documento'] ? 'pagamento.documento' : 'pagamento.recibo', compact('r'))->render());
    }

    // bobina 80mm
    public static function pdfTermica(array $codpagamentos): string
    {
        $r = static::montar(static::carregar($codpagamentos));
        if ($r['valeCredito']) {
            return \Mg\Titulo\TituloValeService::pdfTitulos([$r['valeCredito']]);
        }
        $view = $r['documento'] ? 'pagamento.documento-termica' : 'pagamento.recibo-termica';
        return static::render(view($view, compact('r'))->render(), [0.0, 0.0, 226.77, 841.89]);
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
