import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('navigation', {
    hasUnsavedChanges: false,
    showLeaveModal: false,
    nextUrl: null,
});

Alpine.start();