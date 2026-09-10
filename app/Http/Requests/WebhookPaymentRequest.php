<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class WebhookPaymentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'string'],
            'event_type' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'order_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric'],
            'timestamp' => ['required', 'date'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * The webhook payload is not trusted just because the signature is
     * valid, so a structurally invalid payload is rejected before a
     * webhook_events record is ever created.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Invalid webhook payload.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
