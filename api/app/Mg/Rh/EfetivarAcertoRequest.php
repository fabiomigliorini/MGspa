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
}
