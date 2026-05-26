@extends('layouts.app')
@section('content')
<style>
    html, body {
        margin: 0;
        padding: 0;
        background: linear-gradient(-45deg, #1976d2, #1565c0, #0d47a1, #3490dc);
        background-size: 400% 400%;
        animation: gradientBG 20s ease infinite;
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .authorize-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 3rem);
        padding: 20px;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        padding: 48px 40px;
        width: 100%;
        max-width: 480px;
        color: #e2e8f0;
    }

    .glass-card h2 {
        font-size: 18px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 16px;
        color: #ffffff;
    }

    .glass-card p {
        font-size: 14px;
        line-height: 1.5;
        margin: 0 0 16px;
    }

    .glass-card strong {
        color: #FFED00;
    }

    .scopes {
        margin: 16px 0;
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .scopes ul {
        margin: 8px 0 0;
        padding-left: 20px;
        font-size: 13px;
    }

    .buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .buttons form {
        flex: 1;
        margin: 0;
    }

    .btn {
        width: 100%;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 700;
        padding: 14px;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: transform 0.2s ease;
    }

    .btn:hover {
        transform: scale(1.02);
    }

    .btn-approve {
        background: #FFED00;
        color: #1565c0;
    }

    .btn-deny {
        background: rgba(229, 0, 0, 0.85);
        color: #ffffff;
    }
</style>

<div class="authorize-container">
    <div class="glass-card">
        <h2>Autorização</h2>

        <p><strong>{{ $client->name }}</strong> está solicitando permissão para acessar sua conta.</p>

        @if (count($scopes) > 0)
            <div class="scopes">
                <p style="margin:0;font-weight:600;">Esta aplicação poderá:</p>
                <ul>
                    @foreach ($scopes as $scope)
                        <li>{{ $scope->description }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="buttons">
            <form method="post" action="{{ route('passport.authorizations.approve') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-approve">Autorizar</button>
            </form>

            <form method="post" action="{{ route('passport.authorizations.deny') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="state" value="{{ $request->state }}">
                <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-deny">Cancelar</button>
            </form>
        </div>
    </div>
</div>
@endsection
