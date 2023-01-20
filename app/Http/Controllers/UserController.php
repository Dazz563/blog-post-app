<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use App\Events\OurExampleEvent;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function loginApi(Request $req) {
        // validating incoming request
        $incomingFields = $req->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // auth is a global method available
        if(auth()->attempt($incomingFields)) {
            $user = User::where('username', $incomingFields['username'])->first();
            $token = $user->createToken('ourapptoken')->plainTextToken;
            return $token;
        }

        return 'sorry you suck!';
    }

    public function login(Request $req) {
        // validating incoming request
        $incomingFields = $req->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required',
        ]);

        // auth is a global method available
        if(auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            $req->session()->regenerate();
            event(new OurExampleEvent(['username' => auth()->user()->username, 'action' => 'login']));

            return redirect('/')->with('success', 'You have successfully logged in!');
        } else {
            return redirect('/')->with('failure', 'Invalid login!');
        }
    }

    public function register(Request $req) {
        // validating incoming request
        $incomingFields = $req->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:4', 'confirmed'],
        ]);

        // bcrypt is a globallly available method we can assign to the password.
        $incomingFields['password'] = bcrypt($incomingFields['password']);

        // creating new user in DB
        $user = User::create($incomingFields);
        // logging the new user in (will pass the cookie session to the browser)
        auth()->login($user);

        return redirect('/')->with('success', 'Thank you for creating an account!');
    }

    public function showCorrectHomepage()
    {
        if(auth()->check()) {
            return view('homepage-feed', ['posts' => auth()->user()->feedPosts()->latest()->paginate(3)]);
        } else {
            $postCount = Cache::remember('postCount', 20, function() {
                return Post::count();
            });
            return view('homepage', ['postCount' => Post::count()]);
        }
    }

    public function logout()
    {
        event(new OurExampleEvent(['username' => auth()->user()->username, 'action' => 'logout']));
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out!');
    }

    private function getSharedData($user)
    {
        $currentlyFollowing = 0;

        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followed_user', '=', $user->id]])->count();
        }

        View::share('sharedData', ['currentlyFollowing' => $currentlyFollowing, 'avatar' => $user->avatar, 'username' => $user->username, 'postCount' => $user->getPosts()->count(), 'followerCount' => $user->followers()->count(), 'followingCount' => $user->followingTheseUsers()->count()]);
    }

    public function profile(User $user) {
        $this->getSharedData($user);
        return view('profile-posts', ['posts' => $user->getPosts()->latest()->get()]);
    }

    public function profileRaw(User $user) {
        return response()->json(['theHTML' => view('profile-posts-only', ['posts' => $user->getPosts()->latest()->get()])->render(), 'docTitle' => $user->username . "'s Profile"]);
    }

    public function profileFollowers(User $user) {
        $this->getSharedData($user);
        return view('profile-followers', ['followers' => $user->followers()->latest()->get()]);

    }

    public function profileFollowersRaw(User $user) {
        return response()->json(['theHTML' => view('profile-followers-only', ['followers' => $user->followers()->latest()->get()])->render(), 'docTitle' => $user->username . "'s Followers"]);
    }

    public function profileFollowing(User $user) {
        $this->getSharedData($user);
        return view('profile-following', ['following' => $user->followingTheseUsers()->latest()->get()]);
    }

    public function profileFollowingRaw(User $user) {
        return response()->json(['theHTML' => view('profile-following-only', ['following' => $user->followingTheseUsers()->latest()->get()])->render(), 'docTitle' => 'Who ' . $user->username . "'s Follows"]);

    }

    public function showAvatarForm()
    {
        return view('avatar-form');
    }

    public function storeAvatar(Request $req)
    {
        $req->validate([
            'avatar' => 'required|image|max:3000'
        ]);

        // fetch the user.
        $user = auth()->user();
        // attach id, generate unique id, append .jpg.
        $filename = $user->id . '-' . uniqid() . '.jpg';
        // use 'composer require intervention/image' package to remake the image to our spec.
        $imgData = Image::make($req->file('avatar'))->fit(120)->encode('jpg');
        // store the image in our linked storage folder 'php artisan storage:link'
        Storage::put('public/avatars/' . $filename, $imgData);

        // grabbing the old image for deletion
        $oldAvatar = $user->avatar;
        // update the users avatar in the DB
        $user->avatar = $filename;
        $user->save();

        /**
         * Deleting the old image after successful save.
         * $oldImage = /storage/avatars/1234.jpg
         * we must delete = public/avatars/1234.jpg
         * the solution is to use str_replace()
         */
        // 
        if($oldAvatar != '/fallback-avatar.jpg') {
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar));
        }

        return back()->with('success', 'Your avatar was updated');
    }
}
