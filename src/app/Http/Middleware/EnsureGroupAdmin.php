<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupAdmin
{
    /**
     * Handle an incoming request.
     * Verify that the authenticated user is the admin of the specified group.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $groupId = $request->route('id');
        $userId = Auth::id();

        if (! $userId) {
            return redirect()->route('login');
        }

        $group = Group::find($groupId);

        if (! $group) {
            abort(404, 'Group not found');
        }

        if (! $group->isAdmin($userId)) {
            return redirect()->route('groups.show', $groupId)
                ->with('error', 'Only the group admin can access this page.');
        }

        return $next($request);
    }
}
