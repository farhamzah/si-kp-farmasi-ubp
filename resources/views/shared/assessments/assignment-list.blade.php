<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100">
    <div class="p-6"><h2 class="text-xl font-black text-slate-950">{{ $title }}</h2></div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse($assignments as $assignment)
                    <tr><td class="px-5 py-4"><p class="font-bold">{{ $assignment->student->user->name }}</p><p class="text-xs text-slate-500">{{ $assignment->student->nim }} · {{ $assignment->period->name }}</p></td><td class="px-5 py-4">{{ $assignment->place->name }}</td><td class="px-5 py-4"><a href="{{ route($routeName,$assignment) }}" class="font-bold text-cyan-700">Input Nilai</a></td></tr>
                @empty
                    <tr><td class="px-5 py-10 text-center text-slate-500">Belum ada mahasiswa untuk dinilai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-5">{{ $assignments->links() }}</div>
</section>
