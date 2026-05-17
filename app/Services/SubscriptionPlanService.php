<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * SubscriptionPlanService handles business logic for subscription plans.
 *
 * This service manages CRUD operations and queries for subscription plans.
 * Business logic stays here, not in the controller.
 */
class SubscriptionPlanService
{
    /**
     * Get all subscription plans with optional filtering.
     *
     * @param  bool  $activeOnly
     * @param  int  $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllPlans(bool $activeOnly = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = SubscriptionPlan::query();

        if ($activeOnly) {
            $query->active();
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a specific subscription plan by ID.
     *
     * @param  int  $id
     * @return \App\Models\SubscriptionPlan|null
     */
    public function getPlanById(int $id): ?SubscriptionPlan
    {
        return SubscriptionPlan::find($id);
    }

    /**
     * Create a new subscription plan.
     *
     * @param  array<string, mixed>  $data
     * @return \App\Models\SubscriptionPlan
     */
    public function createPlan(array $data): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'monthly_price' => $data['monthly_price'],
            'yearly_price' => $data['yearly_price'],
            'features' => $data['features'] ?? [],
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update an existing subscription plan.
     *
     * @param  \App\Models\SubscriptionPlan  $plan
     * @param  array<string, mixed>  $data
     * @return \App\Models\SubscriptionPlan
     */
    public function updatePlan(SubscriptionPlan $plan, array $data): SubscriptionPlan
    {
        $plan->update($data);

        return $plan;
    }

    /**
     * Delete a subscription plan.
     *
     * @param  \App\Models\SubscriptionPlan  $plan
     * @return bool
     */
    public function deletePlan(SubscriptionPlan $plan): bool
    {
        return $plan->delete();
    }
}
