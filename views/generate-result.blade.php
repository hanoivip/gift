@extends('hanoivip::admin.layouts.admin')

@section('title', 'Kết quả tạo mã')

@section('content')

@if (!empty($message))
<p> {{ $message }} </p>
@endif

@if (!empty($error_message))
<p> Lỗi xảy ra: {{ $error_message }} </p>
@endif

@if (!empty($codes))
{{ print_r($codes) }}
@endif

@endsection
