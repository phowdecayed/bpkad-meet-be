<?php

namespace App\Http\Requests;

use App\Rules\NoTimeConflict;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => ['required', 'date', new NoTimeConflict],
            'duration' => 'required|integer|min:1',
            'type' => ['required', Rule::in(['online', 'offline', 'hybrid'])],
            'location_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($this->type ?? null, ['offline', 'hybrid'])),
                'exists:meeting_locations,id',
            ],
            'password' => 'nullable|string|max:10', // Zoom password validation
            'settings' => 'nullable|array', // For Zoom settings
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:users,id',
        ];
    }
}
