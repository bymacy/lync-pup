<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('admin.exports._styles')
    <style>
        .doc-page { page-break-after: always; }
        .doc-page:last-child { page-break-after: avoid; }
    </style>
</head>
<body>
{{-- $sections: array of already-rendered content-only HTML strings, one per
     selected document, in the order the admin picked them. Rendered as raw
     HTML (each is trusted server-generated markup, not user input). --}}
@foreach ($sections as $html)
<div class="doc-page">
    {!! $html !!}
    @include('admin.exports._footer')
</div>
@endforeach
</body>
</html>
