@extends('layouts.app')
@section('title','Input Nilai Lapangan - '.config('app.name'))
@section('page_title','Input Nilai Lapangan')
@section('content')
@include('shared.assessments.score-form', ['assignment' => $assignment, 'components' => $components, 'saveRoute' => route('field-supervisor.assessments.save',$assignment), 'submitRoute' => route('field-supervisor.assessments.submit',$assignment)])
@endsection
