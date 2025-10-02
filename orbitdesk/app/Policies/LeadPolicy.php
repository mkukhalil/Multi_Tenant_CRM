<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Controlled in controller
    }

    public function view(User $user, Lead $lead)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id === $lead->tenant_id && 
               ($user->hasRole('Tenant Admin') || 
                $user->id === $lead->assigned_to || 
                $user->id === $lead->created_by ||
                ($user->hasRole('Manager') && $this->isAgentOfManager($user, $lead)));
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['Super Admin', 'Tenant Admin', 'Manager', 'Agent']);
    }

    public function update(User $user, Lead $lead)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id === $lead->tenant_id && 
               ($user->hasRole('Tenant Admin') || 
                $user->id === $lead->assigned_to || 
                $user->id === $lead->created_by ||
                ($user->hasRole('Manager') && $this->isAgentOfManager($user, $lead)));
    }

    public function delete(User $user, Lead $lead)
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->tenant_id === $lead->tenant_id && 
               ($user->hasRole('Tenant Admin') || $user->id === $lead->created_by);
    }

    public function restore(User $user, Lead $lead)
    {
        return $this->update($user, $lead);
    }

    public function forceDelete(User $user, Lead $lead)
    {
        return $this->delete($user, $lead);
    }

    private function isAgentOfManager(User $manager, Lead $lead)
    {
        return User::role('Agent')
            ->where('tenant_id', $manager->tenant_id)
            ->where('id', $lead->assigned_to)
            ->exists();
    }
}
