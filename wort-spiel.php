<?php
/*
Plugin Name: Wort-Spiel System
Plugin URI: https://your-website.com
Description: Interaktives Wort-Lernspiel für Kinder mit Audio-Unterstützung
Version: 1.0.0
Author: Ihr Name
Author URI: https://your-website.com
License: GPL v2 or later
Text Domain: wort-spiel
*/

// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('WORT_SPIEL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WORT_SPIEL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WORT_SPIEL_VERSION', '1.0.0');

/**
 * Haupt-Plugin-Klasse
 */
class WortSpielPlugin {
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Plugin initialisieren
        add_action('init', array($this, 'init'));
        
        // Scripts und Styles einbinden
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Admin Scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Plugin-Aktivierung
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // AJAX-Handler registrieren
        add_action('wp_ajax_wort_spiel_save_game', array($this, 'ajax_save_game'));
        add_action('wp_ajax_wort_spiel_get_user_modes', array($this, 'ajax_get_user_modes'));
        add_action('wp_ajax_wort_spiel_get_game_history', array($this, 'ajax_get_game_history'));
        
        // Admin AJAX
        add_action('wp_ajax_wort_spiel_save_user_modes', array($this, 'ajax_save_user_modes'));
        add_action('wp_ajax_wort_spiel_get_all_players', array($this, 'ajax_get_all_players'));
    }
    
    /**
     * Plugin initialisieren
     */
    public function init() {
        // Textdomain laden
        load_plugin_textdomain('wort-spiel', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Shortcodes registrieren
        add_shortcode('wort_spiel_menu', array($this, 'shortcode_menu'));
        add_shortcode('wort_spiel_game', array($this, 'shortcode_game'));
        add_shortcode('wort_spiel_admin', array($this, 'shortcode_admin'));
        
        // Admin-Menü hinzufügen
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        
        // Custom Post Type für Spielergebnisse (optional)
        $this->register_post_types();
    }
    
    /**
     * Plugin-Aktivierung
     */
    public function activate() {
        // Rollen und Capabilities erstellen
        $this->create_roles();
        
        // Datenbank-Tabellen erstellen (falls nötig)
        $this->create_tables();
        
        // Standard-Optionen setzen
        add_option('wort_spiel_default_modes', array('animals', 'nature'));
        add_option('wort_spiel_audio_enabled', true);
        
        // Rewrite-Regeln erneuern
        flush_rewrite_rules();
    }
    
    /**
     * Plugin-Deaktivierung
     */
    public function deactivate() {
        // Rewrite-Regeln erneuern
        flush_rewrite_rules();
        
        // Rollen entfernen (optional)
        // $this->remove_roles();
    }
    
    /**
     * Rollen und Capabilities erstellen
     */
    private function create_roles() {
        // Neue Rolle für Spieler erstellen
        add_role('wort_spiel_player', __('Wort-Spiel Spieler', 'wort-spiel'), array(
            'read' => true,
            'play_wort_spiel' => true,
        ));
        
        // Admin-Capabilities hinzufügen
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_wort_spiel');
            $admin_role->add_cap('view_wort_spiel_stats');
            $admin_role->add_cap('play_wort_spiel');
        }
        
        // Editor-Rolle kann auch verwalten
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('manage_wort_spiel');
            $editor_role->add_cap('view_wort_spiel_stats');
            $editor_role->add_cap('play_wort_spiel');
        }
    }
    
    /**
     * Custom Post Types registrieren
     */
    private function register_post_types() {
        // Für erweiterte Funktionen später
    }
    
    /**
     * Datenbank-Tabellen erstellen
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabelle für Spielergebnisse
        $table_name = $wpdb->prefix . 'wort_spiel_results';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_id varchar(50) NOT NULL,
            game_mode varchar(50) NOT NULL,
            target_word varchar(50) NOT NULL,
            user_input varchar(50) NOT NULL,
            is_correct tinyint(1) NOT NULL,
            duration int(11) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Scripts und Styles für Frontend
     */
    public function enqueue_scripts() {
        // Nur laden wenn Shortcode auf der Seite ist
        if (has_shortcode(get_post()->post_content ?? '', 'wort_spiel_menu') || 
            has_shortcode(get_post()->post_content ?? '', 'wort_spiel_game')) {
            
            // CSS
            wp_enqueue_style(
                'wort-spiel-style',
                WORT_SPIEL_PLUGIN_URL . 'assets/css/style.css',
                array(),
                WORT_SPIEL_VERSION
            );
            
            // JavaScript
            wp_enqueue_script(
                'wort-spiel-script',
                WORT_SPIEL_PLUGIN_URL . 'assets/js/game.js',
                array('jquery'),
                WORT_SPIEL_VERSION,
                true
            );
            
            // Konfetti-Library
            wp_enqueue_script(
                'confetti',
                'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js',
                array(),
                null,
                true
            );
            
            // SortableJS
            wp_enqueue_script(
                'sortable',
                'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
                array(),
                null,
                true
            );
            
            // AJAX-Parameter für JavaScript
            wp_localize_script('wort-spiel-script', 'wortSpielAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wort_spiel_nonce'),
                'pluginUrl' => WORT_SPIEL_PLUGIN_URL,
                'userId' => get_current_user_id(),
                'userName' => wp_get_current_user()->display_name
            ));
        }
    }
    
    /**
     * Admin Scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Nur auf unserer Admin-Seite laden
        if (strpos($hook, 'wort-spiel') !== false) {
            wp_enqueue_style(
                'wort-spiel-admin-style',
                WORT_SPIEL_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WORT_SPIEL_VERSION
            );
            
            wp_enqueue_script(
                'wort-spiel-admin-script',
                WORT_SPIEL_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                WORT_SPIEL_VERSION,
                true
            );
            
            wp_localize_script('wort-spiel-admin-script', 'wortSpielAdminAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wort_spiel_admin_nonce'),
            ));
        }
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Wort-Spiel Verwaltung', 'wort-spiel'),
            __('Wort-Spiel', 'wort-spiel'),
            'manage_wort_spiel',
            'wort-spiel-admin',
            array($this, 'admin_page'),
            'dashicons-games',
            30
        );
        
        // Untermenüs
        add_submenu_page(
            'wort-spiel-admin',
            __('Spieler verwalten', 'wort-spiel'),
            __('Spieler', 'wort-spiel'),
            'manage_wort_spiel',
            'wort-spiel-players',
            array($this, 'players_page')
        );
        
        add_submenu_page(
            'wort-spiel-admin',
            __('Statistiken', 'wort-spiel'),
            __('Statistiken', 'wort-spiel'),
            'view_wort_spiel_stats',
            'wort-spiel-stats',
            array($this, 'stats_page')
        );
        
        add_submenu_page(
            'wort-spiel-admin',
            __('Einstellungen', 'wort-spiel'),
            __('Einstellungen', 'wort-spiel'),
            'manage_wort_spiel',
            'wort-spiel-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Shortcode: Menü anzeigen
     */
    public function shortcode_menu($atts) {
        // Benutzer muss eingeloggt und berechtigt sein
        if (!is_user_logged_in()) {
            return '<p>' . __('Bitte loggen Sie sich ein, um das Spiel zu spielen.', 'wort-spiel') . '</p>';
        }
        
        if (!current_user_can('play_wort_spiel')) {
            return '<p>' . __('Sie haben keine Berechtigung für dieses Spiel.', 'wort-spiel') . '</p>';
        }
        
        ob_start();
        include WORT_SPIEL_PLUGIN_PATH . 'templates/menu.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Spiel anzeigen
     */
    public function shortcode_game($atts) {
        $atts = shortcode_atts(array(
            'mode' => 'animals'
        ), $atts);
        
        if (!is_user_logged_in() || !current_user_can('play_wort_spiel')) {
            return '<p>' . __('Sie haben keine Berechtigung für dieses Spiel.', 'wort-spiel') . '</p>';
        }
        
        ob_start();
        include WORT_SPIEL_PLUGIN_PATH . 'templates/game.php';
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Admin-Interface
     */
    public function shortcode_admin($atts) {
        if (!current_user_can('manage_wort_spiel')) {
            return '<p>' . __('Sie haben keine Berechtigung für diesen Bereich.', 'wort-spiel') . '</p>';
        }
        
        ob_start();
        include WORT_SPIEL_PLUGIN_PATH . 'templates/admin-frontend.php';
        return ob_get_clean();
    }
    
    /**
     * Admin-Seiten
     */
    public function admin_page() {
        include WORT_SPIEL_PLUGIN_PATH . 'includes/admin/dashboard.php';
    }
    
    public function players_page() {
        include WORT_SPIEL_PLUGIN_PATH . 'includes/admin/players.php';
    }
    
    public function stats_page() {
        include WORT_SPIEL_PLUGIN_PATH . 'includes/admin/stats.php';
    }
    
    public function settings_page() {
        include WORT_SPIEL_PLUGIN_PATH . 'includes/admin/settings.php';
    }
    
    /**
     * AJAX: Spiel-Ergebnis speichern
     */
    public function ajax_save_game() {
        // Nonce prüfen
        if (!wp_verify_nonce($_POST['nonce'], 'wort_spiel_nonce')) {
            wp_die(__('Sicherheitsfehler', 'wort-spiel'));
        }
        
        // Berechtigung prüfen
        if (!current_user_can('play_wort_spiel')) {
            wp_die(__('Keine Berechtigung', 'wort-spiel'));
        }
        
        // Daten sanitizen
        $game_data = array(
            'session_id' => sanitize_text_field($_POST['session_id']),
            'game_mode' => sanitize_text_field($_POST['game_mode']),
            'target_word' => sanitize_text_field($_POST['target_word']),
            'user_input' => sanitize_text_field($_POST['user_input']),
            'is_correct' => (bool) $_POST['is_correct'],
            'duration' => intval($_POST['duration'])
        );
        
        // In Datenbank speichern
        global $wpdb;
        $table_name = $wpdb->prefix . 'wort_spiel_results';
        
        $result = $wpdb->insert(
            $table_name,
            array_merge($game_data, array('user_id' => get_current_user_id())),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Ergebnis gespeichert', 'wort-spiel')));
        } else {
            wp_send_json_error(array('message' => __('Fehler beim Speichern', 'wort-spiel')));
        }
    }
    
    /**
     * AJAX: User-Modi abrufen
     */
    public function ajax_get_user_modes() {
        if (!wp_verify_nonce($_POST['nonce'], 'wort_spiel_nonce')) {
            wp_die(__('Sicherheitsfehler', 'wort-spiel'));
        }
        
        if (!current_user_can('play_wort_spiel')) {
            wp_die(__('Keine Berechtigung', 'wort-spiel'));
        }
        
        $user_id = get_current_user_id();
        $allowed_modes = get_user_meta($user_id, 'wort_spiel_allowed_modes', true);
        
        // Fallback zu Standard-Modi
        if (empty($allowed_modes)) {
            $allowed_modes = get_option('wort_spiel_default_modes', array('animals', 'nature'));
        }
        
        wp_send_json_success(array('allowed_modes' => $allowed_modes));
    }
    
    /**
     * AJAX: Spiel-Historie abrufen
     */
    public function ajax_get_game_history() {
        if (!wp_verify_nonce($_POST['nonce'], 'wort_spiel_nonce')) {
            wp_die(__('Sicherheitsfehler', 'wort-spiel'));
        }
        
        if (!current_user_can('play_wort_spiel')) {
            wp_die(__('Keine Berechtigung', 'wort-spiel'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wort_spiel_results';
        $user_id = get_current_user_id();
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY timestamp DESC LIMIT 100",
            $user_id
        ));
        
        wp_send_json_success(array('history' => $results));
    }
    
    /**
     * AJAX Admin: User-Modi speichern
     */
    public function ajax_save_user_modes() {
        if (!wp_verify_nonce($_POST['nonce'], 'wort_spiel_admin_nonce')) {
            wp_die(__('Sicherheitsfehler', 'wort-spiel'));
        }
        
        if (!current_user_can('manage_wort_spiel')) {
            wp_die(__('Keine Berechtigung', 'wort-spiel'));
        }
        
        $user_id = intval($_POST['user_id']);
        $modes = array_map('sanitize_text_field', $_POST['modes']);
        
        $result = update_user_meta($user_id, 'wort_spiel_allowed_modes', $modes);
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    /**
     * AJAX Admin: Alle Spieler abrufen
     */
    public function ajax_get_all_players() {
        if (!current_user_can('manage_wort_spiel')) {
            wp_die(__('Keine Berechtigung', 'wort-spiel'));
        }
        
        // Alle Benutzer mit der Rolle 'wort_spiel_player' abrufen
        $users = get_users(array(
            'role__in' => array('wort_spiel_player', 'administrator', 'editor'),
            'meta_key' => 'wort_spiel_allowed_modes',
            'meta_compare' => 'EXISTS'
        ));
        
        $players_data = array();
        
        foreach ($users as $user) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wort_spiel_results';
            
            $stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_games,
                    SUM(is_correct) as correct_games,
                    AVG(duration) as avg_duration
                FROM $table_name 
                WHERE user_id = %d",
                $user->ID
            ));
            
            $players_data[$user->display_name] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'allowed_modes' => get_user_meta($user->ID, 'wort_spiel_allowed_modes', true),
                'stats' => $stats
            );
        }
        
        wp_send_json_success($players_data);
    }
    
    /**
     * Auto-Rolle und Modi für neue User zuweisen
     */
    public function auto_assign_user_role($user_id) {
        // User-Objekt abrufen
        $user = new WP_User($user_id);
        
        // Rolle auf wort_spiel_player setzen
        $user->set_role('wort_spiel_player');
        
        // Standard-Modi zuweisen
        $default_modes = get_option('wort_spiel_default_modes', array('animals', 'nature'));
        update_user_meta($user_id, 'wort_spiel_allowed_modes', $default_modes);
        
        // Log für Admin
        error_log("Wort-Spiel: Neue User-Rolle zugewiesen für User ID $user_id");
    }
    
    /**
     * Verfügbare Spielmodi abrufen
     */
    public function get_available_game_modes() {
        $default_modes = array(
            'animals' => array(
                'id' => 'animals',
                'title' => __('Tiere', 'wort-spiel'),
                'description' => __('Katze, Hund, Vogel und mehr', 'wort-spiel'),
                'template' => 'game.php'
            ),
            'animals-learning' => array(
                'id' => 'animals-learning',
                'title' => __('Tiere (Lernmodus)', 'wort-spiel'),
                'description' => __('Mit sichtbarem Wort - Katze, Hund, Vogel und mehr', 'wort-spiel'),
                'template' => 'game-learning.php',
                'category' => 'animals'
            ),
            'nature' => array(
                'id' => 'nature',
                'title' => __('Natur', 'wort-spiel'),
                'description' => __('Baum, Blume, Sonne und mehr', 'wort-spiel'),
                'template' => 'game.php'
            ),
            'nature-learning' => array(
                'id' => 'nature-learning',
                'title' => __('Natur (Lernmodus)', 'wort-spiel'),
                'description' => __('Mit sichtbarem Wort - Baum, Blume, Sonne und mehr', 'wort-spiel'),
                'template' => 'game-learning.php',
                'category' => 'nature'
            ),
            'colors' => array(
                'id' => 'colors',
                'title' => __('Farben', 'wort-spiel'),
                'description' => __('Rot, Blau, Grün und mehr', 'wort-spiel'),
                'template' => 'game.php'
            ),
            'colors-learning' => array(
                'id' => 'colors-learning',
                'title' => __('Farben (Lernmodus)', 'wort-spiel'),
                'description' => __('Mit sichtbarem Wort - Rot, Blau, Grün und mehr', 'wort-spiel'),
                'template' => 'game-learning.php',
                'category' => 'colors'
            ),
            'food' => array(
                'id' => 'food',
                'title' => __('Essen', 'wort-spiel'),
                'description' => __('Brot, Käse, Apfel und mehr', 'wort-spiel'),
                'template' => 'game.php'
            ),
            'food-learning' => array(
                'id' => 'food-learning',
                'title' => __('Essen (Lernmodus)', 'wort-spiel'),
                'description' => __('Mit sichtbarem Wort - Brot, Käse, Apfel und mehr', 'wort-spiel'),
                'template' => 'game-learning.php',
                'category' => 'food'
            ),
            'food-extra' => array(
                'id' => 'food-extra',
                'title' => __('Essen (Extra)', 'wort-spiel'),
                'description' => __('Erweiterte Essen-Wörter mit besonderen Features', 'wort-spiel'),
                'template' => 'game-extra.php',
                'category' => 'food'
            ),
            'counting' => array(                           // ← NEU HINZUFÜGEN
    'id' => 'counting',                        // ← NEU
    'title' => __('Zahlen 1-9', 'wort-spiel'),// ← NEU  
    'description' => __('Zahlen in Reihenfolge klicken', 'wort-spiel'), // ← NEU
    'template' => 'game-counting.php'          // ← NEU
            )
             
        );
        
        // Hook für andere Plugins/Themes, um neue Modi hinzuzufügen
        return apply_filters('wort_spiel_available_game_modes', $default_modes);
    }
}

// Plugin initialisieren
new WortSpielPlugin();