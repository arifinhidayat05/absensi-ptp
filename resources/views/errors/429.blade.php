<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Terlalu Banyak Permintaan | Pengadilan Tinggi Pontianak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 font-['Plus_Jakarta_Sans'] text-slate-100">
    <div class="max-w-md w-full bg-slate-800/90 border-2 border-amber-500/50 rounded-3xl p-8 shadow-2xl text-center backdrop-blur-md relative overflow-hidden">
        <div class="absolute -right-12 -top-12 w-36 h-36 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="w-20 h-20 mx-auto rounded-3xl bg-amber-500/10 border-2 border-amber-500/30 flex items-center justify-center text-amber-400 text-3xl mb-5 shadow-lg">
            <i class="fa-solid fa-gauge-high"></i>
        </div>

        <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-300 border border-amber-500/30">
            HTTP 429 &bull; Rate Limit Exceeded
        </span>

        <h1 class="text-xl font-black text-white mt-4">Terlalu Banyak Permintaan</h1>
        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
            {{ $message ?? 'Alamat IP Anda mengirimkan terlalu banyak permintaan dalam waktu singkat. Sistem membatasi akses sementara untuk mencegah serangan DoS (Denial of Service) dan menjaga kestabilan server.' }}
        </p>

        <div class="mt-6 p-4 rounded-2xl bg-slate-950/60 border border-slate-700/60 text-left text-[11px] space-y-1.5 font-mono text-slate-400">
            <div><strong class="text-slate-300">Status:</strong> <span class="text-amber-400 font-bold">429 Too Many Requests</span></div>
            <div><strong class="text-slate-300">Proteksi:</strong> Anti-DoS / Rate Limiter Active</div>
            <div><strong class="text-slate-300">Waktu:</strong> {{ date('Y-m-d H:i:s') }} WIB</div>
        </div>

        <div class="mt-6 flex flex-col gap-2">
            <button onclick="window.location.reload()"
                class="w-full py-3 px-4 bg-[#064e3b] hover:bg-[#043d2e] text-white font-bold rounded-xl text-xs transition border border-emerald-600 shadow-md flex items-center justify-center gap-2 cursor-pointer">
                <i class="fa-solid fa-rotate-right"></i> Coba Muat Ulang Halaman
            </button>
            <a href="{{ url('/') }}"
                class="w-full py-2.5 px-4 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold rounded-xl text-xs transition">
                Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</body>
</html>
