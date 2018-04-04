@extends('hanoivip::layouts.app')

@section('title', 'Lịch sử sinh mã')

@section('content')

@if (!empty($error_message))
<p> {{ $error_message }} </p>
@endif

@foreach ($histories as $h)
{{ print_r($h) }}
@endforeach

@endsection
