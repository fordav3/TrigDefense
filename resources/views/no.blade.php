@extends('layouts.grids.12')
@section('title', 'Home')
@section('content_header')
    <h1>Welcome to TriglavDefense!</h1>
@stop
@section('full')
    @if(auth()->user())
     Unauthorized.
    @else()
        <div>Please login using ESI in the top right.</div>
    @endif

    
@stop
