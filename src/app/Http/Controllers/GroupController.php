<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\Interfaces\GroupServiceInterface;
use App\Services\Interfaces\ResponseFormatterInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    protected $groupService;

    public function __construct(
        ResponseFormatterInterface $responseFormatter,
        GroupServiceInterface $groupService
    ) {
        parent::__construct($responseFormatter);
        $this->groupService = $groupService;
    }

    /**
     * Display all groups (for discovery) with user's groups highlighted
     */
    public function index()
    {
        $userId = Auth::id();
        $userData = Auth::user();

        // Get all groups for discovery
        $allGroups = $this->groupService->getAllGroups();

        // Get user's groups to highlight them
        $userGroups = $this->groupService->getUserGroups($userId);
        $userGroupIds = array_column($userGroups, 'id');

        // Mark which groups the user is a member of
        foreach ($allGroups as &$group) {
            $group['is_member'] = in_array($group['id'], $userGroupIds);
            // Find if user is admin from userGroups
            $userGroupData = array_filter($userGroups, fn ($g) => $g['id'] === $group['id']);
            $group['is_admin'] = ! empty($userGroupData) && reset($userGroupData)['is_admin'];
        }

        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => $userData['reputation_score'] ?? 0,
        ];

        return view('groups.index', [
            'allGroups' => $allGroups,
            'userGroups' => $userGroups,
            'Curruser' => $Curruser,
        ]);
    }

    /**
     * Show create group form
     */
    public function create()
    {
        $userData = Auth::user();

        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => $userData['reputation_score'] ?? 0,
        ];

        return view('groups.create', compact('Curruser'));
    }

    /**
     * Store a new group
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:100|unique:groups,name',
            'description' => 'nullable|string|max:500',
            'passcode' => 'required|string|min:4|max:50',
        ], [
            'name.unique' => 'A group with this name already exists. Please choose a different name.',
        ]);

        $userId = Auth::id();

        $result = $this->groupService->createGroup(
            $userId,
            $request->input('name'),
            $request->input('description'),
            $request->input('passcode')
        );

        if ($result['success']) {
            return redirect()->route('groups.show', $result['group']->id)
                ->with('success', 'Group created successfully!');
        }

        return back()->withInput()->withErrors(['error' => $result['message']]);
    }

    /**
     * Show group page with leaderboard
     */
    public function show($id)
    {
        $userId = Auth::id();
        $userData = Auth::user();

        $group = $this->groupService->getGroup($id);

        if (! $group) {
            abort(404, 'Group not found');
        }

        // Get group leaderboard
        $leaderboard = $this->groupService->getGroupLeaderboard($id, 20);

        // Find user's rank in the leaderboard
        $userRank = 0;
        $userInfo = null;

        foreach ($leaderboard as $index => $user) {
            if ($user['id'] == $userId) {
                $userRank = $index + 1;
                $userInfo = $user;
                break;
            }
        }

        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => $userData['reputation_score'] ?? 0,
        ];

        return view('groups.show', [
            'group' => $group,
            'leaderboard' => $leaderboard,
            'userRank' => $userRank,
            'userInfo' => $userInfo,
            'userID' => $userId,
            'isAdmin' => $group->isAdmin($userId),
            'Curruser' => $Curruser,
        ]);
    }

    /**
     * Join a group by name and passcode
     */
    public function joinByName(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string',
            'passcode' => 'required|string',
        ]);

        $userId = Auth::id();
        $groupName = $request->input('group_name');
        $passcode = $request->input('passcode');

        // Find group by name
        $group = Group::where('name', $groupName)->first();

        if (! $group) {
            return response()->json([
                'success' => false,
                'message' => 'No group found with that name.',
            ], 404);
        }

        // Check if already a member
        if ($group->isMember($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are already a member of this group.',
            ]);
        }

        // Attempt to join with the passcode
        $result = $this->groupService->joinGroup($userId, $group->id, $passcode);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the group!',
                'redirect' => route('groups.show', $group->id),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Invalid passcode.',
        ], 400);
    }

    /**
     * Show join group form
     */
    public function join($id)
    {
        $userId = Auth::id();
        $userData = Auth::user();

        $group = $this->groupService->getGroup($id);

        if (! $group) {
            abort(404, 'Group not found');
        }

        // Check if already a member
        if ($group->isMember($userId)) {
            return redirect()->route('groups.show', $id)
                ->with('info', 'You are already a member of this group.');
        }

        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => $userData['reputation_score'] ?? 0,
        ];

        return view('groups.join', [
            'group' => $group,
            'Curruser' => $Curruser,
        ]);
    }

    /**
     * Process join group request
     */
    public function processJoin(Request $request, $id)
    {
        $request->validate([
            'passcode' => 'required|string',
        ]);

        $userId = Auth::id();

        $result = $this->groupService->joinGroup(
            $userId,
            $id,
            $request->input('passcode')
        );

        if ($result['success']) {
            return redirect()->route('groups.show', $id)
                ->with('success', 'Successfully joined the group!');
        }

        return back()->withErrors(['passcode' => $result['message']]);
    }

    /**
     * Leave a group
     */
    public function leave($id)
    {
        $userId = Auth::id();

        $result = $this->groupService->leaveGroup($userId, $id);

        if ($result['success']) {
            return redirect()->route('groups.index')
                ->with('success', 'You have left the group.');
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Show group settings (admin only)
     */
    public function settings($id)
    {
        $userId = Auth::id();
        $userData = Auth::user();

        $group = $this->groupService->getGroup($id);

        if (! $group) {
            abort(404, 'Group not found');
        }

        // Get group members for member management
        $members = $group->members()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->withPivot('joined_at')
            ->get();

        $Curruser = [
            'username' => $userData['email'],
            'full_name' => ($userData['first_name'] ?? '').' '.($userData['last_name'] ?? ''),
            'profile_picture' => $userData['profile_picture'],
            'reputation_score' => $userData['reputation_score'] ?? 0,
        ];

        return view('groups.settings', [
            'group' => $group,
            'members' => $members,
            'Curruser' => $Curruser,
        ]);
    }

    /**
     * Update group passcode (admin only)
     */
    public function updatePasscode(Request $request, $id)
    {
        $request->validate([
            'passcode' => 'required|string|min:4|max:50',
        ]);

        $userId = Auth::id();

        $result = $this->groupService->updateGroupPasscode(
            $id,
            $userId,
            $request->input('passcode')
        );

        if ($result['success']) {
            return back()->with('success', 'Passcode updated successfully.');
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * Remove a member from group (admin only)
     */
    public function removeMember($id, $userId)
    {
        $adminId = Auth::id();

        $result = $this->groupService->removeMember($adminId, $id, $userId);

        if ($result['success']) {
            return back()->with('success', 'Member removed successfully.');
        }

        return back()->withErrors(['error' => $result['message']]);
    }

    /**
     * API: Look up a group by its code
     */
    public function lookupByCode(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a group code.',
            ]);
        }

        $group = Group::where('code', $code)->first();

        if (! $group) {
            return response()->json([
                'success' => false,
                'message' => 'No group found with that code.',
            ]);
        }

        return response()->json([
            'success' => true,
            'group_id' => $group->id,
            'group_name' => $group->name,
        ]);
    }
}
