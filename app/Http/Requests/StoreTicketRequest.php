<?php

namespace App\Http\Requests;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketType;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\ServiceTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A client's request or complaint, sent from their signed service-desk link. */
class StoreTicketRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'ticket';
    }

    protected function heldFields(): array
    {
        return ['photos' => true];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        $this->merge(['reporter_email' => $this->filled('reporter_email') ? strtolower(input_string($this->input('reporter_email'))) : null]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TicketType::class)],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'reporter_name' => ['required', 'string', 'max:120'],
            'reporter_email' => ['nullable', 'required_without:reporter_phone', 'email:rfc', 'max:255'],
            'reporter_phone' => ['nullable', 'required_without:reporter_email', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'location' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['file', 'max:8192', 'mimes:'.implode(',', ServiceTicket::PHOTO_TYPES)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reporter_name' => 'your name', 'reporter_email' => 'email', 'reporter_phone' => 'phone', 'photos.*' => 'photo'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.required' => 'Choose request, complaint or security incident.',
            'reporter_email.required_without' => 'Give an email address or a phone number so we can update you.',
            'reporter_phone.required_without' => 'Give an email address or a phone number so we can update you.',
            'reporter_phone.regex' => 'Enter a phone number we can call, for example 087 123 4567.',
            'description.min' => 'Please describe it in a little more detail (at least 10 characters).',
            'photos.max' => 'Attach up to three photos.',
            'photos.*.max' => 'Each photo must be 8 MB or smaller.',
            'photos.*.mimes' => 'Photos must be JPG, PNG, HEIC or WebP.',
        ];
    }
}
