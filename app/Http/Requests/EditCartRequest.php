<?php

namespace App\Http\Requests;

use App\Rules\CheckStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditCartRequest extends FormRequest
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
            'id' => [
                'required',
                Rule::exists('carts', 'id'),
            ],
            'product_id' => [
                'required',
                Rule::exists('products', 'id'),
            ],
            'qty' => [
                'required',
                'numeric',
                'min:1',
                new CheckStock($this->input('product_id'), $this->input('id')),
            ],
        ];
    }
}
