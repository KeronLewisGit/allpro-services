<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\Experience;
use App\Enums\Region;
use App\Enums\RightToWork;
use App\Enums\RoleType;
use App\Http\Requests\Concerns\HoldsUploads;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\Availability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The public job application. Security officers must give a current PSA licence number and
 * expiry. Garda vetting is never asked for here: HR invites the applicant through the NVB's
 * e-vetting system after an offer, so no vetting field or upload is accepted.
 */
class StoreApplicationRequest extends FormRequest
{
    use HoldsUploads;

    public static function heldForm(): string
    {
        return 'application';
    }

    protected function heldFields(): array
    {
        return ['cv' => false];
    }

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->restoreHeldUploads();
        // When an opening is picked, its role and region are the truth.
        $jobId = input_string($this->input('job_posting_id'));
        $job = ctype_digit($jobId) ? JobPosting::open()->find((int) $jobId) : null;
        if ($job) {
            $this->merge(['role_type' => $job->role_type->value, 'region' => $job->region->value]);
        }
        $psa = preg_replace('/\s+/', '', input_string($this->input('psa_licence_number')));
        $this->merge([
            'email' => $this->filled('email') ? strtolower(input_string($this->input('email'))) : null,
            'psa_licence_number' => $psa !== '' ? $psa : null,
            'availability' => is_array($this->input('availability')) ? Availability::clean($this->input('availability')) : [],
        ]);
    }

    public function role(): ?RoleType
    {
        return RoleType::tryFrom(input_string($this->input('role_type')));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->role();
        $psa = $role?->needsPsa() ? 'required' : 'nullable';

        return [
            'job_posting_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('status', 'open')
                ->where(fn ($q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()))],
            'role_type' => ['required', Rule::enum(RoleType::class)],
            'region' => ['required', Rule::enum(Region::class)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'county' => ['nullable', Rule::in((array) allpro('counties'))],
            'experience' => ['required', Rule::enum(Experience::class)],
            'right_to_work' => ['required', Rule::enum(RightToWork::class)],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'availability' => ['required', 'array', 'min:1'],
            'availability.*' => ['string', Rule::in(Availability::keys())],
            'drives' => ['nullable', 'boolean'],
            'available_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'psa_licence_number' => [$psa, 'nullable', 'regex:/^\d{5,10}$/', 'required_with:psa_licence_expires_on'],
            'psa_licence_expires_on' => [$psa, 'nullable', 'date_format:Y-m-d', 'after:today', 'required_with:psa_licence_number'],
            'message' => ['nullable', 'string', 'max:2000'],
            'cv' => ['nullable', 'file', 'max:5120', 'mimes:'.implode(',', JobApplication::CV_TYPES)],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, mixed> only the fields an applicant may set */
    public function applicationData(): array
    {
        $data = $this->safe()->except(['cv', 'consent']);
        if (! ($this->role()?->asksPsa() ?? false)) {
            unset($data['psa_licence_number'], $data['psa_licence_expires_on']);
        }

        return $data;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['first_name' => 'first name', 'last_name' => 'last name', 'available_from' => 'start date', 'cv' => 'CV', 'role_type' => 'role',
            'psa_licence_number' => 'PSA licence number', 'psa_licence_expires_on' => 'PSA licence expiry date', 'right_to_work' => 'right to work'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number we can call, for example 087 123 4567.',
            'job_posting_id.exists' => 'That opening has closed. Choose another opening, or send a general application.',
            'role_type.required' => 'Choose the role you are applying for.',
            'region.required' => 'Choose the region you want to work in.',
            'experience.required' => 'Choose how much experience you have.',
            'right_to_work.required' => 'Tell us whether you can work in Ireland.',
            'availability.required' => 'Tick at least one shift you can work.',
            'availability.min' => 'Tick at least one shift you can work.',
            'psa_licence_number.required' => 'Security officers need a current PSA licence. Enter the licence number from your PSA card.',
            'psa_licence_number.regex' => 'Enter the licence number as it appears on your PSA card (digits only).',
            'psa_licence_expires_on.required' => 'Enter the expiry date printed on your PSA licence card.',
            'psa_licence_expires_on.after' => 'That PSA licence has expired. Renew it with the PSA before applying for a security role.',
            'cv.max' => 'Your CV must be 5 MB or smaller.',
            'cv.mimes' => 'Your CV must be a PDF or Word document.',
            'cv.uploaded' => 'Your CV could not be uploaded. It must be 5 MB or smaller.',
            'consent.accepted' => 'Please agree to us keeping your application on file.',
        ];
    }
}
