<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\NotaFiscal\NotaFiscalStatusService;

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
                    NotaFiscalStatusService::STATUS_LANCADA,
                    NotaFiscalStatusService::STATUS_DIGITACAO,
                    NotaFiscalStatusService::STATUS_ERRO,
                    NotaFiscalStatusService::STATUS_AUTORIZADA,
                    NotaFiscalStatusService::STATUS_CANCELADA,
                    NotaFiscalStatusService::STATUS_INUTILIZADA,
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
