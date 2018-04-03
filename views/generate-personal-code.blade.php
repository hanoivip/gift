@extends('hanoivip::layouts.app')

@section('title', 'Người chơi sinh mã')

@section('content')


@if (!empty($message))
<p> {{ $message }} </p>
@endif
@if (!empty($error_message))
<p> {{ $error_message }} </p>
@endif
@if (!empty($code))
<p> {{ $code }} </p>
@endif

<form method="POST" action="{{ route('gift.generate') }}">
	{{ csrf_field() }}

<button type="submit">Dùng</button>
</form>

@endsection
