<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\NotaFiscal\NotaFiscalService;

class NotaFiscalStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    NotaFiscalService::STATUS_LANCADA,
                    NotaFiscalService::STATUS_DIGITACAO,
                    NotaFiscalService::STATUS_ERRO,
                    NotaFiscalService::STATUS_AUTORIZADA,
                    NotaFiscalService::STATUS_CANCELADA,
                    NotaFiscalService::STATUS_INUTILIZADA,
                ]),
            ],
            'nfeautorizacao' => 'nullable|string|max:100',
            'nfedataautorizacao' => 'nullable|date',
            'nfecancelamento' => 'nullable|string|max:100',
            'nfedatacancelamento' => 'nullable|date',
            'nfeinutilizacao' => 'nullable|string|max:100',
            'nfedatainutilizacao' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status informado não é válido. Valores aceitos: LAN, DIG, ERR, AUT, CAN, INU.',
        ];
    }
}
