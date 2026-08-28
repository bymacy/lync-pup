{{--
    Shared PUP-TBIDO footer, matching the real templates' campus
    address block + tagline, plus the row of accreditation badges (2025
    rankings, WURI, QS Stars).
--}}
<table style="margin-top: 16px;">
    <tr>
        <td width="60%" style="vertical-align: bottom;">
            <div style="font-size: 9px; line-height: 1.4;">
                PUP A. Mabini Campus, Anonas Street, Sta. Mesa, Manila 1016<br>
                Trunk Line: 335-1787 or 335-1777<br>
                Website: www.pup.edu.ph | Inquiries: https://bit.ly/PUPSINTA
            </div>
            <div style="font-size: 13px; font-weight: bold; margin-top: 4px;">
                The Country&rsquo;s 1<sup>st</sup> Polytechnic University
            </div>
        </td>
        <td width="40%" style="text-align: right; vertical-align: bottom;">
            @if (file_exists(public_path('images/exports/accreditation badges.jpg')))
                <img src="{{ public_path('images/exports/accreditation badges.jpg') }}" style="width: 220px;">
            @endif
        </td>
    </tr>
</table>
