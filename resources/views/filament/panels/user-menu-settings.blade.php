@php
    use App\Filament\Resources\AnnouncementResource;
    use App\Filament\Pages\AuditLogPage;
    use App\Filament\Pages\GeneralSetting;
    use App\Models\User;
@endphp

{{-- <x-filament::dropdown.header
    icon="heroicon-o-cog-6-tooth"
>
    Pengaturan
</x-filament::dropdown.header> --}}

<x-filament::dropdown.list>
    @if (auth()->user()?->level >= User::LEVEL_GURU)
        <x-filament::dropdown.list.item
            href="{{ AnnouncementResource::getUrl() }}"
            icon="heroicon-o-megaphone"
        >
            Pengumuman
        </x-filament::dropdown.list.item>
    @endif

    @if (auth()->user()?->level >= User::LEVEL_ADMIN)
        <x-filament::dropdown.list.item
            href="{{ GeneralSetting::getUrl() }}"
            icon="heroicon-o-cog-6-tooth"
        >
            Pengaturan Umum
        </x-filament::dropdown.list.item>
    @endif

    @if (auth()->user()?->level >= User::LEVEL_SUPER_ADMIN)
        <x-filament::dropdown.list.item
            href="{{ AuditLogPage::getUrl() }}"
            icon="heroicon-o-shield-check"
        >
            Audit Log
        </x-filament::dropdown.list.item>
    @endif
</x-filament::dropdown.list>
