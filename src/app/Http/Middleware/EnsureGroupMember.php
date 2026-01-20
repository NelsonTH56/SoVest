<?php

namespace App\Http\Middleware;

use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGroupMember
{
    /**
     * Handle an incoming request.
     * Verify that the authenticated user is a member of the specified group.
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

        if (! $group->isMember($userId)) {
            return redirect()->route('groups.index')
                ->with('error', 'You must be a member of this group to view it.');
        }

        return $next($request);
    }
}
