import { Notyf } from 'notyf';


export const showNotice = (options) => {

    const settings = Object.assign({}, {
        msg: '',
        type: 'warning',
        url: false,
        label: '',
        closeOthers: true
    }, options);

    // Showing on a page with the wp option loaded.
    if (window.wp !== undefined && window.wp.data !== undefined && window.wp.data.dispatch !== undefined) {

        const link = document.createElement("link");
        link.href = 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css';
        link.type = "text/css";
        link.rel = "stylesheet";
        link.media = "screen,print";
        document.getElementsByTagName("head")[0].appendChild(link);
    
        var o = { isDismissible: true };

        var msg = settings.msg;

        if (settings.url) {
            o.actions = [{
                url: settings.url,
                label: settings.label
            }];

            msg = msg.replace('{link}', settings.label);
        } else {
            msg = msg.replace('{link}', '');
        }

        if (settings.closeOthers) {
            document.querySelectorAll('.components-notice').forEach((element) => {
                element.style.display = 'none';
            });
        }

        setTimeout(function () {
            wp.data.dispatch("core/notices").createNotice(settings.type, msg, o);
        }, 10);





    } else {

        // Used for showing notices on preview pages.

        var msg = settings.msg;

        if (settings.url) {
            msg = msg.replace('{link}', '<a href="' + settings.url + '" target="_blank" arial-label="' + settings.label + '">' + settings.label + '</a>');
        } else {
            msg = msg.replace('{link}', '');
        }

        const notyf = new Notyf({
            position: { x: 'left', y: 'top' },
            ripple: false,
            types: [
                {
                    type: 'success',
                    background: '#eff9f1',
                    duration: 2000,
                    dismissible: true,
                    icon: false
                },

                {
                    type: 'warning',
                    background: '#fef8ee',
                    duration: 0,
                    dismissible: true,
                    icon: false
                },
                {
                    type: 'error',
                    background: '#f4a2a2',
                    duration: 0,
                    dismissible: true,
                    icon: false
                }
            ]
        });

        if (settings.closeOthers) {
            notyf.dismissAll();
        }

       const notification = notyf.open({
            type: settings.type,
            message: msg
        });


    }





};


export const debounce = (fn, wait) => {
    let timer;
    return function (...args) {
        if (timer) {
            clearTimeout(timer);
        }
        const context = this;
        timer = setTimeout(() => {
            fn.apply(context, args);
        }, wait);
    }
};


export const isValidDateFormat = (inputString) => {
    // Define a regular expression pattern for the format yyyy-mm-dd
    const regexPattern = /^\d{4}-\d{2}-\d{2}$/;

    // Test the input string against the regular expression
    return regexPattern.test(inputString);
}


