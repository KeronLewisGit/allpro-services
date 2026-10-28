<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** A quote / site-survey request. The survey date must be a weekday, at least two working days out. */
class StoreQuoteRequest extends FormRequest
{
    use HoldsUploads;

    public const string EIRCODE = '/^(?:[AC-FHKNPRTV-Y]\d{2}|D6W)\s?[0-9AC-FHKNPRTV-Y]{4}$/i';

    public static function heldForm(): string
    {
        return 'quote';
    }

    protected function heldFields(): array
    {
        return ['files' => true];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        $this->merge([
            'email' => strtolower(input_string($this->input('email'))),
            'eircode' => format_eircode(input_string($this->input('eircode'))),
            'is_tender' => $this->boolean('is_tender'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', Rule::in(array_keys((array) allpro('services')))],
            'sector' => ['required', Rule::in(array_keys((array) allpro('sectors')))],
            'organisation' => ['required', 'string', 'max:160'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_role' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'site_name' => ['required', 'string', 'max:160'],
            'site_address' => ['required', 'string', 'max:255'],
            'town' => ['required', 'string', 'max:80'],
            'county' => ['required', Rule::in((array) allpro('counties'))],
            'eircode' => ['nullable', 'string', 'regex:'.self::EIRCODE],
            'site_count' => ['required', 'integer', 'min:1', 'max:500'],
            'floor_area_m2' => ['nullable', 'integer', 'min:10', 'max:1000000'],
            'cover' => ['nullable', 'string', 'max:2000'],
            'start_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'survey_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.QuoteRequest::earliestSurvey()->format('Y-m-d'), 'before:'.today()->addMonths(3)->format('Y-m-d')],
            'survey_window' => ['required', Rule::in(array_keys(QuoteRequest::WINDOWS))],
            'is_tender' => ['boolean'],
            'tender_reference' => ['nullable', 'string', 'max:80'],
            'tender_closes_on' => ['nullable', 'required_if:is_tender,true', 'date_format:Y-m-d', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:3000'],
            'files' => ['nullable', 'array', 'max:3'],
            'files.*' => ['file', 'max:10240', 'mimes:'.implode(',', QuoteRequest::FILE_TYPES)],
            'consent' => ['accepted'],
        ];
    }

    /**
     * Surveys happen on weekdays.
     *
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $v): void {
            $date = input_string($this->input('survey_on'));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && ! $v->errors()->has('survey_on') && date('N', (int) strtotime($date)) >= 6) {
                $v->errors()->add('survey_on', 'Site surveys are carried out Monday to Friday. Choose a weekday.');
            }
        }];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['contact_name' => 'your name', 'site_name' => 'site name', 'site_address' => 'site address', 'site_count' => 'number of sites',
            'floor_area_m2' => 'floor area', 'survey_on' => 'survey date', 'survey_window' => 'time of day', 'tender_closes_on' => 'tender closing date', 'files.*' => 'file'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'services.required' => 'Tick at least one service.',
            'sector.required' => 'Choose the sector that best describes the site.',
            'phone.regex' => 'Enter a phone number we can call, for example 091 123 456.',
            'eircode.regex' => 'That does not look like an Eircode (for example H91 X2Y3). Leave it blank if you are not sure.',
            'survey_on.after_or_equal' => 'Choose a survey date at least '.QuoteRequest::SURVEY_LEAD_DAYS.' working days from today, so the regional team can plan the visit.',
            'survey_on.before' => 'Choose a survey date within the next three months.',
            'tender_closes_on.required_if' => 'Enter the tender closing date.',
            'files.max' => 'Attach up to three files.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
            'files.*.mimes' => 'Files must be PDF, Word, Excel, DWG, JPG or PNG.',
            'consent.accepted' => 'Please agree to us using these details to prepare your quote.',
        ];
    }
}
