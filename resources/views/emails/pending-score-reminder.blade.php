<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a">
<div style="max-width:680px;margin:0 auto;padding:28px 16px">
    <div style="background:#ffffff;border:1px solid #e2e8f0;padding:28px">
        <p style="margin:0;color:#0e7490;font-size:12px;font-weight:700;letter-spacing:1px">SI-KP FARMASI UBP</p>
        <h1 style="margin:10px 0 8px;font-size:24px">Pengingat pengisian nilai KP</h1>
        <p style="margin:0 0 20px;line-height:1.6;color:#475569">Yth. {{ $pendingRow['assessor']->name }}, terdapat {{ $pendingRow['pending_count'] }} penilaian sebagai {{ $pendingRow['assessor_label'] }} yang belum disubmit untuk periode {{ $period->name }}.</p>

        @foreach($pendingRow['items'] as $item)
            <div style="margin-top:12px;border:1px solid #e2e8f0;padding:16px">
                <p style="margin:0;font-weight:700">{{ $item['student_name'] }}</p>
                <p style="margin:5px 0 12px;color:#64748b;font-size:13px">{{ $item['student_number'] }} · {{ $item['place_name'] }}</p>
                <a href="{{ $item['url'] }}" style="display:inline-block;background:#0e7490;color:#ffffff;text-decoration:none;padding:10px 16px;font-weight:700">Buka Penilaian</a>
            </div>
        @endforeach

        <p style="margin:24px 0 0;line-height:1.6;color:#64748b;font-size:13px">Silakan login menggunakan akun KP-Farmasi. Daftar ini dibuat otomatis dari nilai yang belum berstatus submitted.</p>
        <p style="margin:8px 0 0;font-size:13px"><a href="{{ url('/') }}" style="color:#0e7490">{{ url('/') }}</a></p>
    </div>
</div>
</body>
</html>
