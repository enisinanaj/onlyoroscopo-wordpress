<?php
class RevisionaryLicenseSettings {
    function display($sitewide, $customize_defaults) {
        if ($customize_defaults || (is_multisite() && !$sitewide && rvy_is_network_activated())) {
            return;
        }
        
        $ui = RvyOptionUI::instance(compact($sitewide, $customize_defaults));

        $tab = 'features';

        require_once(RVY_ABSPATH . '/includes-pro/library/Factory.php');
        $container      = \PublishPress\Revisions\Factory::get_container();
        $licenseManager = $container['edd_container']['license_manager'];

        $use_network_admin = false; !empty($args['use_network_admin']);
        $suppress_updates = false; // $use_network_admin && !is_super_admin();

        $section = 'license'; // --- UPDATE KEY SECTION ---
        ?>
        <tr>
            <td scope="row" colspan="2">
                <?php
                global $activated;

                $id = 'edd_key';

                if (!get_transient('revisionary-pro-refresh-update-info')) {
                    revisionary()->keyStatus(true);
                    set_transient('revisionary-pro-refresh-update-info', true, 86400);
                }

                $opt_val = revisionary()->getOption($id);

                if (!is_array($opt_val) || count($opt_val) < 2) {
                    $activated = false;
                    $expired = false;
                    $key = '';
                    $opt_val = [];
                } else {
                    $activated = !empty($opt_val['license_status']) && ('valid' == $opt_val['license_status']);
                    $expired = $opt_val['license_status'] && ('expired' == $opt_val['license_status']);
                }

                if (isset($opt_val['expire_date']) && is_date($opt_val['expire_date'])) {
                    $date = new \DateTime(date('Y-m-d H:i:s', strtotime($opt_val['expire_date'])), new \DateTimezone('UTC'));
                    $date->setTimezone(new \DateTimezone('America/New_York'));
                    $expire_date_gmt = $date->format("Y-m-d H:i:s");
                    $expire_days = intval((strtotime($expire_date_gmt) - time()) / 86400);
                } else {
                    unset($opt_val['expire_date']);
                }

                $msg = '';

                if ($expired) {
                    $class = 'activating';
                    $is_err = true;
                    $msg = sprintf(
                        __('Your license key has expired. For continued priority support, <a href="%s">please renew</a>.', 'revisionary'),
                        'https://publishpress.com/my-downloads/'
                    );
                } elseif (!empty($opt_val['expire_date'])) {
                    $class = 'activating';
                    if ($expire_days < 30) {
                        $is_err = true;
                    }

                    if ($expire_days == 1) {
                        $msg = sprintf(
                            __('Your license key will expire today. For updates and priority support, <a href="%s">please renew</a>.', 'revisionary'),
                            $expire_days,
                            'https://publishpress.com/my-downloads/'
                        );
                    } elseif ($expire_days < 30) {
                        $msg = sprintf(
                            _n(
                                'Your license key will expire in %d day. For updates and priority support, <a href="%s">please renew</a>.',
                                'Your license key (for plugin updates) will expire in %d days. For updates and priority support, <a href="%s">please renew</a>.',
                                $expire_days,
                                'revisionary'
                            ),
                            $expire_days,
                            'https://publishpress.com/my-downloads/'
                        );
                    } else {
                        $class = "activating hidden";
                    }
                } elseif (!$activated) {
                    $class = 'activating';
                    $msg = sprintf(
                        __('For updates to PublishPress Revisions Pro, activate your <a href="%s">PublishPress license key</a>.', 'revisionary'),
                        'https://publishpress.com/pricing/'
                    );
                } else {
                    $class = "activating hidden";
                    $msg = '';
                }
                ?>

                <div class="pp-key-wrap">

                <?php if ($expired && (!empty($key))) : ?>
                    <span class="pp-key-expired"><?php _e("Key Expired", 'revisionary') ?></span>
                    <input name="<?php echo($id); ?>" type="text" id="<?php echo($id); ?>" style="display:none"/>
                    <button type="button" id="activation-button" name="activation-button"
                            class="button-secondary"><?php _e('Deactivate Key', 'revisionary'); ?></button>
                <?php else : ?>
                    <div class="pp-key-label" style="float:left">
                        <span class="pp-key-active" <?php if (!$activated) echo 'style="display:none;"';?>><?php _e("Key Activated", 'press-permit-core') ?></span>
                        <span class="pp-key-inactive" <?php if ($activated) echo 'style="display:none;"';?>><?php _e("License Key", 'press-permit-core') ?></span>
                    </div>

                        <input name="<?php echo($id); ?>" type="text" placeholder="<?php _e('(please enter publishpress.com key)', 'press-permit-pro');?>" id="<?php echo($id); ?>"
                                maxlength="40" <?php echo ($activated) ? ' style="display:none"' : ''; ?> />

                        <button type="button" id="activation-button" name="activation-button"
                                class="button-secondary"><?php echo (!$activated) ? __('Activate Key', 'revisionary') : __('Deactivate Key', 'revisionary'); ?></button>
                <?php endif; ?>

                    <img id="pp_support_waiting" class="waiting" style="display:none;position:relative"
                            src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>

                    <div class="pp-key-refresh" style="display:inline">
                        &bull;&nbsp;&nbsp;<a href="https://publishpress.com/checkout/purchase-history/"
                                                    target="_blank"><?php _e('review your account info', 'revisionary'); ?></a>
                    </div>
                </div>

                <?php if ($activated) : ?>
                    <?php if ($expired) : ?>
                        <div class="pp-key-hint-expired">
                            <span class="pp-key-expired pp-key-warning"> <?php _e('note: Renewal does not require deactivation. If you do deactivate, re-entry of the license key will be required.', 'revisionary'); ?></span>
                        </div>
                    <?php elseif (revisionary()->getOption('display_hints')) : ?>
                        <div class="pp-key-hint">
                        <span class="rs-subtext"> <?php _e('note: If you deactive, re-entry of the license key will be required for re-activation.', 'revisionary'); ?></span>
                    <?php endif; ?>
                    </div>

                <?php elseif (!$expired) : ?>
                    <div class="pp-key-hint">
                        <span class="rs-subtext"> <?php ?></span>
                    </div>
                <?php endif ?>

                <div id="activation-status" class="<?php echo $class ?>"><?php echo $msg; ?></div>

                <?php if (!empty($is_err)) : ?>
                    <div id="activation-error" class="error"><?php echo $msg; ?></div>
                <?php endif; ?>

                    <?php
                    /*
                    if (!$activated || $expired) {
                        require_once(REVISIONARY_CLASSPATH . '/UI/HintsPro.php');
                        HintsPro::proPromo();
                    }
                    */
                    ?>
            </td>
        </tr>
        <?php

        do_action('revisionary_support_key_ui');
        self::footer_js($activated, $expired);

        $section = 'version'; // --- VERSION SECTION ---
        ?>
            <tr>
                <th scope="row"><?php _e('Version', 'revisionary'); ?></th>
                <td>

                    <?php
                    $update_info = [];

                    $info_link = '';

                    if (!$suppress_updates) {
                        $wp_plugin_updates = get_site_transient('update_plugins');
                        if (
                            $wp_plugin_updates && isset($wp_plugin_updates->response[plugin_basename(REVISIONARY_FILE)])
                            && !empty($wp_plugin_updates->response[plugin_basename(REVISIONARY_FILE)]->new_version)
                            && version_compare($wp_plugin_updates->response[plugin_basename(REVISIONARY_FILE)]->new_version, REVISIONARY_VERSION, '>')
                        ) {
                            $slug = 'revisionary-pro';

                            $_url = "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog&TB_iframe=true&width=600&height=800";
                            $info_url = ($use_network_admin) ? network_admin_url($_url) : admin_url($_url);

                            $info_link = "&nbsp;<span class='update-message'> &bull;&nbsp;&nbsp;<a href='$info_url' class='thickbox'>"
                                . sprintf(__('view %s&nbsp;details', 'revisionary'), $wp_plugin_updates->response[plugin_basename(REVISIONARY_FILE)]->new_version)
                                . '</a></span>';
                        }
                    }

                    ?>
                    <div>
                        <?php printf(__('PublishPress Revisions Pro Version: %1$s %2$s', 'revisionary'), REVISIONARY_VERSION, $info_link); ?>
                        
                        &nbsp;&nbsp;&bull;&nbsp;&nbsp;<a href="<?php echo add_query_arg('rvy_refresh_updates', 1, esc_url($_SERVER['REQUEST_URI']));?>"><?php _e('update check / install', 'revisionary'); ?></a>
                      
                    </div>
                </td>
            </tr>
        <?php

        
        $section = 'branding'; // --- BRANDING SECTION ---
        ?>
        <tr>
            <th scope="row"><?php _e('Branding', 'revisionary'); ?></th>
            <td>
                <?php
                $ui->option_checkbox( 'display_pp_branding', $tab, $section, '', '' );
                ?>
            </td>
        </tr>
    <?php
    }

