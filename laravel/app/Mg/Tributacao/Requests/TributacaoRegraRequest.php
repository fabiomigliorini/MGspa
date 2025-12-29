<?php

namespace Mg\Tributacao\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Mg\Tributacao\TributacaoRegra;

class TributacaoRegraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codtributo'          => 'required|integer|exists:tbltributo,codtributo',
            'codnaturezaoperacao' => 'nullable|integer',
            'codtipoproduto'      => 'nullable|integer',
            'ncm'                 => 'nullable|string|max:10',
            'codestadodestino'    => 'nullable|integer',
            'codcidadedestino'    => 'nullable|integer',
            'tipocliente'         => 'nullable|string|min:3|max:3',

            'basepercentual'      => 'required|numeric|min:0|max:100',
            'aliquota'            => 'required|numeric|min:0',

            'cst'                 => 'required|string|max:3',
            'cclasstrib'          => 'required|string|max:20',

            'geracredito'         => 'boolean',
            'beneficiocodigo'     => 'nullable|string|max:20',
            'observacoes'         => 'nullable|string',

            'vigenciainicio'      => 'required|date',
            'vigenciafim'         => 'nullable|date|after_or_equal:vigenciainicio',
        ];
    }

    /**
     * ============================================================
     * VALIDAÇÃO DE UNICIDADE LÓGICA (REGRA FISCAL)
     * ============================================================
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $idAtual = $this->route('regra');

            $query = TributacaoRegra::query()
                ->where('codtributo', $this->codtributo)
                ->where(function ($q) {
                    if ($this->codnaturezaoperacao) {
                        $q->where('codnaturezaoperacao', $this->codnaturezaoperacao);
                    } else {
                        $q->whereNull('codnaturezaoperacao');
                    }
                })
                ->where(function ($q) {
                    if ($this->ncm) {
                        $q->where('ncm', $this->ncm);
                    } else {
                        $q->whereNull('ncm');
                    }
                })
                ->where(function ($q) {
                    if ($this->codestadodestino) {
                        $q->where('codestadodestino', $this->codestadodestino);
                    } else {
                        $q->whereNull('codestadodestino');
                    }
                })
                ->where(function ($q) {
                    if ($this->codcidadedestino) {
                        $q->where('codcidadedestino', $this->codcidadedestino);
                    } else {
                        $q->whereNull('codcidadedestino');
                    }
                });

            if ($idAtual) {
                $query->where('codtributacaoregra', '!=', $idAtual);
            }

            // 🔹 sobreposição de vigência (mantém igual)
            $inicio = $this->vigenciainicio;
            $fim    = $this->vigenciafim;

            $query->where(function ($q) use ($inicio, $fim) {
                if ($fim) {
                    $q->where(function ($q2) use ($inicio, $fim) {
                        $q2->whereBetween('vigenciainicio', [$inicio, $fim])
                            ->orWhereBetween('vigenciafim', [$inicio, $fim])
                            ->orWhere(function ($q3) use ($inicio, $fim) {
                                $q3->where('vigenciainicio', '<=', $inicio)
                                    ->where(function ($q4) use ($fim) {
                                        $q4->whereNull('vigenciafim')
                                            ->orWhere('vigenciafim', '>=', $fim);
                                    });
                            });
                    });
                } else {
                    $q->where(function ($q2) use ($inicio) {
                        $q2->whereNull('vigenciafim')
                            ->orWhere('vigenciafim', '>=', $inicio);
                    });
                }
            });

            if ($query->exists()) {
                $validator->errors()->add(
                    'regra',
                    'Já existe uma regra idêntica para este tributo, escopo e período de vigência.'
                );
            }
        });
    }
}
