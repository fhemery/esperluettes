@foreach($items as $comment)
  @include('comment::components.partials.comment-item', ['comment' => $comment])
@endforeach
