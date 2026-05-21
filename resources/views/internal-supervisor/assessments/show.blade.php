@extends('layouts.app')
@section('title','Input Nilai Pembimbing - '.config('app.name'))
@section('page_title','Input Nilai Pembimbing')
@section('content')
@include('shared.assessments.score-form', ['assignment' => $assignment, 'components' => $components, 'saveRoute' => route('internal-supervisor.assessments.save',$assignment), 'submitRoute' => route('internal-supervisor.assessments.submit',$assignment)])
@endsection
