{{--
    Unified Comment Component
    Used in both the detail page and matches the modal style.

    @param $comment - Comment model with user relation
    @param $depth - Nesting depth (0 = top level)
--}}

@php
    $authorName = $comment->user->first_name ?? $comment->user->name ?? 'Anonymous';
    $authorInitial = strtoupper(substr($authorName, 0, 1));
    $reputation = $comment->user->reputation_score ?? 0;
    $isReply = $depth > 0;
    $maxDepth = 2;

    // Format date
    $dateStr = '';
    if ($comment->created_at) {
        $date = $comment->created_at;
        $now = now();
        $diff = $now->diff($date);

        if ($diff->days == 0) {
            if ($diff->h == 0) {
                $dateStr = $diff->i . 'm ago';
            } else {
                $dateStr = $diff->h . 'h ago';
            }
        } elseif ($diff->days == 1) {
            $dateStr = '1d ago';
        } elseif ($diff->days < 7) {
            $dateStr = $diff->days . 'd ago';
        } elseif ($diff->days < 30) {
            $dateStr = floor($diff->days / 7) . 'w ago';
        } else {
            $dateStr = $date->format('M j');
        }
    }
@endphp

<div class="modal-comment {{ $isReply ? 'modal-comment--reply' : '' }}">
    <div class="modal-comment-header">
        <div class="modal-comment-avatar">{{ $authorInitial }}</div>
        <span class="modal-comment-author">{{ $authorName }}</span>
        @if($reputation > 0)
            <span class="modal-comment-reputation">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                {{ number_format($reputation) }}
            </span>
        @endif
        <span class="modal-comment-date">{{ $dateStr }}</span>
    </div>
    <div class="modal-comment-body">{{ $comment->content }}</div>
    <div class="modal-comment-actions">
        @if(!$isReply && Auth::check())
            <button class="modal-reply-btn" data-comment-id="{{ $comment->comment_id ?? $comment->id }}">Reply</button>
        @endif
    </div>

    {{-- Render replies if present --}}
    @if(isset($comment->replies) && $comment->replies->count() > 0 && $depth < $maxDepth)
        <div class="modal-replies">
            @foreach($comment->replies as $reply)
                @include('predictions.partials.comment-unified', ['comment' => $reply, 'depth' => $depth + 1])
            @endforeach
            @if($comment->replies->count() > 3 && $depth === 0)
                <div class="modal-continue-thread">Continue thread &rarr;</div>
            @endif
        </div>
    @elseif(isset($comment->replies) && $comment->replies->count() > 0 && $depth >= $maxDepth)
        <div class="modal-continue-thread">
            View {{ $comment->replies->count() }} more {{ $comment->replies->count() === 1 ? 'reply' : 'replies' }} &rarr;
        </div>
    @endif
</div>
