<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LeadServiceInterface;
use App\DTOs\LeadDTO;
use App\Events\LeadSubmittedEvent;
use App\Exceptions\LeadAlreadyExistsException;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Resources\LeadResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling lead operations.
 *
 * Manages lead creation, validation, and retrieval with proper
 * error handling and response formatting.
 */
final class LeadController extends Controller
{
    public function __construct(
        private readonly LeadServiceInterface $leadService
    ) {
    }

    /**
     * Store a newly created lead.
     *
     * @param StoreLeadRequest $request The validated request
     *
     * @return JsonResponse The response with lead data or error
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $leadDTO = LeadDTO::fromArray($request->validated());
            $lead = $this->leadService->createLead($leadDTO);

            // Fire event for asynchronous notification processing
            // This is non-blocking and will be processed via queue
            LeadSubmittedEvent::dispatch($leadDTO, [
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'submitted_at' => now()->toISOString(),
            ]);

            // Load the platform relationship if it exists
            $lead->load('platform');

            return response()->json([
                'success' => true,
                'message' => 'Lead submitted successfully',
                'data' => new LeadResource($lead),
            ], 201);
        } catch (LeadAlreadyExistsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'LEAD_EXISTS',
            ], 409);
        } catch (\Exception $e) {
            Log::error('Lead submission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the lead',
                'error_code' => 'SUBMISSION_ERROR',
            ], 500);
        }
    }

    /**
     * Check if a lead exists with the given email.
     *
     * @param Request $request The request instance
     * @param string  $email   The email to check
     *
     * @return JsonResponse The response with existence status
     */
    public function checkEmail(Request $request, string $email): JsonResponse
    {
        $validator = validator(['email' => $email], [
            'email' => ['required', 'email:rfc'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address.',
                'errors' => $validator->errors()->get('email'),
            ], 422);
        }

        $lead = $this->leadService->getLeadByEmail($email);

        return response()->json([
            'exists' => $lead !== null,
            'submitted_at' => $lead?->submitted_at?->toISOString(),
        ]);
    }
}
