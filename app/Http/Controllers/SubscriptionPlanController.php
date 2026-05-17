<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Http\Requests\UpdateSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Responses\ApiResponse;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    public function __construct(protected SubscriptionPlanService $service)
    {
    }

    /**
     * Get all subscription plans.
     *
     * Query params:
     * - active_only: bool - Show only active plans
     * - per_page: int - Items per page
     */
    public function index(): JsonResponse
    {
        $activeOnly = request()->query('active_only', false);
        $perPage = request()->query('per_page', 15);

        $plans = $this->service->getAllPlans($activeOnly, $perPage);

        return ApiResponse::success(
            SubscriptionPlanResource::collection($plans),
            'Subscription plans retrieved successfully.'
        );
    }

    /**
     * Get a single subscription plan by ID.
     *
     * Returns 404 error if plan not found.
     */
    public function show(Request $request, int $subscriptionPlan): JsonResponse
    {
        $plan = SubscriptionPlan::find($subscriptionPlan);

        if (!$plan) {
            return ApiResponse::error(
                'Subscription plan not found.',
                null,
                404
            );
        }

        return ApiResponse::success(
            new SubscriptionPlanResource($plan),
            'Subscription plan retrieved successfully.'
        );
    }

    /**
     * Create a new subscription plan.
     */
    public function store(StoreSubscriptionPlanRequest $request): JsonResponse
    {
        $plan = $this->service->createPlan($request->validated());

        return ApiResponse::success(
            new SubscriptionPlanResource($plan),
            'Subscription plan created successfully.',
            201
        );
    }

    /**
     * Update an existing subscription plan.
     */
    public function update(UpdateSubscriptionPlanRequest $request, int $subscriptionPlan): JsonResponse
    {
        $plan = SubscriptionPlan::find($subscriptionPlan);

        if (!$plan) {
            return ApiResponse::error(
                'Subscription plan not found.',
                null,
                404
            );
        }

        $plan = $this->service->updatePlan($plan, $request->validated());

        return ApiResponse::success(
            new SubscriptionPlanResource($plan),
            'Subscription plan updated successfully.'
        );
    }

    /**
     * Delete a subscription plan.
     */
    public function destroy(int $subscriptionPlan): JsonResponse
    {
        $plan = SubscriptionPlan::find($subscriptionPlan);

        if (!$plan) {
            return ApiResponse::error(
                'Subscription plan not found.',
                null,
                404
            );
        }

        $this->service->deletePlan($plan);

        return ApiResponse::success(null, 'Subscription plan deleted successfully.');
    }
}
