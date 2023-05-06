@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}">
                        <div class="form-group row">
                            <div class="col-md-12 text-center">
                                <h2 class="font-weight-bold">Identificação MG Papelaria</h2>
                            </div>
                        </div>
                        @csrf
                        <div class="form-group row">
                            <label for="usuario" class="col-md-4 col-form-label text-md-right">{{ __('Usuário') }}</label>
                            <div class="col-md-6">
                                <input id="usuario" type="usuario" class="form-control" name="usuario">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="senha" class="col-md-4 col-form-label text-md-right">{{ __('Senha') }}</label>
                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password">
                                @if($errors->any())
                                @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="form-group row">
                            </div>
                        </div>
                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
        </div>
    </div>
</div>
@endsection
