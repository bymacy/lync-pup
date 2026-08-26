<x-layouts.admin title="Risk Monitoring">

    @php
        // Overall Risk Register donut, built as a CSS conic-gradient — no
        // chart.js elsewhere in this app, so hand-rolled to match convention.
        $donutOrder = ['Critical', 'High', 'Moderate', 'Low', 'None'];
        $total = max($totalStartups, 1);
        $cumulative = 0;
        $segments = [];
        foreach ($donutOrder as $level) {
            $count = $levelCounts[$level] ?? 0;
            $pct = $count / $total * 100;
            $start = $cumulative;
            $cumulative += $pct;
            $segments[] = "{$levelColors[$level]} {$start}% {$cumulative}%";
        }
        $gradient = 'conic-gradient(' . implode(', ', $segments) . ')';

        $atRisk = $totalStartups - ($levelCounts['None'] ?? 0);

        // Flattened for Alpine — the detail modal reads from this rather than
        // re-querying the server. @js() handles escaping for the HTML
        // attribute context, so no stray quotes can break x-data parsing.
        $modalRows = $riskRows->map(fn ($row) => [
            'startup' => $row['startup']->company_name,
            'score' => $row['assessment']['score'],
            'level' => $row['assessment']['level'],
            'indicators' => collect($row['assessment']['indicators'])->map(fn ($i) => [
                'key' => $i['key'],
                'label' => $i['label'],
                'severity' => $i['severity'],
                'score' => $i['score'],
            ])->values(),
        ])->values();
    @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Risk Monitoring</h1>
        <p class="text-gray-500 mt-1">Spot startups drifting off-track before it becomes unrecoverable.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-gray-600 text-sm">Total Startups</p>
            <p class="text-4xl font-bold mt-1">{{ $totalStartups }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-gray-600 text-sm">At Risk</p>
            <p class="text-4xl font-bold mt-1">{{ $atRisk }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5" style="border-color: {{ $levelColors['Critical'] }}">
            <p class="text-gray-600 text-sm">Critical</p>
            <p class="text-4xl font-bold mt-1" style="color: {{ $levelColors['Critical'] }}">{{ $levelCounts['Critical'] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5" style="border-color: {{ $levelColors['High'] }}">
            <p class="text-gray-600 text-sm">High</p>
            <p class="text-4xl font-bold mt-1" style="color: {{ $levelColors['High'] }}">{{ $levelCounts['High'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        {{-- Risk Register --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Risk Register</h2>
            <div class="flex items-center gap-8">
                <div class="relative h-48 w-48 shrink-0 rounded-full" style="background: {{ $gradient }};">
                    <div class="absolute inset-7 rounded-full bg-white flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-gray-800">{{ $totalStartups }}</span>
                        <span class="text-xs text-gray-500">Startups</span>
                    </div>
                </div>
                <ul class="space-y-2 text-sm w-full">
                    @foreach ($donutOrder as $level)
                        <li class="flex items-center justify-between gap-4">
                            <span class="flex items-center gap-2 text-gray-700">
                                <span class="h-3 w-3 rounded-full shrink-0" style="background: {{ $levelColors[$level] }}"></span>
                                {{ $level }}
                            </span>
                            <span class="text-gray-500">
                                {{ $levelCounts[$level] ?? 0 }}
                                ({{ $totalStartups ? round((($levelCounts[$level] ?? 0) / $totalStartups) * 100) : 0 }}%)
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Top Risk Categories --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 overflow-x-auto">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Top Risk Categories</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="py-2 pr-4 font-medium">Category</th>
                        <th class="py-2 px-2 font-medium text-center">Risk Count</th>
                        <th class="py-2 px-2 font-medium text-center">Critical</th>
                        <th class="py-2 px-2 font-medium text-center">High</th>
                        <th class="py-2 px-2 font-medium text-center">Moderate</th>
                        <th class="py-2 px-2 font-medium text-center">Low</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categoryBreakdown as $row)
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="py-3 pr-4 font-medium text-gray-800">{{ $row['category'] }}</td>
                            <td class="py-3 px-2 text-center">{{ $row['count'] }}</td>
                            <td class="py-3 px-2 text-center">{{ $row['by_level']['Critical'] }}</td>
                            <td class="py-3 px-2 text-center">{{ $row['by_level']['High'] }}</td>
                            <td class="py-3 px-2 text-center">{{ $row['by_level']['Moderate'] }}</td>
                            <td class="py-3 px-2 text-center">{{ $row['by_level']['Low'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Risk Indicator table --}}
    <div x-data="{ selected: null, rows: @js($modalRows) }" class="rounded-xl border border-gray-200 bg-white p-6 overflow-x-auto">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Risk Indicator</h2>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-200">
                    <th class="py-2 pr-4 font-medium">Startup</th>
                    <th class="py-2 px-2 font-medium">Risk Level</th>
                    <th class="py-2 px-2 font-medium text-center">Score</th>
                    <th class="py-2 px-2 font-medium">Indicators</th>
                    <th class="py-2 pl-2 font-medium text-right">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riskRows as $i => $row)
                    <tr class="border-b border-gray-100 last:border-0 align-top">
                        <td class="py-3 pr-4 font-medium text-gray-800 whitespace-nowrap">{{ $row['startup']->company_name }}</td>
                        <td class="py-3 px-2">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium text-white"
                                style="background: {{ $levelColors[$row['assessment']['level']] }}">
                                {{ $row['assessment']['level'] }}
                            </span>
                        </td>
                        <td class="py-3 px-2 text-center font-semibold text-gray-800">{{ $row['assessment']['score'] }}</td>
                        <td class="py-3 px-2">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($row['assessment']['indicators'] as $indicator)
                                    <span class="rounded-full border px-2 py-0.5 text-xs"
                                        style="border-color: {{ $severityColors[$indicator['severity']] }}; color: {{ $severityColors[$indicator['severity']] }}">
                                        {{ $indicator['label'] }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 pl-2 text-right whitespace-nowrap">
                            <button type="button" class="text-sm font-medium text-[#6D0D23] hover:underline" @click="selected = {{ $i }}">
                                View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-400">No startups currently have an active risk indicator.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Detail modal --}}
        <div x-show="selected !== null" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="selected = null">
            <template x-if="selected !== null">
                <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl" @click.stop>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800" x-text="rows[selected].startup"></h3>
                            <p class="text-sm text-gray-500">
                                Risk Score: <span class="font-semibold" x-text="rows[selected].score"></span>
                                &mdash; <span x-text="rows[selected].level"></span>
                            </p>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="selected = null">
                            &#10005;
                        </button>
                    </div>
                    <ul class="space-y-3 max-h-96 overflow-y-auto">
                        <template x-for="indicator in rows[selected].indicators" :key="indicator.key">
                            <li class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-medium text-gray-800" x-text="indicator.label"></span>
                                    <span class="text-sm font-semibold text-gray-800" x-text="indicator.score"></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" x-text="indicator.severity + ' severity'"></p>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
    </div>

</x-layouts.admin>
