<?php

namespace Mg\Delivery\Validators;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryRequestValidator extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'deal_id' => 'required|uuid',
            'name' => 'required|string',
            'phone' => 'required|string',
            'street' => 'required|string',
            'number' => 'required|numeric',
            'neighborhood' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'additional_info' => 'nullable|string',
            'payment_method' => 'required|string',
            'observations' => 'nullable|string',
        ];
    }
}
