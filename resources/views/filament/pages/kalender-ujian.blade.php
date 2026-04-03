<x-filament-panels::page>
    <div
        x-data="kalenderUjian()"
        x-init="init()"
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-4"
    >
        <div id="kalender-ujian-calendar"></div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap gap-4 mt-3">
        <div class="flex items-center gap-1.5 text-sm text-gray-600">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#6b7280"></span> Draft
        </div>
        <div class="flex items-center gap-1.5 text-sm text-gray-600">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#16a34a"></span> Aktif
        </div>
        <div class="flex items-center gap-1.5 text-sm text-gray-600">
            <span class="inline-block w-3 h-3 rounded-full" style="background:#2563eb"></span> Selesai
        </div>
    </div>

    {{-- FullCalendar v6 --}}
    @push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>
    <script>
        function kalenderUjian() {
            return {
                calendar: null,
                init() {
                    this.$nextTick(() => {
                        const el = document.getElementById('kalender-ujian-calendar');
                        if (!el) return;

                        this.calendar = new FullCalendar.Calendar(el, {
                            initialView: 'dayGridMonth',
                            locale: 'id',
                            height: 'auto',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,listMonth',
                            },
                            buttonText: {
                                today: 'Hari Ini',
                                month: 'Bulan',
                                week: 'Minggu',
                                list: 'Daftar',
                            },
                            events: function(fetchInfo, successCallback, failureCallback) {
                                fetch(`/cabt/kalender/data?start=${fetchInfo.startStr}&end=${fetchInfo.endStr}`, {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                })
                                .then(r => r.json())
                                .then(successCallback)
                                .catch(failureCallback);
                            },
                            eventClick: function(info) {
                                if (info.event.url) {
                                    info.jsEvent.preventDefault();
                                    window.location.href = info.event.url;
                                }
                            },
                            eventContent: function(arg) {
                                return {
                                    html: `<div class="fc-event-main-frame" title="${arg.event.extendedProps.package || ''}">
                                        <div class="fc-event-title">${arg.event.title}</div>
                                    </div>`
                                };
                            },
                        });

                        this.calendar.render();
                    });
                }
            };
        }
    </script>
    @endpush
</x-filament-panels::page>
