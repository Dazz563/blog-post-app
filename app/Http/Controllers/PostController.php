<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendNewPostEmail;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{
    public function showCreateForm() {
        return view('create-post');
    }

    public function storeNewPostApi(Request $req) {
        $incomingFields = $req->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // strip out any malicious html a user might enter
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        // retrieves the id from the session using the global auth() function
        $incomingFields['user_id'] = auth()->id();

        // create post 
        $newPost = Post::create($incomingFields);

        // send email
        dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name' => auth()->user()->username, 'title' => $newPost->title]));

        return $newPost;
    }

    public function storeNewPost(Request $req) {
        $incomingFields = $req->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // strip out any malicious html a user might enter
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        // retrieves the id from the session using the global auth() function
        $incomingFields['user_id'] = auth()->id();

        // create post 
        $newPost = Post::create($incomingFields);

        // send email
        dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name' => auth()->user()->username, 'title' => $newPost->title]));

        return redirect("/post/{$newPost->id}")->with('success', 'Your new post was successfully created!');
    }

    /**
     * Below is an example of type hinting.
     * In the function parameter we include the model (Post $postId) and match the parameter value 
     * to the route name ($postId) is the route paramter name and the functions parameter name.
     * Laravel will automatically look at it's value and query the DB for a matching post and return it.
     */
    public function showSinglePost(Post $post)
    {
        // using Laravels built in Str class (import it) to add markdown
        // overide the body property on the post to the markdown
        $post['body'] = Str::markdown($post->body);
        
        return view('single-post', ['post' => $post]);
    }

    public function deleteApi(Post $post)
    {
        $post->delete();

        return 'true';
        
    }

    public function delete(Post $post)
    {
        // the policy was moved to the route
        // if(auth()->user()->cannot('delete', $post)) {
        //     return 'You cannot do that!';
        // }
        $post->delete();

        return redirect('/profile/' . auth()->user()->username)->with('success', 'Post successfully deleted!');
        
    }

    public function showEditForm(Post $post)
    {
        return view('edit-post', ['post' => $post]);
    }

    public function updatePost(Post $post, Request $req)
    {
        $incomingFields = $req->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // strip out any malicious html a user might enter
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        $post->update($incomingFields);

        return back()->with('success', 'Post successfully updated!');
    }

    public function search($term)
    {
        // Belows code is SQL specific below this we will do a more laravel proper approach.
        // return Post::where('title', 'LIKE', '%' . $term . '%')->orWhere('body', 'LIKE', '%' . $term . '%')->with('user:id,username,avatar')->get();

        // We are going to use scout - composer require laravel/scout THEN php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
        $posts = Post::search($term)->get();
        $posts->load('getUser:id,username,avatar');
        return $posts;
    }

    
}
