@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">autorizado</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    iniciaste sesión
                </div>
            </div>
        </div>
        <div class="col-auto d-lg-block">
            <img width="100%" height="250" class="rounded float-start" src="{{ asset('img/cfe_icon.svg') }}">
        </div>
    </div>
</div>
@endsection
