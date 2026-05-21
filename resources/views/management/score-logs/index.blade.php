@extends('layouts.app')
@section('title','Log Nilai - '.config('app.name'))
@section('page_title','Log Nilai')
@section('content')
<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm"><thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-widest text-slate-500"><tr><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Mahasiswa</th><th class="px-5 py-3">Action</th><th class="px-5 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($logs as $log)<tr><td class="px-5 py-4">{{ $log->created_at->format('d M Y H:i') }}</td><td class="px-5 py-4">{{ $log->user->name ?? '-' }}</td><td class="px-5 py-4">{{ $log->assignment?->student?->user?->name ?? '-' }}</td><td class="px-5 py-4 font-bold">{{ $log->action }}</td><td class="px-5 py-4">{{ $log->old_status }} → {{ $log->new_status }}</td></tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada log nilai.</td></tr>@endforelse</tbody></table></div><div class="p-5">{{ $logs->links() }}</div></section>
@endsection
