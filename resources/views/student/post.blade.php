@extends('layouts.student')
@section('title', '- '.$post->header)
@section('content')
    <a href="{{ route('student.posts') }}" class="mb-3 inline-flex items-center text-sm" style="color: var(--color-primary);"><i class="fas fa-arrow-left mr-2"></i>Alle Nachrichten</a>
    @include('student.partials.post-card', ['post' => $post, 'full' => true])
@endsection
