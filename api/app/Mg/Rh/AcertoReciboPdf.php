<?php

namespace Mg\Rh;

use Dompdf\Dompdf;

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
                'PeriodoColaborador.Setor',
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

        // Um recibo por acerto; mantem os acertos de um mesmo colaborador juntos (nome, depois data).
        $acertos = $eventos->sortBy(function ($e) {
            $nome = optional(optional(optional($e->PeriodoColaborador)->Colaborador)->Pessoa)->pessoa ?? '';
            $data = $e->data ? $e->data->format('Y-m-d') : '';
            return sprintf('%s|%s|%08d', $nome, $data, $e->codperiodocolaboradoracerto);
        })->values();

        if ($acertos->isEmpty()) {
            return '';
        }

        return self::render($acertos);
    }

    // Recibo(s) de UM unico acerto. $tipo restringe a impressao:
    // 'pagamento' | 'recebimento' | null (ambos, conforme os valores do evento).
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

        return self::render(collect([$ev]), $tipo);
    }

    private static function render($acertos, ?string $tipo = null): string
    {
        $html = view('rh.acerto-recibos', compact('acertos', 'tipo'))->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
