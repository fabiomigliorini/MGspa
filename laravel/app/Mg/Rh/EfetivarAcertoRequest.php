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
            'titulos'                 => 'required|array|min:1',
            'titulos.*.codtitulo'     => 'required|integer|exists:tbltitulo,codtitulo',
            'titulos.*.pagando'       => 'required|numeric|min:0',
            'titulos.*.descontando'   => 'required|numeric|min:0',
            'observacao'              => 'nullable|string|max:200',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $titulos = $this->input('titulos', []);

            // Ao menos um título com valor > 0
            $temValor = collect($titulos)->some(function ($t) {
                return ($t['pagando'] ?? 0) > 0 || ($t['descontando'] ?? 0) > 0;
            });

            if (!$temValor) {
                $v->errors()->add('titulos', 'Ao menos um título deve ter valor de pagando ou descontando.');
                return;
            }

            // Validar saldos e consistência por título
            $codtitulos   = array_column($titulos, 'codtitulo');
            $saldosAtuais = DB::table('tbltitulo')
                ->whereIn('codtitulo', $codtitulos)
                ->pluck('saldo', 'codtitulo');

            foreach ($titulos as $idx => $t) {
                $codtitulo   = $t['codtitulo'] ?? null;
                $pagando     = (float) ($t['pagando'] ?? 0);
                $descontando = (float) ($t['descontando'] ?? 0);

                if (!isset($saldosAtuais[$codtitulo])) {
                    continue;
                }

                $saldo = (float) $saldosAtuais[$codtitulo];

                // pagando só é válido quando saldo < 0 (crédito)
                if ($pagando > 0 && $saldo >= 0) {
                    $v->errors()->add(
                        "titulos.{$idx}.pagando",
                        "Pagando só pode ser informado para títulos com saldo negativo (crédito). Título {$codtitulo} tem saldo {$saldo}."
                    );
                }

                // descontando só é válido quando saldo > 0 (débito)
                if ($descontando > 0 && $saldo <= 0) {
                    $v->errors()->add(
                        "titulos.{$idx}.descontando",
                        "Descontando só pode ser informado para títulos com saldo positivo (débito). Título {$codtitulo} tem saldo {$saldo}."
                    );
                }

                // pagando não pode exceder |saldo|
                if ($pagando > 0 && $pagando > abs($saldo)) {
                    $v->errors()->add(
                        "titulos.{$idx}.pagando",
                        "Pagando ({$pagando}) não pode exceder o saldo absoluto do título {$codtitulo} (" . abs($saldo) . ")."
                    );
                }

                // descontando não pode exceder |saldo|
                if ($descontando > 0 && $descontando > abs($saldo)) {
                    $v->errors()->add(
                        "titulos.{$idx}.descontando",
                        "Descontando ({$descontando}) não pode exceder o saldo absoluto do título {$codtitulo} (" . abs($saldo) . ")."
                    );
                }
            }
        });
    }
}
