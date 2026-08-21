<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Undangan Sidang KP</title>
</head>
<body>
    @include('exam-invitations.partials.letter-body', ['invitation' => $invitation, 'verificationUrl' => $verificationUrl])
</body>
</html>
