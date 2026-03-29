<x-peserta-layout>
    <x-slot name="title">Konfirmasi Ujian — {{ $session->nama_sesi }}</x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('peserta.dashboard') }}"
                class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Dashboard
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Konfirmasi Ujian</h1>
            <p class="text-gray-500 mt-1 text-sm">Pastikan Anda siap sebelum memulai ujian.</p>
        </div>

        {{-- Info Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5">
                <h2 class="text-white font-bold text-lg">{{ $session->nama_sesi }}</h2>
                <p class="text-indigo-200 text-sm mt-0.5">{{ $session->package->nama }}</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Durasi</p>
                        <p class="font-bold text-gray-900 text-lg">{{ $session->package->durasi_menit }}<span class="text-sm font-medium text-gray-500 ml-1">menit</span></p>
                    </div>
                    @if ($session->package->waktu_minimal_menit)
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 mb-1">Minimal Submit</p>
                            <p class="font-bold text-gray-900 text-lg">{{ $session->package->waktu_minimal_menit }}<span class="text-sm font-medium text-gray-500 ml-1">menit</span></p>
                        </div>
                    @endif
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Percobaan</p>
                        @if ($session->package->max_pengulangan == 0)
                            <p class="font-bold text-gray-900 text-lg">ke-{{ $attemptCount + 1 }}<span class="text-sm font-medium text-gray-500 ml-1">(tak terbatas)</span></p>
                        @else
                            <p class="font-bold text-gray-900 text-lg">ke-{{ $attemptCount + 1 }}<span class="text-sm font-medium text-gray-500 ml-1">dari {{ $session->package->max_pengulangan }}</span></p>
                        @endif
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 mb-1">Acak Soal</p>
                        <p class="font-bold text-gray-900">{{ $session->package->acak_soal ? 'Ya' : 'Tidak' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Warning box --}}
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-5">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-amber-800 mb-2">Perhatian sebelum memulai</p>
                    <ul class="space-y-1.5 text-sm text-amber-700">
                        <li class="flex items-start gap-2">
                            <span class="shrink-0 mt-0.5">•</span>
                            <span>Pastikan koneksi internet Anda stabil.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="shrink-0 mt-0.5">•</span>
                            <span>Jangan menutup atau berpindah tab/jendela browser selama ujian berlangsung.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="shrink-0 mt-0.5">•</span>
                            <span>Jawaban tersimpan otomatis setiap Anda memilih opsi.</span>
                        </li>
                        @if ($session->package->waktu_minimal_menit)
                            <li class="flex items-start gap-2">
                                <span class="shrink-0 mt-0.5">•</span>
                                <span>Anda tidak dapat submit sebelum <strong>{{ $session->package->waktu_minimal_menit }} menit</strong> berlalu.</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('ujian.mulai', $session->id) }}" method="POST">
            @csrf

            @if ($session->token_akses)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
                    <label for="token" class="block text-sm font-semibold text-gray-700 mb-3">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Token Akses dari Pengawas
                        </span>
                    </label>
                    <input type="text" id="token" name="token"
                        class="w-full rounded-xl border-gray-300 shadow-sm text-sm px-4 py-3 font-mono text-center tracking-widest text-lg
                            focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition uppercase
                            @error('token') border-red-400 ring-2 ring-red-100 @enderror"
                        placeholder="XXXXXX"
                        autocomplete="off"
                        maxlength="20">
                    @error('token')
                        <p class="mt-2 text-xs text-red-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            @endif

            <div x-data="{ loading: false }">
                <button
                    type="submit"
                    @click="loading = true"
                    :disabled="loading"
                    :class="loading ? 'opacity-70 cursor-not-allowed' : 'hover:bg-indigo-700 active:bg-indigo-800'"
                    class="w-full bg-indigo-600 text-white font-bold py-4 px-6 rounded-2xl shadow-sm transition-colors text-base flex items-center justify-center gap-2">
                    <svg x-show="loading" class="animate-spin w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" style="display:none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="!loading">{{ $attemptCount === 0 ? 'Mulai Ujian Sekarang' : 'Kerjakan Ulang (ke-' . ($attemptCount + 1) . ')' }}</span>
                    <span x-show="loading" style="display:none">Memproses...</span>
                </button>
            </div>
        </form>
    </div>
</x-peserta-layout>
