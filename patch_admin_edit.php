<?php
/**
 * Patch: wire up new Admin "edit Information Sheet" routes and add the
 * toast-notification UI to the admin layout (needed for the save feedback
 * on the new editable page).
 * Run once from the Laravel project root: php patch_admin_edit.php
 * Safe to re-run: every replacement is skipped (with a warning) if the
 * anchor text isn't found, instead of corrupting the file.
 */

function patch(string $path, string $search, string $replace, string $label): void
{
    if (! file_exists($path)) {
        echo "  [SKIP] $label — file not found: $path\n";
        return;
    }

    $content = file_get_contents($path);

    if (str_contains($content, $search) === false) {
        echo "  [WARN] $label — anchor text not found in $path (already patched, or file changed). Skipping.\n";
        return;
    }

    if (str_contains($content, $replace)) {
        echo "  [SKIP] $label — already applied.\n";
        return;
    }

    $count = 0;
    $new = str_replace($search, $replace, $content, $count);
    file_put_contents($path, $new);
    echo "  [OK]   $label ($count replacement(s))\n";
}

echo "Patching routes/web.php...\n";

patch(
    'routes/web.php',
    "    Route::patch('startups/{startup}/information-sheet/approve', [InformationSheetController::class, 'approve'])\n"
    ."        ->name('information-sheet.approve');\n\n"
    ."    Route::resource('mentors', MentorController::class)\n",
    "    Route::patch('startups/{startup}/information-sheet/approve', [InformationSheetController::class, 'approve'])\n"
    ."        ->name('information-sheet.approve');\n"
    ."    Route::patch('startups/{startup}/information-sheet', [InformationSheetController::class, 'update'])\n"
    ."        ->name('information-sheet.update');\n\n"
    ."    Route::post('startups/{startup}/information-sheet/team-members', [InformationSheetController::class, 'storeTeamMember'])\n"
    ."        ->name('information-sheet.team-members.store');\n"
    ."    Route::patch('information-sheet/team-members/{teamMember}', [InformationSheetController::class, 'updateTeamMember'])\n"
    ."        ->name('information-sheet.team-members.update');\n"
    ."    Route::delete('information-sheet/team-members/{teamMember}', [InformationSheetController::class, 'destroyTeamMember'])\n"
    ."        ->name('information-sheet.team-members.destroy');\n\n"
    ."    Route::post('startups/{startup}/information-sheet/incubation', [InformationSheetController::class, 'storeIncubation'])\n"
    ."        ->name('information-sheet.incubation.store');\n"
    ."    Route::patch('information-sheet/incubation/{incubationInvolvement}', [InformationSheetController::class, 'updateIncubation'])\n"
    ."        ->name('information-sheet.incubation.update');\n"
    ."    Route::delete('information-sheet/incubation/{incubationInvolvement}', [InformationSheetController::class, 'destroyIncubation'])\n"
    ."        ->name('information-sheet.incubation.destroy');\n\n"
    ."    Route::post('startups/{startup}/information-sheet/ld', [InformationSheetController::class, 'storeLd'])\n"
    ."        ->name('information-sheet.ld.store');\n"
    ."    Route::patch('information-sheet/ld/{ldIntervention}', [InformationSheetController::class, 'updateLd'])\n"
    ."        ->name('information-sheet.ld.update');\n"
    ."    Route::delete('information-sheet/ld/{ldIntervention}', [InformationSheetController::class, 'destroyLd'])\n"
    ."        ->name('information-sheet.ld.destroy');\n\n"
    ."    Route::post('startups/{startup}/information-sheet/references', [InformationSheetController::class, 'storeReference'])\n"
    ."        ->name('information-sheet.references.store');\n"
    ."    Route::patch('information-sheet/references/{reference}', [InformationSheetController::class, 'updateReference'])\n"
    ."        ->name('information-sheet.references.update');\n"
    ."    Route::delete('information-sheet/references/{reference}', [InformationSheetController::class, 'destroyReference'])\n"
    ."        ->name('information-sheet.references.destroy');\n\n"
    ."    Route::resource('mentors', MentorController::class)\n",
    'Add Admin Information Sheet edit routes (update, team-members, incubation, ld, references)'
);

echo "Patching resources/views/components/layouts/admin.blade.php...\n";

patch(
    'resources/views/components/layouts/admin.blade.php',
    "                    @endif\n\n"
    ."                    {{ \$slot }}\n"
    ."                </main>\n",
    "                    @endif\n\n"
    ."                    <div\n"
    ."                        x-data\n"
    ."                        x-show=\"\$store.toast.show\"\n"
    ."                        x-cloak\n"
    ."                        x-transition:enter=\"transform transition ease-out duration-300\"\n"
    ."                        x-transition:enter-start=\"translate-x-full opacity-0\"\n"
    ."                        x-transition:enter-end=\"translate-x-0 opacity-100\"\n"
    ."                        x-transition:leave=\"transform transition ease-in duration-200\"\n"
    ."                        x-transition:leave-start=\"translate-x-0 opacity-100\"\n"
    ."                        x-transition:leave-end=\"translate-x-full opacity-0\"\n"
    ."                        class=\"fixed top-6 right-6 z-[9999]\">\n\n"
    ."                        <div class=\"flex items-center gap-4 rounded-xl bg-white shadow-2xl border border-gray-200 px-5 py-4 min-w-[360px]\">\n\n"
    ."                            <div class=\"flex h-11 w-11 items-center justify-center rounded-full text-white shadow-lg\"\n"
    ."                                :class=\"{\n"
    ."                                    'bg-gradient-to-r from-[#6D0D23] to-[#11386A]': \$store.toast.type === 'success',\n"
    ."                                    'bg-gradient-to-r from-red-600 to-red-700': \$store.toast.type === 'error',\n"
    ."                                    'bg-gradient-to-r from-amber-500 to-orange-500': \$store.toast.type === 'warning',\n"
    ."                                    'bg-gradient-to-r from-sky-500 to-blue-600': \$store.toast.type === 'info',\n"
    ."                                }\">\n"
    ."                                <template x-if=\"\$store.toast.type === 'success'\"><span class=\"text-xl font-bold\">&#10003;</span></template>\n"
    ."                                <template x-if=\"\$store.toast.type === 'error'\"><span class=\"text-xl font-bold\">&#10005;</span></template>\n"
    ."                                <template x-if=\"\$store.toast.type === 'warning'\"><span class=\"text-xl font-bold\">!</span></template>\n"
    ."                                <template x-if=\"\$store.toast.type === 'info'\"><span class=\"text-xl font-bold\">i</span></template>\n"
    ."                            </div>\n\n"
    ."                            <div class=\"flex-1\">\n"
    ."                                <p class=\"font-semibold text-gray-900\" x-text=\"\$store.toast.title\"></p>\n"
    ."                                <p class=\"text-sm text-gray-500\" x-text=\"\$store.toast.message\"></p>\n"
    ."                            </div>\n\n"
    ."                            <button\n"
    ."                                @click=\"\$store.toast.hide()\"\n"
    ."                                class=\"text-gray-400 hover:text-gray-700 transition text-xl leading-none\">\n"
    ."                                &times;\n"
    ."                            </button>\n\n"
    ."                        </div>\n\n"
    ."                    </div>\n\n"
    ."                    {{ \$slot }}\n"
    ."                </main>\n",
    'Add $store.toast notification UI to the admin layout'
);

echo "\nDone.\n";
