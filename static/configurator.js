import { Rable } from '../../../pb-pubfiles/js/rable.js';

const testMailApplication = new Rable({
    data: {
        email: "",
        notice: "",
        send() {
            PbAuth.apiInstance().post(SITE_LOCATION + 'pb-loader/module/basic-mailer/test-mail', {
                recipient: this.email
            }).then(res => {
                if (res.data.success) {
                    this.notice = "E-mail was sent!";
                } else {
                    this.notice = res.data.message + " (" + res.data.error + ")";
                }
            })
        }
    }
});

testMailApplication.mount('#test-mail');

PbAuth.apiInstance().get('user/info').then(res => {
    if (res.data && res.data.success) {
        testMailApplication.data.email = res.data.user.email;
    }
});

const configurationApplication = new Rable({
    data: {
        enabled: false,
        host: "",
        smtp_auth: false,
        username: "",
        password: "",
        smtp_secure: "",
        port: "",
        from: "",
        from_name: "",
        is_html: false,

        message: "",
        showMessage: false,
        smtp_secure_options: {
            none: "None",
            starttls: "Starttls",
            tls: "SSL/TLS"
        },

        save() {
            let body = {};
            ["enabled", "is_html", "host", "port", "smtp_secure", "from", "from_name", "smtp_auth", "username", "password"].forEach(key => {
                body[key] = this[key];
            });

            PbAuth.apiInstance().post(SITE_LOCATION + 'pb-loader/module/basic-mailer/save-settings', body).then(res => {
                this.showMessage = true
                if (res.data.success) {
                    this.message = "Settings saved!";
                    setTimeout(() => this.showMessage = false, 2000);
                } else {
                    this.message = `${res.data.message} (${res.data.error})`;
                }
            });
        }
    }
});

await configurationApplication.importComponent('input-field', SITE_LOCATION + 'pb-loader/module-static/basic-mailer/components/InputField.html');
await configurationApplication.importComponent('input-toggle', SITE_LOCATION + 'pb-loader/module-static/basic-mailer/components/InputToggle.html');
await configurationApplication.importComponent('input-select', SITE_LOCATION + 'pb-loader/module-static/basic-mailer/components/InputSelect.html');
configurationApplication.mount('#configuration');

PbAuth.apiInstance().get(SITE_LOCATION + 'pb-loader/module/basic-mailer/retrieve-settings').then(res => {
    if (res.data.success) {
        Object.keys(res.data.settings).forEach(key => configurationApplication.data[key] = res.data.settings[key]);
    }
});