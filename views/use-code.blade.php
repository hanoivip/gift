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
	@if (isset($servers))
		<p>Chọn máy chủ:</p>
    	<select id="svname" name="svname"
    		onchange="document.location.href='' + this.value">
    		@foreach ($servers as $sv)
    			@if (isset($selected) && $sv->name == $selected)
    				<option value="{{ $sv->name }}" selected>{{ $sv->title }}</option>
    			@else
    				<option value="{{ $sv->name }}">{{ $sv->title }}</option>
    			@endif
    		@endforeach
    	</select>
    	@if (!empty($roles))
            <p>Chọn nhân vật:</p>
            <select id="roleid" name="roleid">
            	@foreach ($roles as $roleid => $rolename)
            		<option value="{{ $roleid }}">{{ $rolename }}</option>
            	@endforeach
            </select>
        @else
        	<p>Chưa có nhân vật nào trong sv này!</p>
        @endif
	@endif
<button type="submit">Dùng</button>
</form>

@endsection
