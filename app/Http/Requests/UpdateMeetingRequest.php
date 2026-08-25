<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $meetingId = $this->route('meeting')->id ?? $this->route('meeting');

        return [
            'meeting_code' => 'required|string|max:50|unique:meetings,meeting_code,' . $meetingId,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:meeting,seminar,training,other',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'location' => 'required|string|max:255',
            'status' => 'required|in:draft,published,ongoing,completed,cancelled',
        ];
    }
}
