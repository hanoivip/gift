@extends('hanoivip::admin.layouts.admin')

@section('title', 'Processing result')

@section('content')

@if (!empty($message))
<p>{{ $message }}</p>
@endif

@if (!empty($error_message))
<p>{{ $error_message }}</p>
@endif

<a href="{{ route('gift.package.list') }}">OK</a>

@endsection
