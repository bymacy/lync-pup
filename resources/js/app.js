import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.store('navigation', {
    hasUnsavedChanges: false,
    showLeaveModal: false,
    nextUrl: null,
});

Alpine.store('toast', {
    show: false,
    title: '',
    message: '',
    type: 'success',

    notify(type, title, message) {
        this.type = type;
        this.title = title;
        this.message = message;
        this.show = true;

        setTimeout(() => {
            this.show = false;
        }, 3000);
    },

    success(title, message) {
        this.notify('success', title, message);
    },

    error(title, message) {
        this.notify('error', title, message);
    },

    warning(title, message) {
        this.notify('warning', title, message);
    },

    info(title, message) {
        this.notify('info', title, message);
    },

    hide() {
        this.show = false;
    }
});

Alpine.start();