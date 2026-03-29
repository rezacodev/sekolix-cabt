<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Masuk</h2>
        <p class="text-gray-500 mt-2 text-sm">Gunakan email, username, atau nomor peserta Anda.</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Login -->
        <div>
            <label for="login" class="block text-sm font-medium text-gray-700 mb-1.5">
                Username / Email / Nomor Peserta
            </label>
            <input
                id="login"
                type="text"
                name="login"
                value="{{ old('login', 'andi.p') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="contoh: andi.p atau andi@sekolah.id"
                class="w-full rounded-xl border-gray-300 shadow-sm text-sm px-4 py-3
                    focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition
                    @error('login') border-red-400 ring-2 ring-red-100 @enderror"
            >
            @error('login')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                Password
            </label>
            <div class="relative">
                <input
                    id="password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    value="peserta123"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-xl border-gray-300 shadow-sm text-sm px-4 py-3 pr-11
                        focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition
                        @error('password') border-red-400 ring-2 ring-red-100 @enderror"
                >
                <button type="button" @click="show = !show"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                    <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit -->
        <button type="submit"
            class="w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold
                py-3 px-6 rounded-xl shadow-sm transition-colors text-sm mt-2">
            Masuk ke Sistem
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-gray-400">
        Untuk administrator, gunakan
        <a href="/cabt/login" class="text-indigo-500 hover:underline">/cabt/login</a>
    </p>
</x-guest-layout>
