<?php

namespace ez_sales_reps\includes;

/**
 * Register & enqueue all necessary js & css
 */
class dep_loader {

    private $deps;

    public function __construct($deps) {

        $this->deps = json_decode($deps, true);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_js_css'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_js_css'));
    }

    public function enqueue_js_css() {

        foreach ($this->deps['js'] as $js_script => $scriptinfo ) {
            $path = $scriptinfo[0];
            if (count($scriptinfo) > 1) {
                $dep = $scriptinfo[1];
                wp_register_script($js_script, plugins_url($path, dirname(__DIR__)), array($dep));
                wp_enqueue_script($js_script);
            } else {
                wp_register_script($js_script, plugins_url($path, dirname(__DIR__)));
                wp_enqueue_script($js_script);
            }
        }
        foreach ($this->deps['css'] as $css_script => $path ) {
            wp_register_style($css_script, plugins_url($path[0]), dirname(__DIR__));
            wp_enqueue_style($css_script);
        }

        //making site url variable available to this plugin's main js file - wouldn't want to hardcode any urls
        wp_localize_script('ez_sales_reps_js', 'WPURLS', array( 'siteurl' => get_option('siteurl') ));
    }
}

?>