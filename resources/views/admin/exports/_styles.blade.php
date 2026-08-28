<style>
    /* Matches the real PUP-TBIDO Word templates: Times New Roman body,
       compact 10px text, black-ruled tables, and the same maroon-to-navy
       gradient section bars used throughout the rest of the app. */
    body { font-family: "Times New Roman", "Liberation Serif", "DejaVu Serif", serif; font-size: 10px; color: #000; }
    table { border-collapse: collapse; width: 100%; }

    table.bordered th, table.bordered td { border: 1px solid #000; padding: 3px 6px; vertical-align: top; font-size: 10px; }
    table.bordered th { background: #f0f0f0; font-weight: bold; text-align: left; }

    /* Section header bars, e.g. "I. FOUNDER'S INFORMATION" / "SECTION 2:
       TECHNOLOGY READINESS LEVEL (TRL)" — same brand gradient used
       elsewhere in the app (#6D0D23 -> #11386A). */
    .section-title {
        background: linear-gradient(90deg, #6D0D23 0%, #11386A 100%);
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
    table.info-table .info-label { white-space: nowrap; padding-right: 6px; }
    table.info-table .info-value { border-left: 1px solid #000; padding-left: 8px; font-weight: bold; text-decoration: underline; }

    .field-row { margin: 2px 0; font-size: 10px; }
    .field-label { font-weight: bold; }
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
    .signature-caption { text-align: center; font-size: 9px; border-top: 1px solid #000; padding-top: 2px; }

    .section-heading-plain { font-weight: bold; font-size: 10px; text-align: center; margin-top: 10px; text-transform: uppercase; }

    .muted { color: #444; }
</style>
