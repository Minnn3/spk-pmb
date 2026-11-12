@extends('layouts.guest')

@section('title', 'SPK PMB')

@section('content')
    <div class="auth-card text-center">
        <h1 class="text-[22px] font-bold tracking-[-0.01em] text-[#1A1A1A]">SPK PMB</h1>
        <p class="text-[13px] text-[#525252] mt-2 mb-6">
            Sistem Pendukung Keputusan Perencanaan Strategi Promosi dan Alokasi Staf PMB
        </p>
        <a href="{{ route('dashboard.index') }}" class="btn btn-primary btn-full">
            <span>Buka Dashboard</span>
        </a>
    </div>
@endsection
