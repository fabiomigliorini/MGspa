<?php

namespace Mg\Vale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\MgController;
use Mg\Usuario\Autorizador;

class ValeEmitidoController extends MgController
{
    private const GRUPOS = ['Administrador', 'Gerente'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $filtros = $this->validarFiltros($request);
        $vales = $this->consulta($filtros)
            ->orderByDesc(DB::raw('coalesce(negocio.lancamento, vale.criacao)'))
            ->orderByDesc('vale.codnegociovale')
            ->paginate(50)
            ->appends($request->all());

        return ValeEmitidoResource::collection($vales);
    }

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $filtros = $this->validarFiltros($request);
        $consulta = $this->consulta($filtros);
        if ((clone $consulta)->count('vale.codnegociovale') > 500) {
            abort(422, 'O relatório está limitado a 500 registros. Ajuste os filtros e tente novamente.');
        }

        $vales = $consulta
            ->orderByDesc(DB::raw('coalesce(negocio.lancamento, vale.criacao)'))
            ->orderByDesc('vale.codnegociovale')
            ->get();

        if ($request->boolean('html')) {
            return response(ValeEmitidoRelatorioService::html($vales, $filtros), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response(ValeEmitidoRelatorioService::pdf($vales, $filtros), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="vales-emitidos.pdf"',
        ]);
    }

    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'codvalemodelo' => ['nullable', 'integer'],
            'codpessoafavorecido' => ['nullable', 'integer'],
            'codnegocio' => ['nullable', 'integer'],
            'valorde' => ['nullable', 'numeric', 'min:0'],
            'valorate' => ['nullable', 'numeric', 'min:0', 'gte:valorde'],
            'situacao' => ['nullable', 'in:ativo,cancelado,todos'],
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
        ]);
    }

    private function consulta(array $filtros)
    {
        $query = DB::table('tblnegociovale as vale')
            ->join('tblnegocio as negocio', 'negocio.codnegocio', '=', 'vale.codnegocio')
            ->leftJoin('tblvalemodelo as modelo', 'modelo.codvalemodelo', '=', 'vale.codvalemodelo')
            ->leftJoin('tblpessoa as favorecido', 'favorecido.codpessoa', '=', 'vale.codpessoafavorecido')
            ->leftJoin('tbltitulo as titulo', 'titulo.codtitulo', '=', 'vale.codtitulo')
            ->whereIn('negocio.codnegociostatus', [2, 3])
            ->select([
                'vale.codnegociovale',
                'vale.codvalecompra',
                'vale.codnegocio',
                'vale.codvalemodelo',
                'vale.codpessoafavorecido',
                'vale.aluno',
                'vale.turma',
                'vale.valorvale',
                'vale.valortotal',
                'vale.inativo',
                'vale.criacao',
                'negocio.lancamento',
                'negocio.codnegociostatus',
                'modelo.modelo',
                'favorecido.fantasia as favorecido',
                'titulo.codtitulo as titulo_codtitulo',
                'titulo.numero as titulo_numero',
                'titulo.saldo as titulo_saldo',
            ]);

        if (!empty($filtros['codvalemodelo'])) {
            $query->where('vale.codvalemodelo', $filtros['codvalemodelo']);
        }
        if (!empty($filtros['codnegocio'])) {
            $query->where('negocio.codnegocio', $filtros['codnegocio']);
        }
        if (isset($filtros['valorde']) && $filtros['valorde'] !== '') {
            $query->where('vale.valorvale', '>=', $filtros['valorde']);
        }
        if (isset($filtros['valorate']) && $filtros['valorate'] !== '') {
            $query->where('vale.valorvale', '<=', $filtros['valorate']);
        }
        if (!empty($filtros['codpessoafavorecido'])) {
            $query->where('vale.codpessoafavorecido', $filtros['codpessoafavorecido']);
        }
        if (!empty($filtros['de'])) {
            $query->whereRaw('coalesce(negocio.lancamento, vale.criacao)::date >= ?', [$filtros['de']]);
        }
        if (!empty($filtros['ate'])) {
            $query->whereRaw('coalesce(negocio.lancamento, vale.criacao)::date <= ?', [$filtros['ate']]);
        }
        if (!empty($filtros['busca'])) {
            $busca = trim($filtros['busca']);
            $query->where(function ($sub) use ($busca) {
                $sub->where('modelo.modelo', 'ilike', "%{$busca}%")
                    ->orWhere('favorecido.fantasia', 'ilike', "%{$busca}%");
                if (ctype_digit($busca)) {
                    $sub->orWhere('vale.codnegociovale', (int) $busca)
                        ->orWhere('vale.codvalecompra', (int) $busca)
                        ->orWhere('vale.codnegocio', (int) $busca);
                }
            });
        }

        $situacao = $filtros['situacao'] ?? 'ativo';
        if ($situacao === 'ativo') {
            $query->where('negocio.codnegociostatus', 2)->whereNull('vale.inativo');
        } elseif ($situacao === 'cancelado') {
            $query->where(function ($sub) {
                $sub->where('negocio.codnegociostatus', 3)
                    ->orWhereNotNull('vale.inativo');
            });
        }

        return $query;
    }
}
