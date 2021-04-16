<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $user;
    public function uploadFile(Request $request)
    {
        if ($request->file('avatar') != null && $request->file('avatar')->isValid()) {
            $mime = $request->file('avatar')->getClientMimeType();

            if ($mime == 'image/png' || $mime == 'image/jpeg' || $mime == 'image/jpg' || $mime == 'image/gif') {
                $name = time() . "." . $request->file('avatar')->guessClientExtension();
                $folder = "items";
                $avatar = $request->file('avatar')->storeAs($folder, $name, 'public');

                return response()->json(['avatar' => 'storage/' . $avatar], 200);
            }
        }
    }

    public function setUser()
    {
        $this->user  = new UserResource(Auth::user());
    }

    public function getUser()
    {
        $this->setUser();

        return $this->user;
    }
    public function currency()
    {
        return '₦';
    }
}
