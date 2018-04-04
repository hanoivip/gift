@extends('hanoivip::layouts.app')

@section('title', 'Người chơi sinh mã')

@section('content')

<form method="POST" action="{{ route('gift.generate') }}">
	{{ csrf_field() }}
	Chọn gói/hoạt động:
	<select id="package" name="package">
	@foreach ($packages as $pkgcode => $pkg)
		<option value="{{$pkgcode}}">{{$pkg}}</option>
	@endforeach
	</select>
	Tên đăng nhập người muốn tặng:
	<input id="target" name="target" value="" />
	<button type="submit">Tạo mã</button>
</form>

@endsection
