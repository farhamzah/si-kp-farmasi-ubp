@extends('layouts.app')
@section('title', 'Edit Persyaratan Dokumen - '.config('app.name'))
@section('page_title', 'Edit Persyaratan Dokumen')
@section('content')<form method="POST" action="{{ route('management.document-requirements.update', $requirement) }}">@include('management.document-requirements._form')</form>@endsection
