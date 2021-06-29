<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    public function list()
    {

        $users = User::orderBy('id', 'desc')
            ->Paginate(10);

        return view('admin.user.list', compact('users'));
    }

    public function create(User $User = null)
    {
        $roles = [];
        foreach(Role::all()->toArray() as $role){
            $roles[] = [
                'value' => $role['id'],
                'name' => $role['name'],
            ];
        }
        $user_roles = [];
        if($User){
            foreach($User->roles as $role){
                $user_roles[] = $role->id;
            }
        }
        return view('admin.user.add', ['user' => $User, 'roles' => $roles, 'user_roles' => $user_roles]);
    }

    public function store(Request $request, User $User = null)
    {
        if($User){
            $password = ['confirmed'];
        }else{
            $User = new User();
            $password = ['required', 'confirmed'];
        }

        $request->validate([
            'name' => ['required'],
            'email' => ['email','required','unique:users,email,'.$User->id],
            'password' => $password,
            'roles' => ['array']
        ]);

        $User->name = $request->name;
        $User->email = $request->email;
        if($request->password){
            $User->password = Hash::make($request->password);
        }
        if(Auth::user() != $User){
            if($request->active){
               if(!$User->email_verified_at){
                $User->email_verified_at = now();
               }
            }else{
                $User->email_verified_at = null;
            }
        }

        $User->save();

        if($request->roles){
            $User->roles()->detach();
            foreach($request->roles as $role_id){
                $role = Role::find($role_id);
                $User->roles()->attach($role);
            }
            $User->save();
        }

        return redirect()->route('admin_user_list')->with('flash_message', __('admin.Changes have been saved correctly'));
    }

    public function remove(User $User)
    {
        $User->delete();

        return back()->with('flash_message', __('admin.Successfully deleted'));
    }

}
