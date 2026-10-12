<?php

namespace Mg\Rh;

use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoReciboService;
use Mg\Pagamento\PagamentoService;

class AcertoReciboPdf
{
    public static function gerar(int $codperiodo, array $colaboradores = [], ?int $codunidadenegocio = null): string
    {
        ini_set('memory_limit', '256M');

        $eventos = PeriodoColaboradorAcerto::whereNull('inativo')
            ->whereHas('PeriodoColaborador', fn ($q) => $q->where('codperiodo', $codperiodo))
            ->with([
                'PeriodoColaborador.Colaborador.Pessoa',
                'PeriodoColaborador.Colaborador.Filial.Pessoa.Cidade.Estado',
                'PeriodoColaborador.ColaboradorRubricaS',
                'PeriodoColaborador.Setor.UnidadeNegocio',
                'MovimentoTituloS.Titulo',
            ])
            ->get();

        if (!empty($colaboradores)) {
            $eventos = $eventos->whereIn('codperiodocolaborador', $colaboradores);
        }
        if ($codunidadenegocio) {
            $eventos = $eventos->filter(
                fn ($e) => optional(optional($e->PeriodoColaborador)->Setor)->codunidadenegocio == $codunidadenegocio
            );
        }

        // Um recibo por acerto; agrupa por unidade de negocio em ordem alfabetica
        // (Administrativo, Andre Maggi, Botanico, Centro, ...) e, dentro da unidade, mantem
        // os acertos de um mesmo colaborador juntos (nome, depois data).
        $acertos = $eventos->sortBy(function ($e) {
            $pc = optional($e->PeriodoColaborador);
            $unidade = self::chaveAlfabetica(optional(optional($pc->Setor)->UnidadeNegocio)->descricao);
            $nome = optional(optional($pc->Colaborador)->Pessoa)->pessoa ?? '';
            $data = $e->data ? $e->data->format('Y-m-d') : '';
            return sprintf('%s|%s|%s|%08d', $unidade, $nome, $data, $e->codperiodocolaboradoracerto);
        })->values();

        return self::render($acertos);
    }

    // O recibo de UM acerto. $tipo (pagamento/recebimento) e' do recibo antigo, que separava os
    // dois lados; o documento unico ja' traz os dois (o encontro de contas) e ignora.
    public static function gerarPorAcerto(int $codperiodocolaboradoracerto, ?string $tipo = null): string
    {
        ini_set('memory_limit', '256M');

        $ev = PeriodoColaboradorAcerto::with([
            'PeriodoColaborador.Colaborador.Pessoa',
            'PeriodoColaborador.Colaborador.Filial.Pessoa.Cidade.Estado',
            'PeriodoColaborador.ColaboradorRubricaS',
            'PeriodoColaborador.Setor',
            'MovimentoTituloS.Titulo',
        ])->whereNull('inativo')->find($codperiodocolaboradoracerto);

        // Acerto inativo (ou inexistente) nao emite recibo.
        if (!$ev) {
            return '';
        }

        return self::render(collect([$ev]));
    }

    // Chave de ordenacao alfabetica: dobra acentos para ASCII e descarta o resto, senao
    // "Botanico"/"Deposito" cairiam depois de "Sinopel" na comparacao byte a byte.
    // Sem unidade vai para o fim da impressao.
    private static function chaveAlfabetica(?string $texto): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $texto) ?: (string) $texto;
        $chave = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $ascii));

        return $chave === '' ? 'ZZZZZZZZ' : $chave;
    }

    // O recibo do acerto e' o recibo do PAGAMENTO do acerto (TASK-216): o mesmo documento e o mesmo
    // service do contas e do PDV, um por acerto, na ordem pedida. Acerto sem valor nao tem recibo.
    private static function render($acertos): string
    {
        $pagamentos = $acertos->map(fn ($ev) => Pagamento::where('codperiodocolaboradoracerto', $ev->codperiodocolaboradoracerto)
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)->value('codpagamento'))
            ->filter()->values()->all();
        return $pagamentos ? PagamentoReciboService::pdfLote($pagamentos) : '';
    }
}
