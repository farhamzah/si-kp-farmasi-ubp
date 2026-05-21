@extends('layouts.app')
@section('title', 'Tambah Persyaratan Dokumen - '.config('app.name'))
@section('page_title', 'Tambah Persyaratan Dokumen')
@section('content')<form method="POST" action="{{ route('management.document-requirements.store') }}">@include('management.document-requirements._form')</form>@endsection
