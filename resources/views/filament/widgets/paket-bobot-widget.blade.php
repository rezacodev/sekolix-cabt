<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Ringkasan Soal &amp; Bobot
        </x-slot>

        <div class="flex flex-wrap gap-5 items-start">

            {{-- Total Bobot --}}
            @if ($status === 'ok')
                @php $hintText = 'Bobot sudah tepat 100 ✓'; @endphp
                <div class="rounded-xl ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20 px-6 py-4 min-w-[140px] text-center shrink-0">
                    <div class="text-4xl font-bold text-green-700 dark:text-green-300">
                        {{ $totalBobot == (int) $totalBobot ? (int) $totalBobot : number_format($totalBobot, 1) }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-green-700 dark:text-green-300 opacity-70 mt-0.5">dari 100 bobot</div>
                    <div class="text-xs text-green-700 dark:text-green-300 mt-2 opacity-80">{{ $hintText }}</div>
                </div>
            @elseif ($status === 'under')
                @php $hintText = 'Bobot kurang dari 100'; @endphp
                <div class="rounded-xl ring-2 ring-amber-400 bg-amber-50 dark:bg-amber-900/20 px-6 py-4 min-w-[140px] text-center shrink-0">
                    <div class="text-4xl font-bold text-amber-700 dark:text-amber-300">
                        {{ $totalBobot == (int) $totalBobot ? (int) $totalBobot : number_format($totalBobot, 1) }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-amber-700 dark:text-amber-300 opacity-70 mt-0.5">dari 100 bobot</div>
                    <div class="text-xs text-amber-700 dark:text-amber-300 mt-2 opacity-80">{{ $hintText }}</div>
                </div>
            @elseif ($status === 'over')
                @php $hintText = 'Bobot melebihi 100 ⚠'; @endphp
                <div class="rounded-xl ring-2 ring-red-500 bg-red-50 dark:bg-red-900/20 px-6 py-4 min-w-[140px] text-center shrink-0">
                    <div class="text-4xl font-bold text-red-700 dark:text-red-300">
                        {{ $totalBobot == (int) $totalBobot ? (int) $totalBobot : number_format($totalBobot, 1) }}
                    </div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-red-700 dark:text-red-300 opacity-70 mt-0.5">dari 100 bobot</div>
                    <div class="text-xs text-red-700 dark:text-red-300 mt-2 opacity-80">{{ $hintText }}</div>
                </div>
            @else
                <div class="rounded-xl ring-2 ring-gray-300 dark:ring-gray-600 bg-gray-50 dark:bg-gray-800 px-6 py-4 min-w-[140px] text-center shrink-0">
                    <div class="text-4xl font-bold text-gray-500 dark:text-gray-400">0</div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400 opacity-70 mt-0.5">dari 100 bobot</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 opacity-80">Belum ada soal</div>
                </div>
            @endif

            {{-- Jumlah soal --}}
            <div class="rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 bg-gray-50 dark:bg-gray-800/60 px-5 py-4 min-w-[110px] text-center shrink-0">
                <div class="text-4xl font-bold text-gray-700 dark:text-gray-200">{{ $jumlahSoal }}</div>
                <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 mt-0.5">Total Soal</div>
            </div>

            {{-- Breakdown per tipe --}}
            @if ($breakdown->isNotEmpty())
                <div class="flex-1 min-w-[220px]">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                        Per Tipe Soal
                    </p>
                    <div class="space-y-1.5">
                        @foreach ($breakdown as $item)
                            <div class="flex items-center gap-2 text-sm">
                                <span class="w-36 text-gray-600 dark:text-gray-300 truncate text-xs">{{ $item['label'] }}</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-100 w-6 text-right tabular-nums">{{ $item['count'] }}</span>
                                <span class="text-gray-400 text-xs">soal</span>
                                <span class="text-gray-300 dark:text-gray-600">·</span>
                                <span class="font-medium text-gray-700 dark:text-gray-200 tabular-nums text-xs">
                                    {{ $item['bobot'] == (int) $item['bobot'] ? (int) $item['bobot'] : number_format($item['bobot'], 1) }}
                                </span>
                                <span class="text-gray-400 text-xs">bobot</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </x-filament::section>
</x-filament-widgets::widget>
