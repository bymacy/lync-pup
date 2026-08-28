<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('admin.exports._styles')
</head>
<body>
@include('admin.exports._rubric-content', ['startup' => $startup, 'type' => 'TMRL', 'stage' => 'Pre-Assessment', 'assessment' => $assessment])
@include('admin.exports._footer')
</body>
</html>
