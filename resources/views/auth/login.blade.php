@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="auth-card">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-10 h-10 rounded-md border border-[#E5E4E0] bg-[#FAFAF8] text-[#1A1A1A] mb-3">
                <x-icon name="TrendingUp" :size="20" :strokeWidth="1.75" />
            </div>
            <h1 class="text-[22px] font-bold tracking-[-0.01em] text-[#1A1A1A]">SPK PMB</h1>
            <p class="text-[13px] text-[#A3A3A3] mt-1">
                Sistem Pendukung Keputusan Strategi Promosi dan Alokasi Staf PMB
            </p>
        </div>

        <form method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf

            <div class="form-group">
                <label for="email" class="form-label required">Alamat Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-input {{ $errors->has('email') ? 'error' : '' }}"
                    placeholder="admin@spkpmb.ac.id"
                    autocomplete="email"
                    required
                    autofocus
                >
                @error('email')
                    <p class="form-error-message" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label required">Kata Sandi</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                    placeholder="Masukkan kata sandi"
                    autocomplete="current-password"
                    required
                >
                @error('password')
                    <p class="form-error-message" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6">
                <button type="submit" class="btn btn-primary btn-full">
                    <x-icon name="LogIn" :size="16" :strokeWidth="2" />
                    <span>Masuk ke Sistem</span>
                </button>
            </div>
        </form>
    </div>
@endsection

