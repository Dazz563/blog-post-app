<a href="/post/{{ $post->id }}" class="list-group-item list-group-item-action">
    <img class="avatar-tiny" src="{{ $post->getUser->avatar }}" />
    <strong>{{ $post->title }}</strong> 
    <span class="text-muted small">
        @if(!isset($hideAuthor))
        by {{ $post->getUser->username }} 
        @endif
        on {{ $post->created_at->format('n/j/Y') }} 
    </span>
</a>