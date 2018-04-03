@extends('hanoivip::admin.layouts.admin')

@section('title', 'Create new code template')

@section('content')

<form method="POST" action="{{route('gift.package.create')}}">
{{ csrf_field() }}
	Định danh mẫu: <input id="pack_code" name="pack_code" value="" />
	Tên: <input id="name" name="name" value="" />
	Số lượng (0-unlimited): <input id="limit" name="limit" value="0" />
	Tiền tố: <input id="prefix" name="prefix" value="" />
	Thời gian có hiệu lực: <input id="start_time" name="start_time" value="" />
	Thời gian kết thúc: <input id="end_time" name="end_time" value="" />
	Phần thưởng: <input id="rewards" name="rewards" value="" />
	Tiếp thị liên kết: <input id="allow_users" name="allow_users" value="" />
	<button type="submit">Create</button>
</form>

@endsection
