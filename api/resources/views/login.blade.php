@extends('layouts.app')
@section('content')
    <div class="align-middle" style="height: 95dvh; align-items: center; justify-content:center; display:flex">
        <div style="max-width:300px">
            <form method="POST" action="{{ route('auth') }}">
                @csrf
                <input type="text" name="redirect_uri" value="{{ $redirect_uri }}" hidden>
                <div class="form-group">
                    <label for="usuario">{{ __('Usuário') }}</label>
                    <input id="username" class="form-control" type="text" name="username" autofocus>
                </div>
                <div class="form-group">
                    <label for="senha">{{ __('Senha') }}</label>
                    <input id="password" type="password" class="form-control" name="password">
                    @if (!empty($error))
                        <small id="emailHelp" class="form-text text-danger">Usuário ou senha inválidos</small>
                    @endif
                </div>
                <button type="submit" class="btn btn-warning">
                    {{ __('Login') }}
                </button>
            </form>
        </div>
    </div>
@endsection
