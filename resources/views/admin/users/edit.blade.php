@extends('layouts.app')

@section('title', 'Edit User - '.config('app.name'))
@section('page_title', 'Edit User')

@section('content')
<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @include('admin.users._form')
</form>
@endsection
