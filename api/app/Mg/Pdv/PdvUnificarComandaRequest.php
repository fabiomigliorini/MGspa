<?php

namespace Mg\Pdv;

class PdvUnificarComandaRequest extends PdvRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'codpessoa' => 'nullable|integer',
            'codpessoavendedor' => 'nullable|integer',
        ]);
    }
}
