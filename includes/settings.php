<?php
/**
 * Settings page and configuration
 */

namespace SzamlazzHuFluentCart;

use \SzamlaAgent\Language;
use \SzamlaAgent\Document\Invoice\Invoice;
use FluentCart\App\Models\TaxRate;

// Exit if accessed directly
if (!\defined('ABSPATH')) {
    exit;
}

/**
 * Register admin menu
 */
\add_action('admin_menu', function() {
    \add_options_page(
        \__('Számlázz.hu for FluentCart Settings', 'integration-for-szamlazzhu-fluentcart'),
        'Számlázz.hu',
        'manage_options',
        'integration-for-szamlazzhu-fluentcart',
        __NAMESPACE__ . '\\settings_page'
    );
});

/**
 * Register settings
 */
\add_action('admin_init', function() {
    \register_setting('szamlazz_hu_fluentcart_settings', 'szamlazz_hu_agent_api_key');
    \register_setting('szamlazz_hu_fluentcart_settings', 'szamlazz_hu_shipping_vat', [
        'type' => 'integer',
        'default' => 27,
        'sanitize_callback' => function($value) {
            $allowed = [0, 5, 18, 27];
            return in_array((int)$value, $allowed) ? (int)$value : 27;
        }
    ]);
    \register_setting('szamlazz_hu_fluentcart_settings', 'szamlazz_hu_invoice_language', [
        'type' => 'string',
        'default' => Language::LANGUAGE_HU,
        'sanitize_callback' => function($value) {
            try {
                $allowed = Language::getAll();
                return in_array($value, $allowed) ? $value : Language::LANGUAGE_HU;
            } catch (\Exception $e) {
                return Language::LANGUAGE_HU;
            }
        }
    ]);
    \register_setting('szamlazz_hu_fluentcart_settings', 'szamlazz_hu_invoice_type', [
        'type' => 'integer',
        'default' => Invoice::INVOICE_TYPE_P_INVOICE,
        'sanitize_callback' => function($value) {
            $allowed = [Invoice::INVOICE_TYPE_P_INVOICE, Invoice::INVOICE_TYPE_E_INVOICE];
            return in_array((int)$value, $allowed) ? (int)$value : Invoice::INVOICE_TYPE_P_INVOICE;
        }
    ]);
    \register_setting('szamlazz_hu_fluentcart_settings', 'szamlazz_hu_quantity_unit', [
        'type' => 'string',
        'default' => 'db',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    \register_setting('szamlazz_hu_fluentcart_settings', 'szamlazz_hu_shipping_title', [
        'type' => 'string',
        'default' => 'Szállítás',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    
    // Handle clear cache action
    if (isset($_POST['szamlazz_hu_clear_cache']) && \check_admin_referer('szamlazz_hu_clear_cache_action', 'szamlazz_hu_clear_cache_nonce')) {
        clear_cache();
        \add_settings_error('szamlazz_hu_messages', 'szamlazz_hu_cache_cleared', \__('Cache cleared successfully', 'integration-for-szamlazzhu-fluentcart'), 'updated');
    }
    
    // Handle apply shipping VAT action
    if (isset($_POST['szamlazz_hu_apply_shipping_vat']) && \check_admin_referer('szamlazz_hu_apply_shipping_vat_action', 'szamlazz_hu_apply_shipping_vat_nonce')) {
        $shipping_vat = \get_option('szamlazz_hu_shipping_vat', 27);
        setShippingTaxRate($shipping_vat);
        \add_settings_error('szamlazz_hu_messages', 'szamlazz_hu_vat_applied', \__('Shipping VAT rate applied to all tax rates successfully', 'integration-for-szamlazzhu-fluentcart'), 'updated');
    }
    
    \add_settings_section(
        'szamlazz_hu_api_section',
        \__('API Settings', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            echo '<p>' . \esc_html__('Enter your Számlázz.hu API credentials below.', 'integration-for-szamlazzhu-fluentcart') . '</p>';
        },
        'integration-for-szamlazzhu-fluentcart'
    );
    
    \add_settings_field(
        'szamlazz_hu_agent_api_key',
        \__('Agent API Key', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $value = \get_option('szamlazz_hu_agent_api_key', '');
            echo '<input type="password" name="szamlazz_hu_agent_api_key" value="' . \esc_attr($value) . '" class="regular-text" autocomplete="off" />';
            echo '<p class="description"><a href="https://tudastar.szamlazz.hu/gyik/kulcs" target="_blank" rel="noopener noreferrer">' . \esc_html__('What is this?', 'integration-for-szamlazzhu-fluentcart') . '</a></p>';
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_api_section'
    );
    
    \add_settings_section(
        'szamlazz_hu_invoice_section',
        \__('Invoice Settings', 'integration-for-szamlazzhu-fluentcart'),
        null,
        'integration-for-szamlazzhu-fluentcart'
    );
    
    \add_settings_field(
        'szamlazz_hu_invoice_language',
        \__('Invoice Language', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $value = \get_option('szamlazz_hu_invoice_language', Language::LANGUAGE_HU);
            $languages = [
                Language::LANGUAGE_HU => \__('Magyar (Hungarian)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_EN => \__('English', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_DE => \__('Deutsch (German)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_IT => \__('Italiano (Italian)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_RO => \__('Română (Romanian)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_SK => \__('Slovenčina (Slovak)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_HR => \__('Hrvatski (Croatian)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_FR => \__('Français (French)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_ES => \__('Español (Spanish)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_CZ => \__('Čeština (Czech)', 'integration-for-szamlazzhu-fluentcart'),
                Language::LANGUAGE_PL => \__('Polski (Polish)', 'integration-for-szamlazzhu-fluentcart')
            ];
            echo '<select name="szamlazz_hu_invoice_language">';
            foreach ($languages as $code => $name) {
                echo '<option value="' . \esc_attr($code) . '" ' . ($code == $value) ? 'selected>' : '>' . \esc_html($name) . '</option>';
            }
            echo '</select>';
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_invoice_section'
    );
    
    \add_settings_field(
        'szamlazz_hu_invoice_type',
        \__('Invoice Type', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $value = \get_option('szamlazz_hu_invoice_type', Invoice::INVOICE_TYPE_P_INVOICE);
            $types = [
                Invoice::INVOICE_TYPE_P_INVOICE => \__('Paper Invoice', 'integration-for-szamlazzhu-fluentcart'),
                Invoice::INVOICE_TYPE_E_INVOICE => \__('E-Invoice', 'integration-for-szamlazzhu-fluentcart')
            ];
            echo '<select name="szamlazz_hu_invoice_type">';
            foreach ($types as $type_value => $type_name) {
                echo '<option value="' . \esc_attr($type_value) . '" ' . ($type_value == $value) ? 'selected>' : '>' . \esc_html($type_name) . '</option>';
            }
            echo '</select>';
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_invoice_section'
    );
    
    \add_settings_field(
        'szamlazz_hu_quantity_unit',
        \__('Quantity Unit', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $value = \get_option('szamlazz_hu_quantity_unit', 'db');
            echo '<input type="text" name="szamlazz_hu_quantity_unit" value="' . \esc_attr($value) . '" class="regular-text" />';
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_invoice_section'
    );
    
    \add_settings_field(
        'szamlazz_hu_shipping_title',
        \__('Shipping Title', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $value = \get_option('szamlazz_hu_shipping_title', 'Szállítás');
            echo '<input type="text" name="szamlazz_hu_shipping_title" value="' . \esc_attr($value) . '" class="regular-text" />';
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_invoice_section'
    );
    
    \add_settings_field(
        'szamlazz_hu_shipping_vat',
        \__('Shipping VAT Rate', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $value = \get_option('szamlazz_hu_shipping_vat', 27);
            $options = [0, 5, 18, 27];
            echo '<select name="szamlazz_hu_shipping_vat">';
            foreach ($options as $option) {
                echo '<option value="' . \esc_attr($option) . '" ' . ($option == $value) ? 'selected>' : '>' . \esc_html($option) . '%</option>';
            }
            echo '</select>';
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_invoice_section'
    );
    
    \add_settings_field(
        'szamlazz_hu_apply_shipping_vat_field',
        \__('Apply to Tax Rates', 'integration-for-szamlazzhu-fluentcart'),
        function() {
            $current_rates = getShippingTaxRates();
            $selected_vat = \get_option('szamlazz_hu_shipping_vat', 27);
            
            if (empty($current_rates)) {
                echo '<p class="description" style="color: #dc3232;"><strong>' . \esc_html__('Warning:', 'integration-for-szamlazzhu-fluentcart') . '</strong> ' . \esc_html__('No tax rates found. Please configure tax rates in FluentCart first.', 'integration-for-szamlazzhu-fluentcart') . '</p>';
            } elseif (count($current_rates) === 1 && $current_rates[0] == $selected_vat) {
                echo '<p class="description" style="color: #46b450;">' . \esc_html__('All tax rates are already set to', 'integration-for-szamlazzhu-fluentcart') . ' ' . \esc_html($selected_vat) . '%</p>';
            } else {
                echo '<p class="description">' . \esc_html__('Current shipping VAT rates in use:', 'integration-for-szamlazzhu-fluentcart') . ' ' . \esc_html(\implode(', ', $current_rates)) . '%</p>';
            }
        },
        'integration-for-szamlazzhu-fluentcart',
        'szamlazz_hu_invoice_section'
    );
    
});

/**
 * Add settings link to plugins page
 */
\add_filter('plugin_action_links_' . \plugin_basename(\dirname(__DIR__) . '/integration-for-szamlazzhu-fluentcart.php'), function($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        \admin_url('options-general.php?page=integration-for-szamlazzhu-fluentcart'),
        \__('Settings', 'integration-for-szamlazzhu-fluentcart')
    );
    \array_unshift($links, $settings_link);
    return $links;
});

/**
 * Settings page callback
 */
function settings_page() {
    if (!\current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['settings-updated'])) {
        \add_settings_error('szamlazz_hu_messages', 'szamlazz_hu_message', \__('Settings Saved', 'integration-for-szamlazzhu-fluentcart'), 'updated');
    }
    
    \settings_errors('szamlazz_hu_messages');
    ?>
    <div class="wrap">
        <h1><?php echo \esc_html(\get_admin_page_title()); ?></h1>
        
        <!-- API Settings Form -->
        <form action="options.php" method="post">
            <?php
            \settings_fields('szamlazz_hu_fluentcart_settings');
            \do_settings_sections('integration-for-szamlazzhu-fluentcart');
            \submit_button(\__('Save Settings', 'integration-for-szamlazzhu-fluentcart'));
            ?>
        </form>
        <h1><?php echo \esc_html__('Actions', 'integration-for-szamlazzhu-fluentcart'); ?></h1>
        
        <h2><?php echo \esc_html__('Shipping VAT Settings', 'integration-for-szamlazzhu-fluentcart'); ?></h2>
        
        <!-- Apply Shipping VAT Form -->
        <?php
        $current_rates = getShippingTaxRates();
        $selected_vat = \get_option('szamlazz_hu_shipping_vat', 27);
        $is_button_disabled = empty($current_rates) || (count($current_rates) === 1 && $current_rates[0] == strval($selected_vat));
        ?>
        <form action="<?php echo \esc_url(\admin_url('options-general.php?page=integration-for-szamlazzhu-fluentcart')); ?>" method="post" style="margin-top: 20px;">
            <?php \wp_nonce_field('szamlazz_hu_apply_shipping_vat_action', 'szamlazz_hu_apply_shipping_vat_nonce'); ?>
            <input type="hidden" name="szamlazz_hu_apply_shipping_vat" value="1" />
            <?php \submit_button(\__('Apply Shipping VAT to All Tax Rates', 'integration-for-szamlazzhu-fluentcart'), 'primary', 'submit', false, $is_button_disabled ? ['disabled' => true] : []); ?>
        </form>
        
        <!-- Cache Management Section -->
        <h2><?php echo \esc_html__('Cache Management', 'integration-for-szamlazzhu-fluentcart'); ?></h2>
        <?php
        $cache_size = get_cache_size();
        $formatted_size = format_bytes($cache_size);
        ?>
        <p><?php echo \esc_html__('Current cache size:', 'integration-for-szamlazzhu-fluentcart'); ?> <strong><?php echo \esc_html($formatted_size); ?></strong></p>
        <p class="description"><?php echo \esc_html__('Clearing the cache will delete all cached PDFs, XMLs, and logs.', 'integration-for-szamlazzhu-fluentcart'); ?></p>
        
        <!-- Clear Cache Form -->
        <form action="<?php echo \esc_url(\admin_url('options-general.php?page=integration-for-szamlazzhu-fluentcart')); ?>" method="post" style="margin-top: 20px;">
            <?php \wp_nonce_field('szamlazz_hu_clear_cache_action', 'szamlazz_hu_clear_cache_nonce'); ?>
            <input type="hidden" name="szamlazz_hu_clear_cache" value="1" />
            <?php \submit_button(\__('Clear Cache', 'integration-for-szamlazzhu-fluentcart'), 'secondary', 'submit', false); ?>
        </form>
    </div>
    <?php
}

/**
 * Set shipping tax rate for all Hungarian tax rates
 */
function setShippingTaxRate($vatRate) {
    $taxRates = TaxRate::where('country', 'HU')->get();
    foreach ($taxRates as $rate) {
        $rate->for_shipping = $vatRate;
        $rate->save();
    }
}

/**
 * Get all unique shipping tax rates for Hungarian tax rates
 */
function getShippingTaxRates() {
    $taxRates = TaxRate::where('country', 'HU')->get();
    $rates = [];
    
    foreach ($taxRates as $taxRate) {
        // Use for_shipping if not null, otherwise use rate
        $rate = $taxRate->for_shipping !== null ? $taxRate->for_shipping : $taxRate->rate;
        $rates[] = $rate;
    }
    
    // Return only distinct values
    return array_values(array_unique($rates));
}
