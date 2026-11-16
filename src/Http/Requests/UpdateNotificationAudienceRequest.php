<?php

namespace App\Domain\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationAudienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notify_subject_user' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'max:64'],
            'users' => ['sometimes', 'array'],
            'users.*' => ['uuid', 'exists:users,id'],
        ];
    }

    public function notifySubjectUser(): bool
    {
        return (bool) $this->boolean('notify_subject_user');
    }

    /**
     * @return list<string>
     */
    public function roleNames(): array
    {
        return array_values(array_filter(array_map('strval', (array) $this->input('roles', []))));
    }

    /**
     * @return list<string>
     */
    public function userIds(): array
    {
        return array_values(array_filter(array_map('strval', (array) $this->input('users', []))));
    }
}
