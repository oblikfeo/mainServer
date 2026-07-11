<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\Telegram\TelegramBotRegistrationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return;
        }

        if (! TelegramBotRegistrationService::isPlaceholderTelegramEmail($user->email)) {
            return;
        }

        $incoming = strtolower(trim((string) $this->input('email', '')));
        if ($incoming === '') {
            $this->merge(['email' => $user->email]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Укажите имя.',
            'name.max' => 'Имя не должно быть длиннее :max символов.',
            'email.required' => 'Укажите адрес электронной почты.',
            'email.email' => 'Введите корректный адрес электронной почты.',
            'email.max' => 'Адрес почты не должен быть длиннее :max символов.',
            'email.unique' => 'Этот адрес почты уже используется.',
        ];
    }
}
