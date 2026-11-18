<?php

namespace Mg\Filial;

use Carbon\Carbon;

// Percentuais do Simples por mes da filial (TASK-280). A competencia e' sempre
// gravada como o 1o dia do mes; um mes por filial.
class FilialSimplesService
{
    public static function listar(int $codfilial)
    {
        return FilialSimples::where('codfilial', $codfilial)
            ->orderByDesc('competencia')
            ->get();
    }

    // O mes da competencia (qualquer dia do mes serve). Mesmo motor da tela da
    // nota de servico e do Duplicar.
    public static function doMes(int $codfilial, $competencia): ?FilialSimples
    {
        return FilialSimples::where('codfilial', $codfilial)
            ->where('competencia', Carbon::parse($competencia)->startOfMonth()->toDateString())
            ->first();
    }

    public static function criar(int $codfilial, array $dados): FilialSimples
    {
        Filial::findOrFail($codfilial);
        $comp = static::verificarMesLivre($codfilial, $dados['competencia']);
        $reg = new FilialSimples();
        $reg->codfilial = $codfilial;
        $reg->competencia = $comp->toDateString();
        $reg->tributospercentual = $dados['tributospercentual'];
        $reg->issaliquota = $dados['issaliquota'];
        $reg->save();
        return $reg;
    }

    public static function atualizar(int $codfilial, int $codfilialsimples, array $dados): FilialSimples
    {
        $reg = static::daFilial($codfilial, $codfilialsimples);
        $comp = static::verificarMesLivre($codfilial, $dados['competencia'], $codfilialsimples);
        $reg->competencia = $comp->toDateString();
        $reg->tributospercentual = $dados['tributospercentual'];
        $reg->issaliquota = $dados['issaliquota'];
        $reg->save();
        return $reg;
    }

    public static function excluir(int $codfilial, int $codfilialsimples): void
    {
        static::daFilial($codfilial, $codfilialsimples)->delete();
    }

    public static function daFilial(int $codfilial, int $codfilialsimples): FilialSimples
    {
        return FilialSimples::where('codfilial', $codfilial)->findOrFail($codfilialsimples);
    }

    // Um mes por filial: nunca sobrescreve sem querer, quem quer trocar edita o registro
    protected static function verificarMesLivre(int $codfilial, string $competencia, ?int $ignorar = null): Carbon
    {
        $comp = Carbon::parse($competencia)->startOfMonth();
        $qry = FilialSimples::where('codfilial', $codfilial)
            ->where('competencia', $comp->toDateString());
        if ($ignorar) {
            $qry->where('codfilialsimples', '!=', $ignorar);
        }
        if ($qry->exists()) {
            abort(409, 'Já existe percentual para ' . $comp->format('m/Y') . '. Edite o registro existente.');
        }
        return $comp;
    }
}
