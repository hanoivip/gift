@extends('hanoivip::layouts.app')

@section('title', 'Kết quả tạo mã')

@section('content')

@if (!empty($message))
<p> {{ $message }} </p>
@endif

@if (!empty($error_message))
<p> Lỗi xảy ra: {{ $error_message }} </p>
@endif

@if (!empty($code))
<p> Mã của bạn là: {{ $code }} </p>
@endif

<form method="GET" action="{{ route('gift.generate.ui') }}">	
	<button type="submit">Tạo mã khác</button>
</form>

<form method="GET" action="{{ route('gift.history') }}">
	<button type="submit">Lịch sử</button>
</form>

@endsection
