<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserGeolocation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;

class UsersController extends Controller
{
    const ITEM_PER_PAGE = 10;
    public function userNotifications()
    {
        $user = $this->getUser();
        $notifications = $user->notifications()->orderBy('created_at', 'DESC')->take(50)->get();
        $unread_notifications = $user->unreadNotifications()->count();
        // if ($notifications->isEmpty()) {
        //     $notifications = $user->notifications()->orderBy('created_at', 'DESC')->take(20)->get();
        //     // $notifications =
        // }
        return response()->json(compact('notifications', 'unread_notifications'), 200);
    }
    public function markNotificationAsRead()
    {
        $user = $this->getUser();
        $user->unreadNotifications->markAsRead();
        return $this->userNotifications();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $searchParams = $request->all();
        $userQuery = User::query();
        $limit = Arr::get($searchParams, 'limit', static::ITEM_PER_PAGE);
        $role = Arr::get($searchParams, 'role', '');
        $keyword = Arr::get($searchParams, 'keyword', '');
        if (!empty($keyword)) {
            $userQuery->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('email', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('phone', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('address', 'LIKE', '%' . $keyword . '%');
            });
        }
        if (!empty($role)) {
            $userQuery->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        } else {
            $userQuery->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'customer');
            });
        }
        $userQuery->where('user_type', '!=', 'developer');

        return UserResource::collection($userQuery->paginate($limit));
    }
    public function allUsers(Request $request)
    {
        $userQuery = User::query();
        $userQuery->whereHas('roles', function ($q) {
            $q->whereNotIn('name', ['admin', 'super']);
        });
        $users = $userQuery->get();
        return response()->json(compact('users'), 200);
    }

    public function fetchSalesReps()
    {
        $user = $this->getUser();
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            list($sales_reps, $sales_reps_ids) = $this->teamMembers();
        } else {
            $userQuery = User::query();

            $userQuery->whereHas('roles', function ($q) {
                $q->where('name', 'sales_rep');
            });

            $sales_reps = $userQuery->get();
        }

        return response()->json(compact('sales_reps'), 200);
    }

    public function assignRole(Request $request, User $user)
    {
        $actor = $this->getUser();
        if ($actor->isSuperAdmin() || $actor->isAdmin()) {
            // $role = Role::findByName($request->role);
            // $user->syncRoles($role);
            $user->syncRoles([$request->role]);
            $title = "User assigned role";
            $description = ucwords($user->name) . " was assigned the role of " . $request->role . " by $actor->name ($actor->email)";
            $this->logUserActivity($title, $description);
            return new UserResource($user);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $actor = $this->getUser();
        if (!$actor->hasPermission('create-users')) {
            return response()->json(['error' => 'Permission denied'], 403);
        }
        $rules = [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'username' => 'required|string|unique:users',
            'phone' => 'required|string|unique:users',
            'email' => 'required|string|unique:users',
            // 'password' => 'required|string',
            // 'confirmPassword' => 'required|same:password'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $user = new User([
                'first_name'  => $request->first_name,
                'last_name'  => $request->last_name,
                'name'  => $request->first_name . ' ' . $request->last_name,
                'username' => $request->username,
                'phone'  => $request->phone,
                'email' => $request->email,
                'user_type' => 'staff',
                'password' => bcrypt('password'), // default password
            ]);

            if ($user->save()) {
                // $role = Role::where('name', $request->role)->first();
                $user->syncRoles([$request->role]);
                $user->flushCache();
                // $user->attachRole($request->role);


                $title = "New Registration";
                $description = ucwords($user->name) . " was newly registered by $actor->name ($actor->email)";
                $this->logUserActivity($title, $description);

                return new UserResource($user);
            } else {
                return response()->json(['error' => 'Error trying to save user']);
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }
        // if ($user->isSuperAdmin()) {
        //     return response()->json(['error' => 'Admin can not be modified'], 403);
        // }

        $currentUser = Auth::user();
        if (
            (!$currentUser->isSuperAdmin() || !$currentUser->isAdmin())

            && $currentUser->id !== $user->id
            && !$currentUser->hasPermission('update-users')
        ) {
            return response()->json(['error' => 'Permission denied'], 403);
        }

        $validator = Validator::make($request->all(), $this->getValidationRules(false));
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $email = $request->get('email');
            $phone = $request->get('phone');
            $found = User::where('email', $email)->first();
            if ($found && $found->id !== $user->id) {
                return response()->json(['error' => 'Email has been taken'], 403);
            }

            $user->first_name = $request->get('first_name');
            $user->last_name = $request->get('last_name');
            $user->name = $user->first_name . ' ' . $user->last_name;
            $user->email = $email;
            $user->phone = $phone;
            $user->save();

            $actor = $this->getUser();
            $title = "Profile Update";
            $description = ucwords($user->name) . "'s information was modified by $actor->name ($actor->email)";
            $this->logUserActivity($title, $description);
            return new UserResource($user);
        }
    }

    public function randomCodeGenerator()
    {
        $tokens = 'abcdefghABCDEFGH0123456789'; //'ABCDEF0123456789';
        $serial = '';
        for ($i = 0; $i < 6; $i++) {
            $serial .= $tokens[mt_rand(0, strlen($tokens) - 1)];
        }
        return $serial;
    }
    public function adminResetUserPassword(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if (
            !$currentUser->isSuperAdmin()
            && !$currentUser->hasPermission('update-users')
        ) {
            return response()->json(['error' => 'Permission denied'], 403);
        }
        $new_password = $this->randomCodeGenerator();
        $user->password = Hash::make($new_password);
        $user->password_status = 'default';
        $user->save();

        $actor = $currentUser;
        $title = "Password Reset";
        $description = ucwords($user->name) . "'s password was reset by $actor->name ($actor->email)";
        $this->logUserActivity($title, $description);
        return response()->json(['new_password' => $new_password], 200);
    }
    public function updatePassword(Request $request, User $user)
    {
        if ($user === null) {
            return response()->json(['error' => 'User not found'], 404);
        }
        // if ($user->isAdmin()) {
        //     return response()->json(['error' => 'Admin can not be modified'], 403);
        // }

        $currentUser = Auth::user();
        if (
            !$currentUser->isSuperAdmin()
            && $currentUser->id !== $user->id
            && !$currentUser->hasPermission('update-users')
        ) {
            return response()->json(['error' => 'Permission denied'], 403);
        }
        $validator = Validator::make(
            $request->all(),
            array_merge(
                $this->getValidationRules(false),
                [
                    'password' => ['required', 'min:6'],
                    'confirmPassword' => 'same:password',
                ]
            )
        );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();
            $user->password = Hash::make($params['password']);
            $user->password_status = 'custom';
            $user->save();

            $actor = $this->getUser();
            $title = "Password updated";
            $description = ucwords($user->name) . "'s password was updated by $actor->name ($actor->email)";
            $this->logUserActivity($title, $description);
            return new UserResource($user);
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        if ($user->isSuperAdmin()) {
            response()->json(['error' => 'Ehhh! Can not delete super user'], 403);
        }

        try {
            $actor = $this->getUser();
            $title = "User Deleted";
            $description = ucwords($user->name) . " was deleted by " . $actor->name;
            $this->logUserActivity($title, $description);
            $user->delete();
        } catch (\Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 403);
        }

        return response()->json(null, 204);
    }
    /**
     * @param bool $isNew
     * @return array
     */
    private function getValidationRules($isNew = true)
    {
        return [
            'name' => 'required',
            'email' => $isNew ? 'required|email|unique:users' : 'required|email',
            'roles' => [
                'required',
                'array'
            ],
        ];
    }

    public function setCurrentLocation(Request $request)
    {
        $today = todayDate();
        $user = $this->getUser();
        $longitude = $request->longitude;
        $latitude = $request->latitude;
        $accuracy = $request->accuracy;
        $location = UserGeolocation::where('user_id', $user->id)
            ->where('created_at', 'LIKE', '%' . $today . '%')
            ->where(['longitude' => $longitude, 'latitude' => $latitude])->first();
        if (!$location) {
            $location = new UserGeolocation();
            $location->user_id = $user->id;
            $location->longitude = $longitude;
            $location->latitude = $latitude;
            $location->accuracy = $accuracy;
            $location->save();
        }
        $locations = UserGeolocation::where('user_id', $user->id)
            ->where('created_at', 'LIKE', '%' . $today . '%')->get();
        return response()->json(compact('locations'), 200);
    }

    public function showLocationTrails()
    {
        $today = todayDate();
        $user = $this->getUser();
        $locations = UserGeolocation::where('user_id', $user->id)
            ->where('created_at', 'LIKE', '%' . $today . '%')->get();
        return response()->json(compact('locations'), 200);
    }
}
