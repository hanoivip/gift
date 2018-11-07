@extends('hanoivip::admin.layouts.admin')

@section('title', 'Create new code template')

@section('content')

<form method="POST" action="{{route('gift.package.create')}}">
{{ csrf_field() }}
	Định danh mẫu: <input id="pack_code" name="pack_code" value="{{ old('pack_code') }}" />
	@if ($errors->has('pack_code'))
        <span class="help-block">
            <strong>{{ $errors->first('pack_code') }}</strong>
        </span>
    @endif
                                
	Tên: <input id="name" name="name" value="{{ old('name') }}" />
	@if ($errors->has('name'))
        <span class="help-block">
            <strong>{{ $errors->first('name') }}</strong>
        </span>
    @endif

	Số lượng (0-unlimited): <input id="limit" name="limit" value="0" />
	Tiền tố: <input id="prefix" name="prefix" value="{{ old('prefix') }}" />
	Thời gian có hiệu lực: <input id="start_time" name="start_time" value="{{ old('start_time') }}" />
	@if ($errors->has('start_time'))
        <span class="help-block">
            <strong>{{ $errors->first('start_time') }}</strong>
        </span>
    @endif

	Thời gian kết thúc: <input id="end_time" name="end_time" value="{{ old('end_time') }}" />
	@if ($errors->has('end_time'))
        <span class="help-block">
            <strong>{{ $errors->first('end_time') }}</strong>
        </span>
    @endif

	Phần thưởng: <input id="rewards" name="rewards" value="{{ old('rewards') }}" />
	@if ($errors->has('rewards'))
        <span class="help-block">
            <strong>{{ $errors->first('rewards') }}</strong>
        </span>
    @endif
    
    Code hằng số: <input id="const_code" name="const_code" value="0" /><br/>
    Ds server đc sử dụng: <input id="server_include" name="server_include" value="[]" /><br/>
    Ds server ko đc sử dụng: <input id="server_exclude" name="server_exclude" value="[]" /><br/>
	Tiếp thị liên kết: <input id="allow_users" name="allow_users" value="0" /><br/>
	<button type="submit">Create</button>
</form>

@endsection
