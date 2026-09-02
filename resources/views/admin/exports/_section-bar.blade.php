{{--
    One of the maroon-to-navy "I./II./III./IV./V." section banners on the
    Startup Information Sheet. DomPDF's CSS gradient support is unreliable
    (see the note on .section-title in _styles.blade.php - a failed
    gradient there leaves white text on a blank background, i.e.
    invisible), and these banners use custom-kerned bold lettering baked
    into the image itself rather than a web font, so - to actually match
    the official PUP-TBIDO template pixel for pixel - this renders the
    real banner images exported from that template
    (public/images/exports/section-*.jpg) instead of trying to recreate
    them in CSS. Falls back to the old CSS bar (with $text) if an image
    is ever missing, so a startup with no exports folder degrades
    gracefully instead of breaking.
--}}
@php $path = public_path('images/exports/'.$image); @endphp
@if (file_exists($path))
    <img src="{{ $path }}" style="width: 100%; display: block; margin-top: 10px;">
@else
    <div class="section-title">{!! $text !!}</div>
@endif
