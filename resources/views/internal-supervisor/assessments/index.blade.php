@extends('layouts.app')
@section('title','Penilaian Pembimbing - '.config('app.name'))
@section('page_title','Penilaian Pembimbing')
@section('content')
@include('shared.assessments.assignment-list', ['assignments' => $assignments, 'title' => 'Mahasiswa Bimbingan', 'routeName' => 'internal-supervisor.assessments.show'])
@endsection
