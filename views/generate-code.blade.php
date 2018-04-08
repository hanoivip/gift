@extends('hanoivip::admin.layouts.admin')

@section('title', 'Admin sinh mã')

@section('content')

<form method="POST" action="{{ route('gift.batch-generate') }}">
	{{ csrf_field() }}
	Chọn gói/hoạt động:
	<select id="package" name="package">
	@foreach ($packages as $pkg)
		<option value="{{$pkg->pack_code}}">{{$pkg->name}}</option>
	@endforeach
	</select>
	Số lượng mã:
	<input id="count" name="count" value="20" />
	<button type="submit">Sinh mã</button>
</form>

@endsection
