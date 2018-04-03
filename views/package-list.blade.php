@extends('hanoivip::admin.layouts.admin')

@section('title', 'List of code templates')

@section('content')

@foreach ($packages as $pkg)
{{ print_r($pkg) }}

<form method="POST" action="{{ route('gift.package.delete') }}">
	{{ csrf_field() }}
	<input type="hidden" name="_method" value="delete" />
	<input id="code" name="code" type="hidden" value="{{$pkg['pack_code']}}">
	<button type="submit">Del</button>
</form>

<form method="GET" action="{{ route('gift.package.view') }}">
	<input id="code" name="code" type="hidden" value="{{$pkg['pack_code']}}">
	<button type="submit">View</button>
</form>

@endforeach

<form method="GET" action="{{ route('gift.package.new') }}">
	<button type="submit">New template</button>
</form>

@endsection
