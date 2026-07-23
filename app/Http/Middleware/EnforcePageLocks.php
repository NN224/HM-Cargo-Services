<?php

namespace App\Http\Middleware;

use App\Enums\LockablePage;
use App\Filament\Pages\PageLocked;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single enforcement point for per-employee page locks.
 *
 * Central by design: one check over every panel request, driven by the
 * LockablePage registry, so no destination can be lockable in the UI yet
 * unenforced. A page added later is covered the moment its key is in the
 * registry — no new enforcement code.
 */
class EnforcePageLocks
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $page = LockablePage::fromRouteName($request->route()?->getName());

        if ($user instanceof User && $page !== null && $user->isPageLocked($page)) {
            return redirect(PageLocked::getUrl());
        }

        return $next($request);
    }
}
