<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Undangan Sidang KP</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 17mm 14mm; }
        body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    @include('exam-invitations.partials.letter-body', ['invitation' => $invitation, 'verificationUrl' => $verificationUrl])
</body>
</html>
