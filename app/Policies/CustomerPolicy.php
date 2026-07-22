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
        // Filament ties page reachability to this ability: EditRecord aborts
        // with 403 on mount when it is false (canEdit() -> update policy),
        // not just the save action. Gating it on ManageCustomers therefore
        // locked an employee out of the whole page, not merely the money in
        // it — and the page is one they need for receiving cargo. So this is
        // open, and the rate per kilogram (the actual money, D-024) is
        // guarded separately as its own section (EditCustomer::ratesSection()).
        // This does widen what an employee may save here: name, phone, the
        // credit-customer flag, and active/inactive — a deliberate trade,
        // not an oversight (see task-4-report.md).
        return true;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasCapability(Capability::DeleteRecords);
    }
}
