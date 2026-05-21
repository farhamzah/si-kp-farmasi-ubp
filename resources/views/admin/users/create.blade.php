@extends('layouts.app')

@section('title', 'Tambah User - '.config('app.name'))
@section('page_title', 'Tambah User')

@section('content')
<form method="POST" action="{{ route('admin.users.store') }}">
    @include('admin.users._form')
</form>
@endsection
