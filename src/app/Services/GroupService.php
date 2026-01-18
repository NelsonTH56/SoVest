<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Prediction;
use App\Models\User;
use App\Services\Interfaces\GroupServiceInterface;

class GroupService implements GroupServiceInterface
{
    /**
     * Create a new group
     */
    public function createGroup(int $userId, string $name, ?string $description, string $passcode)
    {
        try {
            // Check if group name already exists
            if (Group::where('name', $name)->exists()) {
                return [
                    'success' => false,
                    'message' => 'A group with this name already exists',
                ];
            }

            $group = new Group;
            $group->name = $name;
            $group->description = $description;
            $group->passcode = $passcode; // Will be hashed by mutator
            $group->admin_id = $userId;

            if (! $group->validate()) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $group->getErrors(),
                ];
            }

            $group->save();

            // Add the creator as a member
            $group->members()->attach($userId, ['joined_at' => now()]);

            return [
                'success' => true,
                'message' => 'Group created successfully',
                'group' => $group,
            ];
        } catch (\Exception $e) {
            error_log('Error creating group: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error creating group: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Join an existing group
     */
    public function joinGroup(int $userId, int $groupId, string $passcode)
    {
        try {
            $group = Group::find($groupId);

            if (! $group) {
                return [
                    'success' => false,
                    'message' => 'Group not found',
                ];
            }

            // Check if user is already a member
            if ($group->isMember($userId)) {
                return [
                    'success' => false,
                    'message' => 'You are already a member of this group',
                ];
            }

            // Verify passcode
            if (! $group->verifyPasscode($passcode)) {
                return [
                    'success' => false,
                    'message' => 'Incorrect passcode',
                ];
            }

            // Add user to group
            $group->members()->attach($userId, ['joined_at' => now()]);

            return [
                'success' => true,
                'message' => 'Successfully joined the group',
            ];
        } catch (\Exception $e) {
            error_log('Error joining group: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error joining group: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Leave a group
     */
    public function leaveGroup(int $userId, int $groupId)
    {
        try {
            $group = Group::find($groupId);

            if (! $group) {
                return [
                    'success' => false,
                    'message' => 'Group not found',
                ];
            }

            // Check if user is the admin
            if ($group->isAdmin($userId)) {
                return [
                    'success' => false,
                    'message' => 'Admins cannot leave their group. Transfer ownership or delete the group instead.',
                ];
            }

            // Check if user is a member
            if (! $group->isMember($userId)) {
                return [
                    'success' => false,
                    'message' => 'You are not a member of this group',
                ];
            }

            // Remove user from group
            $group->members()->detach($userId);

            return [
                'success' => true,
                'message' => 'Successfully left the group',
            ];
        } catch (\Exception $e) {
            error_log('Error leaving group: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error leaving group: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Remove a member from a group (admin only)
     */
    public function removeMember(int $adminId, int $groupId, int $userId)
    {
        try {
            $group = Group::find($groupId);

            if (! $group) {
                return [
                    'success' => false,
                    'message' => 'Group not found',
                ];
            }

            // Verify the requester is the admin
            if (! $group->isAdmin($adminId)) {
                return [
                    'success' => false,
                    'message' => 'Only the group admin can remove members',
                ];
            }

            // Cannot remove yourself (the admin)
            if ($userId === $adminId) {
                return [
                    'success' => false,
                    'message' => 'Admins cannot remove themselves from the group',
                ];
            }

            // Check if target user is a member
            if (! $group->isMember($userId)) {
                return [
                    'success' => false,
                    'message' => 'User is not a member of this group',
                ];
            }

            // Remove the member
            $group->members()->detach($userId);

            return [
                'success' => true,
                'message' => 'Member removed successfully',
            ];
        } catch (\Exception $e) {
            error_log('Error removing member: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error removing member: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get leaderboard for a group
     */
    public function getGroupLeaderboard(int $groupId, int $limit = 20)
    {
        try {
            $group = Group::find($groupId);

            if (! $group) {
                return [];
            }

            // Get group members ordered by reputation score
            $members = $group->members()
                ->select([
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'users.reputation_score',
                ])
                ->orderBy('users.reputation_score', 'desc')
                ->limit($limit)
                ->get();

            // For each member, fetch prediction count and average accuracy
            $result = $members->map(function ($user) {
                $predictionsCount = Prediction::where('user_id', $user->id)->count();
                $avgAccuracy = Prediction::where('user_id', $user->id)
                    ->whereNotNull('accuracy')
                    ->avg('accuracy');

                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'reputation_score' => $user->reputation_score,
                    'predictions_count' => $predictionsCount ?? 0,
                    'avg_accuracy' => $avgAccuracy ?? 0,
                    'joined_at' => $user->pivot->joined_at,
                ];
            })->toArray();

            return $result;
        } catch (\Exception $e) {
            error_log('Error fetching group leaderboard: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get all groups a user belongs to
     */
    public function getUserGroups(int $userId)
    {
        try {
            $user = User::find($userId);

            if (! $user) {
                return [];
            }

            return $user->groups()
                ->withCount('members')
                ->get()
                ->map(function ($group) use ($userId) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'member_count' => $group->members_count,
                        'is_admin' => $group->admin_id === $userId,
                        'joined_at' => $group->pivot->joined_at,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            error_log('Error fetching user groups: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get all groups (for discovery)
     */
    public function getAllGroups()
    {
        try {
            return Group::withCount('members')
                ->with('admin:id,first_name,last_name')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'member_count' => $group->members_count,
                        'admin_name' => $group->admin ? $group->admin->first_name.' '.$group->admin->last_name : 'Unknown',
                        'created_at' => $group->created_at,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            error_log('Error fetching all groups: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Update group passcode (admin only)
     */
    public function updateGroupPasscode(int $groupId, int $adminId, string $newPasscode)
    {
        try {
            $group = Group::find($groupId);

            if (! $group) {
                return [
                    'success' => false,
                    'message' => 'Group not found',
                ];
            }

            // Verify the requester is the admin
            if (! $group->isAdmin($adminId)) {
                return [
                    'success' => false,
                    'message' => 'Only the group admin can change the passcode',
                ];
            }

            // Validate new passcode
            if (strlen($newPasscode) < 4) {
                return [
                    'success' => false,
                    'message' => 'Passcode must be at least 4 characters',
                ];
            }

            $group->passcode = $newPasscode; // Will be hashed by mutator
            $group->save();

            return [
                'success' => true,
                'message' => 'Passcode updated successfully',
            ];
        } catch (\Exception $e) {
            error_log('Error updating passcode: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error updating passcode: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get group by ID
     */
    public function getGroup(int $groupId)
    {
        return Group::find($groupId);
    }
}
