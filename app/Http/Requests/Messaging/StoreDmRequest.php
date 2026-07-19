<?php

namespace App\Http\Requests\Messaging;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreDmRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any active user may start a DM; WHO they may reach is validated below.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /** The recipient must be in the sender's role-scoped contact list. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $allowed = $this->user()->contactableUsers()->pluck('id');

            if (! $allowed->contains((int) $this->input('recipient_id'))) {
                $validator->errors()->add('recipient_id', 'لا يمكنك مراسلة هذا المستخدم.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'recipient_id' => 'المستلم',
            'body' => 'الرسالة',
        ];
    }
}
