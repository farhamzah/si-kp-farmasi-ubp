<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
    <div class="p-6"><h2 class="text-xl font-black text-slate-950">{{ $title }}</h2></div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-widest text-slate-500">
                <tr>
                    <th class="px-5 py-3">Mahasiswa</th>
                    <th class="px-5 py-3">Tempat</th>
                    <th class="px-5 py-3">Kesiapan</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assignments as $assignment)
                    @php
                        $studentDisplay = app(\App\Services\KpMasterDataReadService::class)->getStudentDisplayData($assignment->student);
                        $eligibility = isset($assessorType) ? $assignment->assessmentEligibility($assessorType) : ['ready' => true, 'pending' => []];
                        $firstPending = collect($eligibility['pending'] ?? [])->first();
                    @endphp
                    <tr>
                        <td class="px-5 py-4">
                            <p class="font-bold">{{ $studentDisplay->name }}</p>
                            <p class="text-xs text-slate-500">{{ $studentDisplay->studentNumber }} - {{ $assignment->period->name }}</p>
                        </td>
                        <td class="px-5 py-4">{{ $assignment->place->name }}</td>
                        <td class="px-5 py-4">
                            @if($eligibility['ready'])
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Siap dinilai</span>
                            @else
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Belum siap</span>
                                @if($firstPending)
                                    <p class="mt-2 max-w-md text-xs leading-5 text-slate-500">{{ $firstPending['label'] }}: {{ $firstPending['description'] }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route($routeName,$assignment) }}" class="inline-flex rounded-2xl border border-cyan-200 px-4 py-2 text-xs font-bold text-cyan-700">
                                {{ $eligibility['ready'] ? 'Input Nilai' : 'Lihat Syarat' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Belum ada mahasiswa untuk dinilai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-5">{{ $assignments->links() }}</div>
</section>
