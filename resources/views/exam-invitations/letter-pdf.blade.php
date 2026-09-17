<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Undangan Sidang KP</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        html { margin: 0; padding: 0; background: #fff; }
        body { margin: 15mm 17mm 14mm; padding: 0; background: #fff; }
        .pdf-page { width: auto; }
    </style>
</head>
<body>
    <main class="pdf-page">
        @include('exam-invitations.partials.letter-body', compact('invitation', 'verificationUrl', 'logoSrc', 'qrSrc'))
    </main>
</body>
</html>
