@extends('layouts.app')
@section('title', 'Edit Tempat KP - '.config('app.name'))
@section('page_title', 'Edit Tempat KP')
@section('content')<form method="POST" action="{{ route('management.kp-places.update', $place) }}">@include('management.places._form')</form>@endsection
