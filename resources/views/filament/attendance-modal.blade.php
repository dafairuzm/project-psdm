<div class="space-y-4">
    
    @if ($attendances->isEmpty())
        <p class="text-gray-500">Belum ada data kehadiran untuk kegiatan ini.</p>
    @else
        <table class="w-full table-auto border-collapse border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2 text-left">Tanggal</th>
                    <th class="border px-4 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                    <tr>
                        <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('d F Y') }}</td>
                        <td class="border px-4 py-2">{{ $attendance->status }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
