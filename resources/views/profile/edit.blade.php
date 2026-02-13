@extends('layouts.app')

@section('title', 'Profil Ayarları')

@section('header')
    <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Profil Ayarları') }}
    </h2>
@endsection

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <div class="max-w-xl">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <i class="fas fa-user-edit text-indigo-400"></i> Profil Bilgileri
            </h3>
            <p class="text-sm text-slate-400 mb-6">Hesap bilgilerinizi ve e-posta adresinizi güncelleyin.</p>
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <div class="max-w-xl">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <i class="fas fa-key text-purple-400"></i> Şifre Değiştir
            </h3>
            <p class="text-sm text-slate-400 mb-6">Güvenliğiniz için uzun ve rastgele bir şifre kullanın.</p>
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="p-8 bg-red-950/20 border border-red-900/50 shadow-2xl rounded-3xl">
        <div class="max-w-xl">
            <h3 class="text-lg font-bold text-red-400 mb-2 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Hesabı Sil
            </h3>
            <p class="text-sm text-slate-400 mb-6">Hesabınız silindiğinde tüm verileriniz kalıcı olarak kaldırılacaktır.</p>
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</div>

<style>
    /* Input kutularını karanlık temaya ve metin hizalamasına göre iyileştiriyoruz */
    input[type="text"], input[type="email"], input[type="password"] {
        background-color: #020617 !important; /* Daha derin bir siyah */
        border-color: #334155 !important;    /* Belirgin bir gri sınır */
        color: white !important;
        border-radius: 0.75rem !important;

        /* İŞTE KRİTİK NOKTA: Yazıyı duvardan kurtaran iç boşluk (Padding) */
        padding-left: 1.25rem !important;
        padding-right: 1.25rem !important;
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;

        width: 100% !important;
        transition: all 0.2s ease-in-out;
    }

    /* Tıklandığında (Focus) şık bir parlama efekti */
    input:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
        outline: none !important;
    }

    /* Etiketlerin (Label) rengini ve konumunu düzeltiyoruz */
    label {
        color: #94a3b8 !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        tracking: 0.05em !important;
        margin-bottom: 0.5rem !important;
        display: block;
    }

    /* "SAVE" Butonunu projenin geri kalanıyla uyumlu yapalım */
    button[type="submit"] {
        background-color: #4f46e5 !important; /* Indigo-600 */
        color: white !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 0.75rem !important;
        transition: all 0.2s;
    }
    button[type="submit"]:hover {
        background-color: #4338ca !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
    }
</style>
@endsection
