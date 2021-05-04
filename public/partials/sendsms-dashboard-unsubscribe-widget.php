<?php
echo $args['before_widget'];
if (!empty($instance['title'])) {
    echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
}
wp_nonce_field('sendsms-security-nonce');
?>
<div id="sensms-widget-error-message" style="color:red"></div>
<div id="sensms-widget-success-message" style="color:green"></div>
<div id="sensms-widget-feedback-message"></div>
<div id="sendsms-widget-add-form">
    <div class="sendsms-widget-phone-field">
        <label id="sendsms_forPhoneNumber"><?= __('Phone number', 'sendsms-dashboard') ?></label>
        <input id="sendsms_widget_phone_number" type="tel" aria-label="<?= __('Phone number', 'sendsms-dashboard') ?>" aria-describedby="sendsms_forPhoneNumber">
    </div>
    <?php
    /*
    <div class="sendsms-widget-gdpr-field">
        <input id="sendsms_widget_gdpr" type="checkbox" aria-label="<?= __('I agree with the privacy policy', 'sendsms-dashboard') ?>" aria-describedby="sendsms_forGdpr">
        <label id="sendsms_forGdpr">
            <?php
            _e('I agree with the ', 'sendsms-dashboard');
            ?>
            <a href="<?= !empty($instance['gdpr_link']) ? esc_url($instance['gdpr_link']) : "" ?>"><?= _e('privacy policy', 'sendsms-dashboard') ?></a>
            <?php
            ?>
        </label>
    </div>
    */?>
    <div class="sendsms-widget-send-button">
        <button class="button" id="sendsms_subscribe" type="button" aria-label="<?= __('Submit', 'sendsms-dashboard') ?>"><?= __('Submit', 'sendsms-dashboard') ?></button>
    </div>
</div>
<div id="sendsms-widget-verify-form">
    <div id="sendsms-widget-code-field">
        <label id="sendsms_forValidationField"><?= __('Code', 'sendsms-dashboard') ?></label>
        <input id="sendsms_widget_validation_field" type="text" aria-label="<?= __('Code', 'sendsms-dashboard') ?>" aria-describedby="sendsms_forValidationField">
    </div>
    <div class="sendsms-widget-validation-button">
        <button class="button" id="sendsms_validate" type="button" aria-label="<?= __('Verify', 'sendsms-dashboard') ?>"><?= __('Verify', 'sendsms-dashboard') ?></button>
    </div>
</div>
<?php echo $args['after_widget'];