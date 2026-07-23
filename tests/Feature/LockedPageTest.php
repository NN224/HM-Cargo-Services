<?php

use App\Enums\Capability;
use App\Filament\Concerns\LocksWhenUnauthorized;
use Filament\Schemas\Components\Section;

/**
 * The shared locked-page mechanism.
 *
 * One presentation is met by an employee who reaches a page they may not see,
 * whether the reason is a missing capability (this piece) or an administrator's
 * lock (piece 3). The reason is a parameter precisely so both can use it.
 */
test('the lock notice carries the reason it was given', function () {
    $subject = new class
    {
        use LocksWhenUnauthorized;

        public function build(string $reason): Section
        {
            return $this->lockNotice($reason);
        }
    };

    $notice = $subject->build('سبب مخصّص للاختبار');

    expect($notice)->toBeInstanceOf(Section::class);

    // Section has no toArray() in this Filament version (5.7). Both public,
    // container-free accessors below expose the built component tree instead:
    // the heading directly on the Section, and the reason as the constant
    // state of the child TextEntry — without needing a Livewire/container
    // context, which getChildComponents()/toHtml() would require.
    expect($notice->getHeading())->toContain('هذه الصفحة مقفلة');

    $children = $notice->getDefaultChildComponents();
    expect($children[0]->getState())->toContain('سبب مخصّص للاختبار');
});

test('the capability reason names the capability in Arabic', function () {
    $subject = new class
    {
        use LocksWhenUnauthorized;

        public function reason(Capability $c): string
        {
            return $this->capabilityLockReason($c);
        }
    };

    $reason = $subject->reason(Capability::RecordPayments);

    // Capability::RecordPayments->label() is 'تسجيل الدفعات'.
    expect($reason)->toContain(Capability::RecordPayments->label());
});
