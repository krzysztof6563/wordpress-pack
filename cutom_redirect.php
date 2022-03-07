<?php

class DevLog {
    public function __construct() {
        add_action('init',  [$this, 'add_rewrite_rule']);
        add_filter('query_vars',  [$this, 'whitelist_query_vars']);
        add_action('template_include', [$this, 'redirect_template']);
    }
    
    public function add_rewrite_rule() {
        add_rewrite_rule('some-text/([a-z0-9-]+)/some-text/([0-9-]+)?$', 'index.php?param1=$matches[1]&param2=$matches[2]', 'top' );
    }

    public function whitelist_query_vars($query_vars) {
        $query_vars[] = 'param1';
        $query_vars[] = 'param2';
        return $query_vars;
    }

    public function redirect_template($template) {
        if ( get_query_var( 'param1' ) == false || get_query_var( 'param2' ) == '' ) {
            return $template;
        }
     
        return get_template_directory() . '/page-templates/custom_page.php';
    }
}
