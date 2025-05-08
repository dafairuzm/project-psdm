<?php

namespace App\Filament\Resources\UserActivityResource\Pages;

use App\Filament\Resources\UserActivityResource;
use App\Models\UserActivity;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUserActivity extends CreateRecord
{
    protected static string $resource = UserActivityResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $exists = \App\Models\UserActivity::where('user_id', $data['user_id'])
            ->where('activity_id', $data['activity_id'])
            ->exists();

        if ($exists) {
            // Tampilkan notifikasi
            Notification::make()
                ->title('Peserta sudah terdaftar di kegiatan ini.')
                ->danger() // warna merah
                ->persistent() // supaya tetap muncul sampai diklik
                ->send();

            // Lempar error validasi agar tetap muncul di bawah field (opsional)
            throw ValidationException::withMessages([
                'user_id' => 'Peserta ini sudah terdaftar dalam kegiatan tersebut.',
            ]);
        }

        return $data;
    }

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     $exists = UserActivity::where('user_id', $data['user_id'])
    //         ->where('activity_id', $data['activity_id'])
    //         ->exists();

    //     if ($exists) {
    //         throw ValidationException::withMessages([
    //             'user_id' => 'Peserta ini sudah terdaftar dalam kegiatan tersebut.',
    //         ]);
    //     }

    //     return $data;
    // }

}
