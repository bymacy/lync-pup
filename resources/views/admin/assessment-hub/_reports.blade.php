{{--
    Reports tab: this startup's "Save to Reports"-d exports
    ($savedReports, passed by AssessmentHubController, already scoped to
    $selectedStartup and ordered newest first). Reached only when a
    specific startup is selected — see the Overview/Reports guard in
    _assessment.blade.php.
--}}
<div x-data="{ confirmingId: null }">
    @if ($savedReports->isEmpty())
    <div class="rounded-xl border border-dashed p-12 text-center text-gray-400">
        No saved reports yet for {{ $selectedStartup->company_name }}. Use "Export Document" above, then
        "Save to Reports" once it's generated.
    </div>
    @else
    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-left text-white">
                    <th class="px-4 py-3 font-semibold">File Name</th>
                    <th class="px-4 py-3 font-semibold">Format</th>
                    <th class="px-4 py-3 font-semibold">Pages</th>
                    <th class="px-4 py-3 font-semibold">Size</th>
                    <th class="px-4 py-3 font-semibold">Generated</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($savedReports as $report)
                <tr class="border-b border-gray-100 last:border-0">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $report->file_name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->format }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->page_count ?: '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->file_size_label }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $report->created_at->format('M d, Y g:i A') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.exports.download', $report) }}"
                                class="text-xs font-semibold text-rose-900 hover:underline">Download File</a>

                            <button type="button" @click="confirmingId = {{ $report->saved_report_id }}"
                                class="text-gray-400 transition hover:text-red-600" aria-label="Delete report">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                </svg>
                            </button>

                            {{-- Delete confirmation modal for this row --}}
                            <template x-teleport="body">
                                <div x-show="confirmingId === {{ $report->saved_report_id }}" x-cloak x-transition.opacity
                                    @keydown.escape.window="confirmingId = null"
                                    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4" style="display:none;">
                                    <div @click.outside="confirmingId = null"
                                        class="relative w-full max-w-md rounded-2xl bg-white px-8 pb-8 pt-10 text-center shadow-2xl">
                                        <button type="button" @click="confirmingId = null"
                                            class="absolute right-4 top-4 flex h-6 w-6 items-center justify-center rounded-full border border-[#6D0D23] text-[#6D0D23] transition hover:bg-[#6D0D23] hover:text-white"
                                            aria-label="Close">
                                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <h3 class="bg-gradient-to-r from-[#6D0D23] to-[#11386A] bg-clip-text text-xl font-bold text-transparent">
                                            Delete Report File
                                        </h3>

                                        <p class="mt-2 text-sm text-gray-600">
                                            Are you sure you want to delete this file? This action is permanent
                                            and cannot be undone.
                                        </p>

                                        <div class="mt-6 grid grid-cols-2 gap-4">
                                            <button type="button" @click="confirmingId = null"
                                                class="h-11 rounded-lg border border-[#6D0D23] bg-white text-sm font-bold text-[#6D0D23] transition hover:bg-rose-50">
                                                Cancel
                                            </button>

                                            <form method="POST" action="{{ route('admin.exports.destroy', $report) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="h-11 w-full rounded-lg bg-gradient-to-r from-[#6D0D23] to-[#11386A] text-sm font-bold text-white transition hover:opacity-90">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
