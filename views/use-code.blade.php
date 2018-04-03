@extends('hanoivip::layouts.app')

@section('title', 'Sử dụng mã quà tặng')

@section('content')

@if (!empty($message))
<p> {{ $message }} </p>
@endif
@if (!empty($error_message))
<p> {{ $error_message }} </p>
@endif

<form method="POST" action="{{ route('gift.use') }}">
	{{ csrf_field() }}
Nhập mã quà tặng: <input id="code" name="code" type="text" />
<button type="submit">Dùng</button>
</form>

@endsection
