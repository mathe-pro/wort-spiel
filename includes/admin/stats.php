<?php
/**
 * Detaillierte Statistiken
 * 
 * @package WortSpiel
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Berechtigung prüfen
if (!current_user_can('view_wort_spiel_stats')) {
    wp_die(__('Sie haben keine Berechtigung für diesen Bereich.', 'wort-spiel'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'wort_spiel_results';

// Filter-Parameter
$selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_mode = isset($_GET['game_mode']) ? sanitize_text_field($_GET['game_mode']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$show_only_errors = isset($_GET['errors_only']) ? true : false;

// Basis-Query aufbauen
$where_conditions = array('1=1');
$query_params = array();

if ($selected_user > 0) {
    $where_conditions[] = 'r.user_id = %d';
    $query_params[] = $selected_user;
}

if (!empty($selected_mode)) {
    $where_conditions[] = 'r.game_mode = %s';
    $query_params[] = $selected_mode;
}

if (!empty($date_from)) {
    $where_conditions[] = 'DATE(r.timestamp) >= %s';
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = 'DATE(r.timestamp) <= %s';
    $query_params[] = $date_to;
}

if ($show_only_errors) {
    $where_conditions[] = 'r.is_correct = 0';
}

$where_clause = implode(' AND ', $where_conditions);

// Alle Spieler für Filter-Dropdown
$all_users = $wpdb->get_results("
    SELECT DISTINCT u.ID, u.display_name, COUNT(r.id) as game_count
    FROM {$wpdb->users} u
    JOIN $table_name r ON u.ID = r.user_id
    GROUP BY u.ID, u.display_name
    ORDER BY u.display_name ASC
");

// Alle Spielmodi für Filter-Dropdown
$all_modes = $wpdb->get_col("
    SELECT DISTINCT game_mode 
    FROM $table_name 
    ORDER BY game_mode ASC
");

// Haupt-Query für Spiel-Details
$games_query = "
    SELECT 
        r.*,
        u.display_name as player_name,
        u.user_email
    FROM $table_name r
    JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE $where_clause
    ORDER BY r.timestamp DESC
    LIMIT 1000
";

if (!empty($query_params)) {
    $games = $wpdb->get_results($wpdb->prepare($games_query, $query_params));
} else {
    $games = $wpdb->get_results($games_query);
}

// Zusammenfassungs-Statistiken für aktuellen Filter
$summary_query = "
    SELECT 
        COUNT(*) as total_games,
        COUNT(DISTINCT r.user_id) as unique_players,
        SUM(r.is_correct) as correct_games,
        AVG(r.duration) as avg_duration,
        MIN(r.timestamp) as first_game,
        MAX(r.timestamp) as last_game
    FROM $table_name r
    JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE $where_clause
";

if (!empty($query_params)) {
    $summary = $wpdb->get_row($wpdb->prepare($summary_query, $query_params));
} else {
    $summary = $wpdb->get_row($summary_query);
}

$accuracy = $summary->total_games > 0 ? round(($summary->correct_games / $summary->total_games) * 100, 1) : 0;

// Fehler-Analyse
$error_analysis_query = "
    SELECT 
        r.target_word,
        r.user_input,
        r.game_mode,
        COUNT(*) as error_count,
        u.display_name as player_name
    FROM $table_name r
    JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE $where_clause AND r.is_correct = 0
    GROUP BY r.target_word, r.user_input, r.game_mode, u.display_name
    ORDER BY error_count DESC, r.target_word ASC
    LIMIT 100
";

if (!empty($query_params)) {
    $error_analysis = $wpdb->get_results($wpdb->prepare($error_analysis_query, $query_params));
} else {
    $error_analysis = $wpdb->get_results($error_analysis_query);
}

// Spieler-Performance (für ausgewählten Zeitraum)
$player_performance_query = "
    SELECT 
        u.display_name,
        COUNT(*) as total_games,
        SUM(r.is_correct) as correct_games,
        ROUND((SUM(r.is_correct) / COUNT(*)) * 100, 1) as accuracy,
        AVG(r.duration) as avg_duration,
        COUNT(DISTINCT r.game_mode) as modes_played
    FROM $table_name r
    JOIN {$wpdb->users} u ON r.user_id = u.ID
    WHERE $where_clause
    GROUP BY r.user_id, u.display_name
    ORDER BY accuracy DESC, total_games DESC
    LIMIT 50
";

if (!empty($query_params)) {
    $player_performance = $wpdb->get_results($wpdb->prepare($player_performance_query, $query_params));
} else {
    $player_performance = $wpdb->get_results($player_performance_query);
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-bar"></span>
        <?php _e('Detaillierte Statistiken', 'wort-spiel'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Filter-Sektion -->
    <div class="stats-filters">
        <h3><?php _e('Filter', 'wort-spiel'); ?></h3>
        <form method="get" action="">
            <input type="hidden" name="page" value="wort-spiel-stats">
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="user_id"><?php _e('Spieler:', 'wort-spiel'); ?></label>
                    <select name="user_id" id="user_id">
                        <option value=""><?php _e('Alle Spieler', 'wort-spiel'); ?></option>
                        <?php foreach ($all_users as $user): ?>
                        <option value="<?php echo $user->ID; ?>" <?php selected($selected_user, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?> (<?php echo $user->game_count; ?> Spiele)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="game_mode"><?php _e('Spielmodus:', 'wort-spiel'); ?></label>
                    <select name="game_mode" id="game_mode">
                        <option value=""><?php _e('Alle Modi', 'wort-spiel'); ?></option>
                        <?php foreach ($all_modes as $mode): ?>
                        <option value="<?php echo esc_attr($mode); ?>" <?php selected($selected_mode, $mode); ?>>
                            <?php echo esc_html($mode); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from"><?php _e('Von:', 'wort-spiel'); ?></label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to"><?php _e('Bis:', 'wort-spiel'); ?></label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="errors_only">
                        <input type="checkbox" name="errors_only" id="errors_only" <?php checked($show_only_errors); ?>>
                        <?php _e('Nur Fehler', 'wort-spiel'); ?>
                    </label>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="button button-primary"><?php _e('Filtern', 'wort-spiel'); ?></button>
                    <a href="<?php echo admin_url('admin.php?page=wort-spiel-stats'); ?>" class="button"><?php _e('Zurücksetzen', 'wort-spiel'); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Zusammenfassung -->
    <div class="stats-summary">
        <h3><?php _e('Zusammenfassung (aktueller Filter)', 'wort-spiel'); ?></h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-number"><?php echo number_format($summary->total_games ?? 0); ?></div>
                <div class="summary-label"><?php _e('Spiele gesamt', 'wort-spiel'); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo number_format($summary->unique_players ?? 0); ?></div>
                <div class="summary-label"><?php _e('Spieler', 'wort-spiel'); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo $accuracy; ?>%</div>
                <div class="summary-label"><?php _e('Erfolgsquote', 'wort-spiel'); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php echo round($summary->avg_duration ?? 0); ?>s</div>
                <div class="summary-label"><?php _e('Ø Dauer', 'wort-spiel'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Export-Buttons -->
    <div class="export-actions">
        <button id="export-filtered-data" class="button button-secondary">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Gefilterte Daten exportieren (CSV)', 'wort-spiel'); ?>
        </button>
        <button id="export-error-analysis" class="button button-secondary">
            <span class="dashicons dashicons-warning"></span>
            <?php _e('Fehler-Analyse exportieren', 'wort-spiel'); ?>
        </button>
    </div>
    
    <!-- Tabs -->
    <div class="stats-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#games-tab" class="nav-tab nav-tab-active" data-tab="games"><?php _e('Alle Spiele', 'wort-spiel'); ?> (<?php echo count($games); ?>)</a>
            <a href="#errors-tab" class="nav-tab" data-tab="errors"><?php _e('Fehler-Analyse', 'wort-spiel'); ?> (<?php echo count($error_analysis); ?>)</a>
            <a href="#players-tab" class="nav-tab" data-tab="players"><?php _e('Spieler-Performance', 'wort-spiel'); ?></a>
        </nav>
        
        <!-- Tab: Alle Spiele -->
        <div id="games-tab" class="tab-content active">
            <?php if (empty($games)): ?>
            <p class="no-data"><?php _e('Keine Spiele gefunden für die aktuellen Filter.', 'wort-spiel'); ?></p>
            <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 120px;"><?php _e('Datum/Zeit', 'wort-spiel'); ?></th>
                        <th style="width: 100px;"><?php _e('Spieler', 'wort-spiel'); ?></th>
                        <th style="width: 80px;"><?php _e('Modus', 'wort-spiel'); ?></th>
                        <th style="width: 80px;"><?php _e('Zielwort', 'wort-spiel'); ?></th>
                        <th style="width: 80px;"><?php _e('Eingabe', 'wort-spiel'); ?></th>
                        <th style="width: 60px;"><?php _e('Ergebnis', 'wort-spiel'); ?></th>
                        <th style="width: 60px;"><?php _e('Dauer', 'wort-spiel'); ?></th>
                        <th><?php _e('Session', 'wort-spiel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                    <tr class="<?php echo $game->is_correct ? 'correct-game' : 'wrong-game'; ?>">
                        <td>
                            <div class="game-timestamp">
                                <?php echo date_i18n('d.m.Y', strtotime($game->timestamp)); ?>
                                <br><small><?php echo date_i18n('H:i:s', strtotime($game->timestamp)); ?></small>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo esc_html($game->player_name); ?></strong>
                            <br><small><?php echo esc_html($game->user_email); ?></small>
                        </td>
                        <td>
                            <span class="mode-badge"><?php echo esc_html($game->game_mode); ?></span>
                        </td>
                        <td>
                            <strong class="target-word"><?php echo esc_html($game->target_word); ?></strong>
                        </td>
                        <td>
                            <span class="user-input <?php echo $game->is_correct ? 'correct' : 'wrong'; ?>">
                                <?php echo esc_html($game->user_input); ?>
                            </span>
                        </td>
                        <td>
                            <span class="result-badge <?php echo $game->is_correct ? 'correct' : 'wrong'; ?>">
                                <?php echo $game->is_correct ? '✅' : '❌'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $game->duration; ?>s
                        </td>
                        <td>
                            <small><?php echo esc_html($game->session_id); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Fehler-Analyse -->
        <div id="errors-tab" class="tab-content">
            <?php if (empty($error_analysis)): ?>
            <p class="no-data"><?php _e('Keine Fehler gefunden für die aktuellen Filter.', 'wort-spiel'); ?></p>
            <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Zielwort', 'wort-spiel'); ?></th>
                        <th><?php _e('Falsche Eingabe', 'wort-spiel'); ?></th>
                        <th><?php _e('Spieler', 'wort-spiel'); ?></th>
                        <th><?php _e('Modus', 'wort-spiel'); ?></th>
                        <th><?php _e('Häufigkeit', 'wort-spiel'); ?></th>
                        <th><?php _e('Fehler-Typ', 'wort-spiel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($error_analysis as $error): ?>
                    <?php
                    // Fehler-Typ analysieren
                    $error_type = '';
                    $target = strtoupper($error->target_word);
                    $input = strtoupper($error->user_input);
                    
                    if (strlen($input) != strlen($target)) {
                        $error_type = 'Länge falsch';
                    } elseif (levenshtein($target, $input) == 1) {
                        $error_type = 'Ein Buchstabe falsch';
                    } elseif (similar_text($target, $input) / strlen($target) > 0.5) {
                        $error_type = 'Reihenfolge falsch';
                    } else {
                        $error_type = 'Komplett falsch';
                    }
                    ?>
                    <tr>
                        <td><strong class="target-word"><?php echo esc_html($error->target_word); ?></strong></td>
                        <td><span class="wrong-input"><?php echo esc_html($error->user_input); ?></span></td>
                        <td><?php echo esc_html($error->player_name); ?></td>
                        <td><span class="mode-badge"><?php echo esc_html($error->game_mode); ?></span></td>
                        <td>
                            <span class="error-frequency"><?php echo $error->error_count; ?>x</span>
                        </td>
                        <td>
                            <span class="error-type-badge"><?php echo $error_type; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Tab: Spieler-Performance -->
        <div id="players-tab" class="tab-content">
            <?php if (empty($player_performance)): ?>
            <p class="no-data"><?php _e('Keine Spieler-Daten gefunden für die aktuellen Filter.', 'wort-spiel'); ?></p>
            <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Rang', 'wort-spiel'); ?></th>
                        <th><?php _e('Spieler', 'wort-spiel'); ?></th>
                        <th><?php _e('Spiele', 'wort-spiel'); ?></th>
                        <th><?php _e('Richtig', 'wort-spiel'); ?></th>
                        <th><?php _e('Erfolgsquote', 'wort-spiel'); ?></th>
                        <th><?php _e('Ø Dauer', 'wort-spiel'); ?></th>
                        <th><?php _e('Modi gespielt', 'wort-spiel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; ?>
                    <?php foreach ($player_performance as $player): ?>
                    <tr>
                        <td>
                            <span class="rank-badge rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                #<?php echo $rank; ?>
                            </span>
                        </td>
                        <td><strong><?php echo esc_html($player->display_name); ?></strong></td>
                        <td><?php echo number_format($player->total_games); ?></td>
                        <td><?php echo number_format($player->correct_games); ?></td>
                        <td>
                            <span class="accuracy-badge accuracy-<?php echo $player->accuracy >= 80 ? 'high' : ($player->accuracy >= 60 ? 'medium' : 'low'); ?>">
                                <?php echo $player->accuracy; ?>%
                            </span>
                        </td>
                        <td><?php echo round($player->avg_duration); ?>s</td>
                        <td><?php echo $player->modes_played; ?></td>
                    </tr>
                    <?php $rank++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.stats-filters {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: bold;
    color: #333;
}

.filter-group select,
.filter-group input[type="date"] {
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    min-width: 120px;
}

.stats-summary {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.summary-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.summary-number {
    font-size: 2rem;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.summary-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 5px;
}

.export-actions {
    margin-bottom: 20px;
}

.export-actions .button {
    margin-right: 10px;
}

.stats-tabs {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.tab-content {
    display: none;
    padding: 20px;
}

.tab-content.active {
    display: block;
}

.correct-game {
    background-color: #f8fff8 !important;
}

.wrong-game {
    background-color: #fffbfb !important;
}

.game-timestamp {
    font-size: 0.9rem;
}

.mode-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: bold;
}

.target-word {
    color: #2e7d32;
    font-weight: bold;
}

.user-input.correct {
    color: #2e7d32;
    font-weight: bold;
}

.user-input.wrong {
    color: #d32f2f;
    font-weight: bold;
}

.result-badge {
    font-size: 1.2rem;
}

.wrong-input {
    color: #d32f2f;
    font-weight: bold;
    background: #ffebee;
    padding: 2px 6px;
    border-radius: 3px;
}

.error-frequency {
    background: #ff5722;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 0.85rem;
}

.error-type-badge {
    background: #f57c00;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
}

.rank-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 0.9rem;
}

.rank-1 { background: #ffd700; color: #333; }
.rank-2 { background: #c0c0c0; color: #333; }
.rank-3 { background: #cd7f32; color: white; }
.rank-other { background: #e0e0e0; color: #666; }

.accuracy-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.85rem;
}

.accuracy-high { background: #d4edda; color: #155724; }
.accuracy-medium { background: #fff3cd; color: #856404; }
.accuracy-low { background: #f8d7da; color: #721c24; }

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px 20px;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .export-actions .button {
        display: block;
        width: 100%;
        margin-bottom: 10px;
        margin-right: 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab-Funktionalität
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).data('tab');
        
        // Tabs umschalten
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Content umschalten
        $('.tab-content').removeClass('active');
        $('#' + targetTab + '-tab').addClass('active');
    });
    
    // Export-Funktionen
    $('#export-filtered-data').on('click', function() {
        var params = new URLSearchParams(window.location.search);
        params.set('action', 'wort_spiel_export_stats');
        params.set('format', 'csv');
        params.set('nonce', '<?php echo wp_create_nonce('wort_spiel_admin_nonce'); ?>');
        
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?' + params.toString();
    });
    
    $('#export-error-analysis').on('click', function() {
        var params = new URLSearchParams(window.location.search);
        params.set('action', 'wort_spiel_export_errors');
        params.set('format', 'csv');
        params.set('nonce', '<?php echo wp_create_nonce('wort_spiel_admin_nonce'); ?>');
        
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?' + params.toString();
    });
});
</script>