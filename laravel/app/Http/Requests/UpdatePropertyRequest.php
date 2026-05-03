<?php

namespace App\Http\Requests;

use App\Enums\PropertyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates payload for `PUT` / `PATCH /api/properties/{id}` (Part A).
 */
class UpdatePropertyRequest extends FormRequest
{
    /**
     * Decide whether the authenticated user may run this request.
     *
     * Part A exposes a public JSON API with no auth; always allow.
     *
     * @return bool Always `true`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a property; each key uses `sometimes` so PATCH can send a subset.
     *
     * @return array<string, mixed> Laravel rule arrays (strings, `Rule`, etc.).
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:projects,id',
            ],
            'label' => [
                'sometimes',
                'required',
                'string',
                'max:'.(int) __('validations.limits.property_label_max_length'),
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::enum(PropertyStatus::class),
            ],
            'price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Human-readable attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'project_id' => __('validations.attributes.project_id'),
            'label' => __('validations.attributes.label'),
            'status' => __('validations.attributes.status'),
            'price' => __('validations.attributes.price'),
        ];
    }
}
