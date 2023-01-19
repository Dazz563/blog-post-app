<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function createFollow(User $user)
    {
        // you cannot follow yourself
        if($user->id == auth()->user()->id) {
            return back()->with('failure', 'You cannot follow yourself');
        }
        // you cannot follow someone twice
        $existsCheck = Follow::where([['user_id', '=', auth()->user()->id], ['followed_user', '=', $user->id]])->count();
        if($existsCheck) {
            return back()->with('failure', 'You are already following that user');
        }

        // gets the user logged in (person doing the following)
        $newFollow = new Follow;
        $newFollow->user_id = auth()->user()->id;
        // get the person being followed (coming from the URL)
        $newFollow->followed_user = $user->id;
        $newFollow->save();

        return back()->with('success', 'User successfully followed');

    }

    public function removeFollow(User $user)
    {
        Follow::where([['user_id', '=', auth()->user()->id], ['followed_user', '=', $user->id]])->delete();
        return back()->with('success', 'User unfollowed');

    }
}
