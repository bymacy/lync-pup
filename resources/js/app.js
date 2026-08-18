import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Reflects a value into the URL's query string (?key=value) without
 * triggering a navigation. Used across tabbed/filtered pages (Roadblock
 * Management, Assessment Hub, Startup Roadblock/Archive, etc.) so that
 * whichever tab or filter is currently selected survives a page reload —
 * whether that's the admin hitting F5, or an auto-refresh timer — instead
 * of silently resetting back to that page's default view.
 */
window.setQueryParam = function (key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    window.history.replaceState({}, '', url);
};

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