<?php

namespace App\Http\Requests\Zoom;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingRequest extends FormRequest
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
            'meetingId' => 'required|string',
            'topic' => 'sometimes|string',
            'start_time' => 'sometimes|date',
            'duration' => 'sometimes|integer|min:1',
            'password' => 'sometimes|string|max:10',
            'settings' => 'sometimes|array',
        ];
    }
}