    private function footer_js($activated, $expired)
    {
        $vars = [
            'activated' => ($activated || !empty($expired)) ? true : false,
            'expired' => !empty($expired),
            'activateCaption' => __('Activate Key', 'revisionary'),
            'deactivateCaption' => __('Deactivate Key', 'revisionary'),
            'connectingCaption' => __('Connecting to publishpress.com server...', 'revisionary'),
            'noConnectCaption' => __('The request could not be processed due to a connection failure.', 'revisionary'),
            'noEntryCaption' => __('Please enter the license key shown on your order receipt.', 'revisionary'),
            'errCaption' => __('An unidentified error occurred.', 'revisionary'),
            'keyStatus' => json_encode([
                'deactivated' => __('The key has been deactivated.', 'revisionary'),
                'valid' => __('The key has been activated.', 'revisionary'),
                'expired' => __('The key has expired.', 'revisionary'),
                'invalid' => __('The key is invalid.', 'revisionary'),
                '-100' => __('An unknown activation error occurred.', 'revisionary'),
                '-101' => __('The key provided is not valid. Please double-check your entry.', 'revisionary'),
                '-102' => __('This site is not valid to activate the key.', 'revisionary'),
                '-103' => __('The key provided could not be validated by publishpress.com.', 'revisionary'),
                '-104' => __('The key provided is already active on another site.', 'revisionary'),
                '-105' => __('The key has already been activated on the allowed number of sites.', 'revisionary'),
                '-200' => __('An unknown deactivation error occurred.', 'revisionary'),
                '-201' => __('Unable to deactivate because the provided key is not valid.', 'revisionary'),
                '-202' => __('This site is not valid to deactivate the key.', 'revisionary'),
                '-203' => __('The key provided could not be validated by publishpress.com.', 'revisionary'),
                '-204' => __('The key provided is not active on the specified site.', 'revisionary'),
            ]),
            'activateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_activate_key'),
            'deactivateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_deactivate_key'),
            'refreshURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_refresh_version'),
            'activationHelp' => sprintf(__('If this is incorrect, <a href="%s">request activation help</a>.', 'revisionary'), 'https://publishpress.com/contact/'),
            'supportOptChanged' => __('Please save settings before uploading site configuration.', 'revisionary'),
        ];

        wp_localize_script('revisionary-pro-settings', 'revisionarySettings', $vars);
    }
} // end class
