<?php

namespace Modules\Car\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class CarsRequest extends FormRequest
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
            'name' => 'required|max:191',
            'category_id' => 'required|numeric',
            'status' => 'required',
        ];
    }
}
