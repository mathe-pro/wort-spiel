<?php
/**
 * Spieler-Verwaltung
 * 
 * @package WortSpiel
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Berechtigung prüfen
if (!current_user_can('manage_wort_spiel')) {
    wp_die(__('Sie haben keine Berechtigung für diesen Bereich.', 'wort-spiel'));
}

// POST-Verarbeitung für Modi-Update
if (isset($_POST['update_user_modes']) && wp_verify_nonce($_POST['_wpnonce'], 'update_user_modes')) {
    $user_id = intval($_POST['user_id']);
    $selected_modes = isset($_POST['game_modes']) ? array_map('sanitize_text_field', $_POST['game_modes']) : array();
    
    if ($user_id > 0) {
        update_user_meta($user_id, 'wort_spiel_allowed_modes', $selected_modes);
        echo '<div class="notice notice-success"><p>' . __('Spielmodi erfolgreich aktualisiert!', 'wort-spiel') . '</p></div>';
    }
}

// Alle Benutzer mit Spiel-Berechtigung abrufen
$users_query = new WP_User_Query(array(
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'wort_spiel_allowed_modes',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => 'wp_capabilities',
            'value' => 'wort_spiel_player',
            'compare' => 'LIKE'
        )
    ),
    'orderby' => 'display_name',
    'order' => 'ASC'
));

$users = $users_query->get_results();

// Zusätzlich: Alle Admins hinzufügen (falls sie nicht schon dabei sind)
$admin_users = get_users(array('role' => 'administrator'));
foreach ($admin_users as $admin) {
    $found = false;
    foreach ($users as $user) {
        if ($user->ID == $admin->ID) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $users[] = $admin;
    }
}

// Statistiken für jeden User abrufen
global $wpdb;
$table_name = $wpdb->prefix . 'wort_spiel_results';

$user_stats = array();
foreach ($users as $user) {
    $stats = $wpdb->get_row($wpdb->prepare("
        SELECT 
            COUNT(*) as total_games,
            SUM(is_correct) as correct_games,
            AVG(duration) as avg_duration,
            MAX(timestamp) as last_played
        FROM $table_name 
        WHERE user_id = %d
    ", $user->ID));
    
    $user_stats[$user->ID] = $stats;
}

// Verfügbare Spielmodi
$available_modes = array(
    'animals' => __('Tiere', 'wort-spiel'),
    'animals-learning' => __('Tiere (Lernmodus)', 'wort-spiel'),
    'nature' => __('Natur', 'wort-spiel'),
    'nature-learning' => __('Natur (Lernmodus)', 'wort-spiel'),
    'colors' => __('Farben', 'wort-spiel'),
    'colors-learning' => __('Farben (Lernmodus)', 'wort-spiel'),
    'food' => __('Essen', 'wort-spiel'),
    'food-learning' => __('Essen (Lernmodus)', 'wort-spiel'),
    'food-extra' => __('Essen (Extra)', 'wort-spiel'),
    'counting' => __('Zahlen 1-9', 'wort-spiel'),
    'animals-audio-extra' => __('Tiere Audio-Extra', 'wort-spiel'),
    'COUNTING_DOTS' => __('Punkte Zählen', 'wort-spiel') 
);

// Ausgewählter User für Details
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_user = $selected_user_id ? get_user_by('ID', $selected_user_id) : null;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php _e('Spieler verwalten', 'wort-spiel'); ?>
    </h1>
    
    <a href="<?php echo admin_url('user-new.php'); ?>" class="page-title-action">
        <?php _e('Neuen Spieler hinzufügen', 'wort-spiel'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <div class="wort-spiel-players-layout">
        
        <!-- Spieler-Liste -->
        <div class="players-list">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select id="bulk-mode-action">
                        <option value=""><?php _e('Bulk-Aktion wählen', 'wort-spiel'); ?></option>
                        <option value="enable-all"><?php _e('Alle Modi aktivieren', 'wort-spiel'); ?></option>
                        <option value="disable-all"><?php _e('Alle Modi deaktivieren', 'wort-spiel'); ?></option>
                        <option value="enable-basic"><?php _e('Nur Basis-Modi aktivieren', 'wort-spiel'); ?></option>
                    </select>
                    <button type="button" id="apply-bulk-action" class="button"><?php _e('Anwenden', 'wort-spiel'); ?></button>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped users">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th class="manage-column column-username column-primary">
                            <?php _e('Spieler', 'wort-spiel'); ?>
                        </th>
                        <th class="manage-column"><?php _e('Spiele', 'wort-spiel'); ?></th>
                        <th class="manage-column"><?php _e('Erfolg', 'wort-spiel'); ?></th>
                        <th class="manage-column"><?php _e('Modi', 'wort-spiel'); ?></th>
                        <th class="manage-column"><?php _e('Letztes Spiel', 'wort-spiel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="no-items">
                            <?php _e('Keine Spieler gefunden. Erstellen Sie einen neuen Benutzer und weisen Sie ihm die Rolle "Wort-Spiel Spieler" zu.', 'wort-spiel'); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <?php 
                            $stats = $user_stats[$user->ID];
                            $allowed_modes = get_user_meta($user->ID, 'wort_spiel_allowed_modes', true) ?: array();
                            $accuracy = $stats->total_games > 0 ? round(($stats->correct_games / $stats->total_games) * 100, 1) : 0;
                            $is_selected = $selected_user_id == $user->ID;
                        ?>
                        <tr class="<?php echo $is_selected ? 'selected-user' : ''; ?>">
                            <th class="check-column">
                                <input type="checkbox" name="users[]" value="<?php echo $user->ID; ?>" class="user-checkbox">
                            </th>
                            <td class="username column-username column-primary">
                                <div class="user-info">
                                    <?php echo get_avatar($user->ID, 32); ?>
                                    <div class="user-details">
                                        <strong>
                                            <a href="<?php echo add_query_arg('user_id', $user->ID); ?>" class="user-name">
                                                <?php echo esc_html($user->display_name); ?>
                                            </a>
                                        </strong>
                                        <div class="user-email"><?php echo esc_html($user->user_email); ?></div>
                                    </div>
                                </div>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo add_query_arg('user_id', $user->ID); ?>">
                                            <?php _e('Modi verwalten', 'wort-spiel'); ?>
                                        </a> |
                                    </span>
                                    <span class="view">
                                        <a href="<?php echo get_edit_user_link($user->ID); ?>" target="_blank">
                                            <?php _e('Profil bearbeiten', 'wort-spiel'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo number_format($stats->total_games ?? 0); ?></strong>
                                <?php if ($stats->total_games > 0): ?>
                                <div class="game-breakdown">
                                    <?php echo number_format($stats->correct_games); ?> richtig
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($stats->total_games > 0): ?>
                                <span class="accuracy-badge accuracy-<?php echo $accuracy >= 80 ? 'high' : ($accuracy >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $accuracy; ?>%
                                </span>
                                <?php else: ?>
                                <span class="no-data">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="modes-count">
                                    <?php echo count($allowed_modes); ?>/<?php echo count($available_modes); ?>
                                </span>
                                <?php if (count($allowed_modes) > 0): ?>
                                <div class="modes-preview">
                                    <?php 
                                    $preview_modes = array_slice($allowed_modes, 0, 2);
                                    foreach ($preview_modes as $mode) {
                                        echo '<span class="mode-tag">' . esc_html($available_modes[$mode] ?? $mode) . '</span>';
                                    }
                                    if (count($allowed_modes) > 2) {
                                        echo '<span class="mode-tag more">+' . (count($allowed_modes) - 2) . '</span>';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($stats->last_played): ?>
                                <div class="last-played">
                                    <?php echo human_time_diff(strtotime($stats->last_played), current_time('timestamp')); ?>
                                    <?php _e('her', 'wort-spiel'); ?>
                                </div>
                                <div class="last-played-date">
                                    <?php echo date_i18n('d.m.Y H:i', strtotime($stats->last_played)); ?>
                                </div>
                                <?php else: ?>
                                <span class="no-data"><?php _e('Nie gespielt', 'wort-spiel'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- User-Details Sidebar -->
        <?php if ($selected_user): ?>
        <div class="user-details-sidebar">
            <div class="user-header">
                <?php echo get_avatar($selected_user->ID, 64); ?>
                <div class="user-info">
                    <h3><?php echo esc_html($selected_user->display_name); ?></h3>
                    <p><?php echo esc_html($selected_user->user_email); ?></p>
                </div>
            </div>
            
            <!-- Modi-Verwaltung -->
            <div class="user-modes-section">
                <h4><?php _e('Erlaubte Spielmodi', 'wort-spiel'); ?></h4>
                <form method="post" action="">
                    <?php wp_nonce_field('update_user_modes'); ?>
                    <input type="hidden" name="user_id" value="<?php echo $selected_user->ID; ?>">
                    <input type="hidden" name="update_user_modes" value="1">
                    
                    <?php 
                    $user_modes = get_user_meta($selected_user->ID, 'wort_spiel_allowed_modes', true) ?: array();
                    ?>
                    
                    <div class="modes-grid">
                        <?php foreach ($available_modes as $mode_id => $mode_name): ?>
                        <label class="mode-checkbox <?php echo in_array($mode_id, $user_modes) ? 'checked' : ''; ?>">
                            <input type="checkbox" 
                                   name="game_modes[]" 
                                   value="<?php echo esc_attr($mode_id); ?>"
                                   <?php checked(in_array($mode_id, $user_modes)); ?>>
                            <span class="mode-label"><?php echo esc_html($mode_name); ?></span>
                            <?php if (strpos($mode_id, 'learning') !== false): ?>
                            <span class="mode-type learning">📖</span>
                            <?php elseif (strpos($mode_id, 'extra') !== false): ?>
                            <span class="mode-type extra">✨</span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mode-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Modi aktualisieren', 'wort-spiel'); ?>
                        </button>
                        <button type="button" id="select-all-modes" class="button">
                            <?php _e('Alle auswählen', 'wort-spiel'); ?>
                        </button>
                        <button type="button" id="select-none-modes" class="button">
                            <?php _e('Alle abwählen', 'wort-spiel'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Benutzer-Statistiken -->
            <div class="user-stats-section">
                <h4><?php _e('Statistiken', 'wort-spiel'); ?></h4>
                <?php 
                $user_stat = $user_stats[$selected_user->ID];
                if ($user_stat->total_games > 0):
                ?>
                <div class="user-stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($user_stat->total_games); ?></div>
                        <div class="stat-label"><?php _e('Gespielte Wörter', 'wort-spiel'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($user_stat->correct_games); ?></div>
                        <div class="stat-label"><?php _e('Richtig', 'wort-spiel'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo round(($user_stat->correct_games / $user_stat->total_games) * 100, 1); ?>%</div>
                        <div class="stat-label"><?php _e('Erfolgsquote', 'wort-spiel'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo round($user_stat->avg_duration); ?>s</div>
                        <div class="stat-label"><?php _e('Ø Dauer', 'wort-spiel'); ?></div>
                    </div>
                </div>
                
                <!-- Letzte Spiele -->
                <div class="recent-games">
                    <h5><?php _e('Letzte Spiele', 'wort-spiel'); ?></h5>
                    <?php
                    $recent_games = $wpdb->get_results($wpdb->prepare("
                        SELECT target_word, user_input, is_correct, duration, timestamp, game_mode
                        FROM $table_name 
                        WHERE user_id = %d 
                        ORDER BY timestamp DESC 
                        LIMIT 10
                    ", $selected_user->ID));
                    ?>
                    
                    <?php if ($recent_games): ?>
                    <div class="games-list">
                        <?php foreach ($recent_games as $game): ?>
                        <div class="game-item <?php echo $game->is_correct ? 'correct' : 'wrong'; ?>">
                            <div class="game-result">
                                <span class="result-icon"><?php echo $game->is_correct ? '✅' : '❌'; ?></span>
                                <strong><?php echo esc_html($game->target_word); ?></strong>
                                <?php if (!$game->is_correct): ?>
                                → "<?php echo esc_html($game->user_input); ?>"
                                <?php endif; ?>
                            </div>
                            <div class="game-meta">
                                <?php echo esc_html($game->game_mode); ?> | 
                                <?php echo $game->duration; ?>s | 
                                <?php echo human_time_diff(strtotime($game->timestamp), current_time('timestamp')); ?> her
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="no-data"><?php _e('Noch keine Spiele.', 'wort-spiel'); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php else: ?>
                <p class="no-data"><?php _e('Dieser Benutzer hat noch keine Spiele gespielt.', 'wort-spiel'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
.wort-spiel-players-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 20px;
    margin-top: 20px;
}

.players-list {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-details .user-email {
    color: #666;
    font-size: 0.9rem;
}

.accuracy-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.85rem;
}

.accuracy-high { background: #d4edda; color: #155724; }
.accuracy-medium { background: #fff3cd; color: #856404; }
.accuracy-low { background: #f8d7da; color: #721c24; }

.modes-count {
    font-weight: bold;
    color: #0073aa;
}

.modes-preview {
    margin-top: 4px;
}

.mode-tag {
    display: inline-block;
    background: #f0f0f1;
    color: #3c434a;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.75rem;
    margin-right: 4px;
    margin-bottom: 2px;
}

.mode-tag.more {
    background: #0073aa;
    color: white;
}

.last-played {
    font-weight: bold;
}

.last-played-date {
    color: #666;
    font-size: 0.85rem;
}

.selected-user {
    background-color: #e7f3ff !important;
}

.user-details-sidebar {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.user-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-header h3 {
    margin: 0;
    color: #23282d;
}

.user-header p {
    margin: 5px 0 0 0;
    color: #666;
}

.user-modes-section,
.user-stats-section {
    padding: 20px;
    border-bottom: 1px solid #f0f0f1;
}

.user-modes-section:last-child,
.user-stats-section:last-child {
    border-bottom: none;
}

.modes-grid {
    display: grid;
    gap: 8px;
    margin: 15px 0;
}

.mode-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.mode-checkbox:hover {
    background: #f8f9fa;
}

.mode-checkbox.checked {
    background: #e7f3ff;
    border-color: #0073aa;
}

.mode-label {
    flex: 1;
    font-weight: 500;
}

.mode-type {
    font-size: 0.8rem;
}

.mode-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.user-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 15px 0;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.stat-label {
    color: #666;
    font-size: 0.85rem;
    margin-top: 5px;
}

.games-list {
    max-height: 300px;
    overflow-y: auto;
}

.game-item {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 8px;
}

.game-item.correct {
    border-left: 4px solid #46b450;
    background: #f8fff8;
}

.game-item.wrong {
    border-left: 4px solid #dc3232;
    background: #fffbfb;
}

.game-result {
    font-weight: bold;
    margin-bottom: 4px;
}

.game-meta {
    font-size: 0.85rem;
    color: #666;
}

.no-data {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

@media (max-width: 1200px) {
    .wort-spiel-players-layout {
        grid-template-columns: 1fr;
    }
    
    .user-details-sidebar {
        order: -1;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Alle auswählen/abwählen
    $('#cb-select-all').on('change', function() {
        $('.user-checkbox').prop('checked', this.checked);
    });
    
    // Modi alle auswählen/abwählen
    $('#select-all-modes').on('click', function(e) {
        e.preventDefault();
        $('.mode-checkbox input[type="checkbox"]').prop('checked', true);
        $('.mode-checkbox').addClass('checked');
    });
    
    $('#select-none-modes').on('click', function(e) {
        e.preventDefault();
        $('.mode-checkbox input[type="checkbox"]').prop('checked', false);
        $('.mode-checkbox').removeClass('checked');
    });
    
    // Mode-Checkbox visuelles Feedback
    $('.mode-checkbox input[type="checkbox"]').on('change', function() {
        if (this.checked) {
            $(this).closest('.mode-checkbox').addClass('checked');
        } else {
            $(this).closest('.mode-checkbox').removeClass('checked');
        }
    });
    
    // Bulk-Aktionen
    $('#apply-bulk-action').on('click', function() {
        var action = $('#bulk-mode-action').val();
        var checkedUsers = $('.user-checkbox:checked');
        
        if (!action || checkedUsers.length === 0) {
            alert('<?php _e('Bitte wählen Sie eine Aktion und mindestens einen Benutzer aus.', 'wort-spiel'); ?>');
            return;
        }
        
        // Hier würde normalerweise ein AJAX-Call gemacht
        alert('<?php _e('Bulk-Aktion wird in der nächsten Version implementiert.', 'wort-spiel'); ?>');
    });
});
</script>