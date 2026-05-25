@extends('layouts.app')
@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap');

    /* Premium Animated Gradient Background - System Blue */
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

    /* Full height centering container */
    .login-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 3rem);
        padding: 20px;
    }

    /* Dark Glassmorphism Card */
    .glass-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border-radius: 12px; /* Alinhado ao modal do sistema */
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        padding: 48px 40px;
        width: 100%;
        max-width: 420px;
        text-align: center;
        transition: transform 0.3s ease;
    }

    .form-group {
        text-align: left;
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #e2e8f0;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Premium Dark Inputs */
    .modern-input {
        width: 100%;
        border-radius: 8px; /* Alinhado aos inputs do Quasar */
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 16px 20px;
        font-size: 16px;
        background: rgba(0, 0, 0, 0.2);
        color: #ffffff;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        outline: none;
    }

    .modern-input:focus {
        background: rgba(0, 0, 0, 0.4);
        border-color: #FFED00;
        box-shadow: 0 0 0 4px rgba(255, 237, 0, 0.15);
        transform: translateY(-2px);
    }

    .modern-input::placeholder {
        color: #64748b;
    }

    /* Premium "Wow" Button */
    .modern-btn {
        width: 100%;
        background: #FFED00;
        border: none;
        border-radius: 8px; /* Alinhado aos inputs do Quasar */
        color: #1565c0; /* System blue */
        font-size: 18px;
        font-weight: 800;
        padding: 18px;
        margin-top: 16px;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 10px 25px -5px rgba(255, 237, 0, 0.4);
    }

    .modern-btn:hover {
        transform: scale(1.05);
        /*
        box-shadow: 0 20px 35px -5px rgba(255, 237, 0, 0.5);
        background: #fff566;
        */
    }

    .modern-btn:active {
        transform: translateY(0) scale(0.98);
    }

    /* Elegant Error Badge */
    .error-badge {
        display: flex;
        align-items: center;
        background: #E50000; /* Vermelho forte da marca */
        color: #ffffff; /* Texto branco puro */
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        margin-top: 12px;
        width: 100%;
        box-shadow: 0 4px 15px rgba(229, 0, 0, 0.4);
    }

    .error-badge svg {
        width: 20px;
        height: 20px;
        margin-right: 8px;
        flex-shrink: 0;
        fill: currentColor;
    }

    /* Logo Presentation */
    .logo-container {
        margin-bottom: 40px;
        display: flex;
        justify-content: center;
    }

    .logo-container img {
        width: 100%;
        max-width: 140px;
        height: auto;
        border-radius: 8px; /* Reduzido */
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .logo-container img:hover {
        transform: scale(1.8) rotate(-10deg);
    }
</style>

<div class="login-container">
    <div class="glass-card">
        <div class="logo-container">
            <img src="{{ asset('MG Papelaria Selo.svg') }}" alt="MG Papelaria Selo">
        </div>

        <form method="POST" action="{{ route('auth') }}">
            @csrf
            <input type="text" name="redirect_uri" value="{{ $redirect_uri ?? '' }}" hidden>

            <div class="form-group">
                <label for="username">{{ __('Usuário') }}</label>
                <input id="username" class="modern-input" type="text" name="username" placeholder="Digite seu usuário" autofocus required>
            </div>

            <div class="form-group">
                <label for="password">{{ __('Senha') }}</label>
                <input id="password" type="password" class="modern-input" name="password" placeholder="Digite sua senha" required>

                @if (!empty($error))
                    <div class="error-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 15h2v2h-2zm0-8h2v6h-2zm.99-5C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/></svg>
                        Usuário ou senha inválidos
                    </div>
                @endif
            </div>

            <button type="submit" class="modern-btn">
                {{ __('Entrar') }}
            </button>
        </form>
    </div>
</div>
@endsection
