<?php

namespace App\Services\Interfaces;

/**
 * GroupServiceInterface
 *
 * This interface defines the contract for group management
 * in the SoVest application.
 */
interface GroupServiceInterface
{
    /**
     * Create a new group
     *
     * @param  int  $userId  User creating the group (becomes admin)
     * @param  string  $name  Group name
     * @param  string|null  $description  Group description
     * @param  string  $passcode  Passcode for joining
     * @return array Created group data or error
     */
    public function createGroup(int $userId, string $name, ?string $description, string $passcode);

    /**
     * Join an existing group
     *
     * @param  int  $userId  User joining the group
     * @param  int  $groupId  Group to join
     * @param  string  $passcode  Passcode attempt
     * @return array Success status and message
     */
    public function joinGroup(int $userId, int $groupId, string $passcode);

    /**
     * Leave a group
     *
     * @param  int  $userId  User leaving the group
     * @param  int  $groupId  Group to leave
     * @return array Success status and message
     */
    public function leaveGroup(int $userId, int $groupId);

    /**
     * Remove a member from a group (admin only)
     *
     * @param  int  $adminId  Admin performing the action
     * @param  int  $groupId  Group ID
     * @param  int  $userId  User to remove
     * @return array Success status and message
     */
    public function removeMember(int $adminId, int $groupId, int $userId);

    /**
     * Get leaderboard for a group
     *
     * @param  int  $groupId  Group ID
     * @param  int  $limit  Number of users to return
     * @return array Top users in the group
     */
    public function getGroupLeaderboard(int $groupId, int $limit = 20);

    /**
     * Get all groups a user belongs to
     *
     * @param  int  $userId  User ID
     * @return array User's groups
     */
    public function getUserGroups(int $userId);

    /**
     * Get all groups (for discovery)
     *
     * @return array All groups with member counts
     */
    public function getAllGroups();

    /**
     * Update group passcode (admin only)
     *
     * @param  int  $groupId  Group ID
     * @param  int  $adminId  Admin user ID
     * @param  string  $newPasscode  New passcode
     * @return array Success status and message
     */
    public function updateGroupPasscode(int $groupId, int $adminId, string $newPasscode);

    /**
     * Get group by ID
     *
     * @param  int  $groupId  Group ID
     * @return \App\Models\Group|null
     */
    public function getGroup(int $groupId);
}
