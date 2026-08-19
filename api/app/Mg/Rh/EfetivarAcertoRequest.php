<?php

namespace Mg\Rh;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class EfetivarAcertoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'forma'                 => 'required|in:B,D,F',
            'data'                  => 'nullable|date',
            'observacao'            => 'nullable|string|max:200',
            'titulos'               => 'required|array|min:1',
            // codtitulo null = linha sintética do benefício (remuneração variável)
            'titulos.*.codtitulo'   => 'nullable|integer|exists:tbltitulo,codtitulo',
            'titulos.*.pagando'     => 'required|numeric|min:0',
            'titulos.*.descontando' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $titulos = $this->input('titulos', []);

            // Ao menos uma linha com valor > 0
            $temValor = collect($titulos)->some(function ($t) {
                return ($t['pagando'] ?? 0) > 0 || ($t['descontando'] ?? 0) > 0;
            });
            if (!$temValor) {
                $v->errors()->add('titulos', 'Ao menos uma linha deve ter valor de pagando ou descontando.');
                return;
            }

            $this->validarBeneficio($v, $titulos);

            // Valida saldos apenas dos títulos REAIS (a linha sintética tem codtitulo null)
            $codtitulos = array_values(array_filter(array_column($titulos, 'codtitulo')));
            $saldos     = $codtitulos
                ? DB::table('tbltitulo')->whereIn('codtitulo', $codtitulos)->pluck('saldo', 'codtitulo')
                : collect();

            foreach ($titulos as $idx => $t) {
                $codtitulo   = $t['codtitulo'] ?? null;
                $pagando     = (float) ($t['pagando'] ?? 0);
                $descontando = (float) ($t['descontando'] ?? 0);

                if (!$codtitulo || !isset($saldos[$codtitulo])) {
                    continue;
                }

                $saldo = (float) $saldos[$codtitulo];

                if ($pagando > 0 && $saldo >= 0) {
                    $v->errors()->add("titulos.{$idx}.pagando", "Pagando só vale para título com saldo negativo (crédito).");
                }
                if ($descontando > 0 && $saldo <= 0) {
                    $v->errors()->add("titulos.{$idx}.descontando", "Descontando só vale para título com saldo positivo (débito).");
                }
                if ($pagando > 0 && $pagando > abs($saldo) + 0.001) {
                    $v->errors()->add("titulos.{$idx}.pagando", "Pagando não pode exceder o saldo do título.");
                }
                if ($descontando > 0 && $descontando > abs($saldo) + 0.001) {
                    $v->errors()->add("titulos.{$idx}.descontando", "Descontando não pode exceder o saldo do título.");
                }
            }
        });
    }

    /**
     * O benefício entregue tem que ter lastro nas rubricas do colaborador.
     *
     * As linhas com `codtitulo` nulo são o benefício (remuneração variável) e
     * escapam do loop acima, que só sabe validar título real. Sem esta checagem
     * dá para gravar `rubricas` acima do `valortotal` — saldo de recarga sem
     * rubrica que o lastreie —, e o "Saldo a acertar" do colaborador fica
     * negativo para sempre. Quem precisa entregar mais lança uma rubrica antes.
     *
     * O front já faz o clamp; isto fecha o POST direto na API.
     */
    protected function validarBeneficio($v, array $titulos): void
    {
        $pedido = 0.0;
        $temDesconto = false;
        foreach ($titulos as $t) {
            if (!empty($t['codtitulo'])) {
                continue;
            }
            $pedido += round((float) ($t['pagando'] ?? 0), 2);
            if (round((float) ($t['descontando'] ?? 0), 2) > 0) {
                $temDesconto = true;
            }
        }

        // `descontando` na linha sintética é descartado em silêncio pelo
        // AcertoService::efetivar (o `continue` vem depois de somar `pagando`).
        // Aceitar sem avisar grava um acerto todo zerado.
        if ($temDesconto) {
            $v->errors()->add('titulos', 'A linha de benefício não aceita desconto. Ajuste a rubrica do colaborador.');
        }

        if ($pedido <= 0) {
            return;
        }

        $codperiodocolaborador = (int) $this->route('codperiodocolaborador');
        $pc = PeriodoColaborador::find($codperiodocolaborador);
        if (!$pc) {
            return; // o service faz findOrFail e devolve o erro certo
        }

        // Mesma conta de AcertoService::buscarTitulos: previsto menos o que os
        // acertos ativos já entregaram.
        $entregue = (float) PeriodoColaboradorAcerto::where('codperiodocolaborador', $codperiodocolaborador)
            ->whereNull('inativo')
            ->sum('rubricas');
        $disponivel = round(((float) $pc->valortotal) - $entregue, 2);

        if ($pedido > $disponivel + 0.005) {
            $v->errors()->add('titulos', sprintf(
                'Benefício: disponível %s, solicitado %s. Lance uma rubrica para aumentar o previsto.',
                number_format($disponivel, 2, ',', '.'),
                number_format($pedido, 2, ',', '.')
            ));
        }
    }
}
