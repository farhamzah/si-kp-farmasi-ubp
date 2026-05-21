@extends('layouts.app')
@section('title','Penilaian Lapangan - '.config('app.name'))
@section('page_title','Penilaian Lapangan')
@section('content')
@include('shared.assessments.assignment-list', ['assignments' => $assignments, 'title' => 'Mahasiswa KP', 'routeName' => 'field-supervisor.assessments.show'])
@endsection
