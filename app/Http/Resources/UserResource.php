<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $currentUser = Auth::user();
        $can_edit = false;
        if ($this->id === $currentUser->id || $currentUser->hasRole('super') || $currentUser->hasRole('admin')) {
            $can_edit = true;
        }

        $xml = simplexml_load_file("apk/app_version.xml") or die("Error: Cannot create object");

        // $roles = array_map(
        //             function ($role) {
        //                 return $role['name'];
        //             },
        //             $this->roles->toArray()
        //         );
        // $permissions = array_map(
        //     function ($permission) {
        //         return $permission['name'];
        //     },
        //     $this->allPermissions()->toArray()
        // );
        // $rights = array_merge($roles, $permissions);
        return [
            'version' => $xml->version[0],
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'first_name' => $this->first_name,
            'last_name' =>  $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'username' => $this->username,
            'last_login' => $this->last_login,
            'product_type' => $this->product_type,
            // 'address' => $this->staff->address,
            'notifications' => [],
            // 'activity_logs' => $this->notifications()->orderBy('created_at', 'DESC')->get(),
            'roles' => array_map(
                function ($role) {
                    return $role['name'];
                },
                $this->roles->toArray()
            ),
            // 'role' => 'admin',
            'permissions' => array_map(
                function ($permission) {
                    return $permission['name'];
                },
                $this->allPermissions()->toArray()
            ),
            'avatar' => '/' . $this->photo, //'https://i.pravatar.cc',
            'can_edit' => $can_edit,
            'p_status' => $this->password_status,
            'disable_debtors_creation' => false,
            'add_initial_stock' => ($this->add_initial_stock === 1) ? true : false,
            'add_initial_debtors' => ($this->add_initial_debtors === 1) ? true : false,
        ];
    }
}
