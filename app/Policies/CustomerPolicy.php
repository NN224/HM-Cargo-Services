<?php

namespace App\Policies;

use App\Enums\Capability;
use App\Models\Customer;
use App\Models\User;

/**
 * Customer authorization.
 *
 * Two questions are kept deliberately separate:
 *   - may this user do it?      answered here, by role and capability
 *   - is it safe to do?         answered by Customer::canBeDeleted()
 *
 * Both must agree before a record is removed. Holding the delete capability
 * never overrides a dependency (D-021).
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasCapability(Capability::ManageCustomers);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasCapability(Capability::ManageCustomers);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasCapability(Capability::DeleteRecords);
    }
}
