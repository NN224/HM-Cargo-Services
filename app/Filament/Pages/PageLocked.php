<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LocksWhenUnauthorized;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/**
 * Where the middleware sends an employee who opened a locked page.
 *
 * It is not itself lockable and carries no navigation entry — it is only ever
 * reached by redirect. It renders the shared locked notice so a page locked by
 * an administrator looks the same as one gated by a missing capability.
 */
class PageLocked extends Page
{
    use LocksWhenUnauthorized;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'page-locked';

    protected static ?string $title = 'صفحة مقفلة';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->lockNotice('هذه الصفحة مقفلة من الإدارة.'),
        ]);
    }
}
