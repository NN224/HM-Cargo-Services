<?php

namespace App\Models\Concerns;

use DomainException;

/**
 * Safe deletion (D-021).
 *
 * A record may be permanently removed only when nothing depends on it. This
 * keeps genuine mistakes correctable while making it impossible to leave a
 * historical invoice, statement or report pointing at something that no longer
 * exists.
 *
 * A model using this trait declares its dependants in
 * {@see deletionDependencies()} as `Arabic label => relation name`.
 */
trait GuardsDeletion
{
    /**
     * Relations that block deletion.
     *
     * Note the distinction between a dependant and a part: a shipment's
     * packages are part of the shipment and go with it, so they are not
     * listed. Its payments are separate records that reference it, so they
     * are.
     *
     * @return array<string, string>
     */
    abstract protected function deletionDependencies(): array;

    /**
     * The Arabic names of whatever is currently blocking deletion.
     *
     * Returned rather than a bare boolean so the interface can tell the
     * operator exactly what to deal with instead of just refusing.
     *
     * @return array<int, string>
     */
    public function deletionBlockers(): array
    {
        $blockers = [];

        foreach ($this->deletionDependencies() as $label => $relation) {
            if ($this->{$relation}()->exists()) {
                $blockers[] = $label;
            }
        }

        return $blockers;
    }

    public function canBeDeleted(): bool
    {
        return $this->deletionBlockers() === [];
    }

    /**
     * Delete, or explain precisely why not.
     *
     * @throws DomainException when something still depends on this record
     */
    public function deleteSafely(): void
    {
        $blockers = $this->deletionBlockers();

        if ($blockers !== []) {
            throw new DomainException(
                'لا يمكن حذف هذا السجل لأنه مرتبط بـ: '.implode('، ', $blockers)
                .'. يمكنك تعطيله بدلاً من حذفه.'
            );
        }

        $this->delete();
    }
}
