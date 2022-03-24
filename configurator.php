<?php
    use Library\Assets;

    $assets = new Assets;
    $assets->registerHead('style', "configurator.css", array("origin" => "module:basic-mailer", "permanent" => true));
    $assets->registerBody('script', "configurator.js", array("origin" => "module:basic-mailer", "permanent" => true, "properties" => "type=\"module\""));
?>

<section id="test-mail">
    <h1>
        Send a test mail.
    </h1>

    <p class="notice" :if="notice != ''">
        <br> {{ notice }}
    </p>

    <input type="text" :value="email" placeholder="E-mail address">
    <button @click="send()">
        Send!
    </button>
</section>

<section id="configuration">
    <input-toggle &enabled="checked" $id="mailer-enabled" $label="Enable mailer"></input-toggle>
    <input-toggle &is_html="checked" $id="is-html" $label="HTML E-mail"></input-toggle>

    <input-field &host="value" $placeholder="Hostname"></input-field>
    <input-field &port="value" $placeholder="Port" $type="number"></input-field>
    <input-select &smtp_secure_options="options" $placeholder="SMTP Encryption" &smtp_secure="selected"></input-select>
    
    <input-field &from="value" $placeholder="From address"></input-field>
    <input-field &from_name="value" $placeholder="From name"></input-field>
    
    <input-toggle &smtp_auth="checked" $id="smtp-auth" $label="Authenticate"></input-toggle>
    <input-field &username="value" $placeholder="Username" :if="smtp_auth"></input-field>
    <input-field &password="value" $placeholder="Password" :if="smtp_auth"></input-field>


    <div class="submitter">
        <button @click="save()">
            Save settings
        </button>
        <p class="message" :class:show="showMessage">
            {{ message }}
        </p>
    </div>
</section>