<style>
    {{--
        Cambria, registered from the actual font files extracted from
        Macy's own licensed Windows/Office install (public/fonts/cambria/)
        - DomPDF only rasterizes fonts it has explicitly registered via
        @font-face (or its bundled defaults); naming "Cambria" in a
        font-family list without this block silently falls back to
        whatever generic serif DomPDF has on hand. All four faces are
        registered so normal/bold/italic/bold-italic all resolve to the
        real typeface instead of a synthetic (faux) bold/italic.
    --}}
    @font-face {
        font-family: 'Cambria';
        src: url('{{ public_path('fonts/cambria/Cambria-Regular.ttf') }}');
        font-weight: normal;
        font-style: normal;
    }
    @font-face {
        font-family: 'Cambria';
        src: url('{{ public_path('fonts/cambria/Cambria-Bold.ttf') }}');
        font-weight: bold;
        font-style: normal;
    }
    @font-face {
        font-family: 'Cambria';
        src: url('{{ public_path('fonts/cambria/Cambria-Italic.ttf') }}');
        font-weight: normal;
        font-style: italic;
    }
    @font-face {
        font-family: 'Cambria';
        src: url('{{ public_path('fonts/cambria/Cambria-BoldItalic.ttf') }}');
        font-weight: bold;
        font-style: italic;
    }

    /* Matches the real PUP-TBIDO Word templates: Times New Roman body,
       compact 10px text, black-ruled tables, and the same maroon-to-navy
       gradient section bars used throughout the rest of the app. */
    body { font-family: "Times New Roman", "Liberation Serif", "DejaVu Serif", serif; font-size: 10px; color: #000; }
    table { border-collapse: collapse; width: 100%; }

    table.bordered th, table.bordered td { border: 1px solid #000; padding: 3px 6px; vertical-align: top; font-size: 10px; }
    table.bordered th { background: #f0f0f0; font-weight: bold; text-align: left; text-transform: uppercase; }

    /* Section header bars, e.g. "I. FOUNDER'S INFORMATION" / "SECTION 2:
       TECHNOLOGY READINESS LEVEL (TRL)" — same brand gradient used
       elsewhere in the app (#6D0D23 -> #11386A). */
    .section-title {
        /* DomPDF doesn't reliably render `background: linear-gradient(...)` as a shorthand value - when it silently fails, the background stays blank while `color: #fff` below still applies, making these bars invisible (white text on a white page). background-color is a guaranteed-to-render fallback; background-image layers the gradient on top of it wherever DomPDF does support it. */
        background-color: #6D0D23;
        background-image: linear-gradient(90deg, #6D0D23 0%, #11386A 100%);
        color: #fff;
        padding: 5px 10px;
        font-weight: bold;
        font-size: 11px;
        letter-spacing: 0.3px;
        margin-top: 10px;
    }

    /* Two-column "numbered label | underlined value" field layout used by
       the Information Sheet (e.g. "1. SURNAME: ___"), with a vertical
       divider between the label and value like the real form. */
    table.info-table { margin-top: 4px; }
    table.info-table td { padding: 2px 8px 2px 0; vertical-align: top; font-size: 10px; }
    table.info-table .info-label { white-space: nowrap; padding-right: 6px; text-transform: uppercase; }
    table.info-table .info-value { border-left: 1px solid #000; padding-left: 8px; font-weight: bold; text-decoration: underline; }

    .field-row { margin: 2px 0; font-size: 10px; }
    .field-label { font-weight: bold; text-transform: uppercase; }
    .field-value { text-decoration: underline; font-weight: bold; }

    /* Small square checkbox glyph, filled with "X" when checked. */
    .checkbox { display: inline-block; width: 9px; height: 9px; border: 1px solid #000; margin-right: 3px; text-align: center; font-size: 8px; line-height: 9px; font-weight: bold; }

    /* Stacked signatory block ("Prepared By:" / bold underlined name /
       italic position), matching the real forms rather than a 3-column
       side-by-side layout. */
    .sig-block { margin-top: 10px; }
    .sig-label { font-size: 10px; }
    .sig-name { font-weight: bold; text-decoration: underline; margin-top: 12px; font-size: 10px; }
    .sig-position { font-style: italic; font-size: 9px; margin-top: 1px; }

    .signature-box { border: 1px solid #000; height: 46px; }
    /* Plain caption directly under a signature box, e.g. "Founder's
       Signature (Sign inside the box)" - no rule of its own, since the
       box's bottom border already reads as the boundary. */
    .signature-caption { text-align: center; font-size: 9px; padding-top: 2px; }
    /* A blank-line-style field below a signature block, e.g. "Date
       Accomplished" - the horizontal rule doubles as the line the value
       sits on, matching the real form rather than a "Label: value" pair. */
    .sig-line { text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 2px; margin-top: 20px; }

    .section-heading-plain { font-weight: bold; font-size: 10px; text-align: center; margin-top: 10px; text-transform: uppercase; }

    /* Plain numbered headings that are NOT one of the maroon "I./II./..."
       banners (e.g. "22. EDUCATIONAL BACKGROUND", "35. REFERENCES") - the
       real template only puts the colored bar treatment on the five
       Roman-numeral sections; every other numbered item heading is plain,
       left-aligned body text. */
    .item-heading { font-size: 10px; margin-top: 8px; margin-bottom: 2px; }

    .muted { color: #444; }
</style>
