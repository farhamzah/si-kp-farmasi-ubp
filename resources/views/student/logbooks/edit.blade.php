@extends('layouts.app')
@section('title','Edit Logbook - '.config('app.name'))
@section('page_title','Edit Logbook')
@section('content')
<section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <form method="POST" action="{{ route('student.logbooks.update', $logbook) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('student.logbooks._form', ['logbook' => $logbook])
    </form>
</section>
@endsection
