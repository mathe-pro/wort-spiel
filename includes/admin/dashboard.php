<?php
/**
 * Admin Dashboard
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

// Statistiken abrufen
global $wpdb;
$table_name = $wpdb->prefix . 'wort_spiel_results';

// Gesamt-Statistiken
$total_stats = $wpdb->get_row("
    SELECT 
        COUNT(*) as total_games,
        COUNT(DISTINCT user_id) as total_players,
        SUM(is_correct) as correct_games,
        AVG(duration) as avg_duration
    FROM $table_name
");

$accuracy = $total_stats->total_games > 0 ? round(($total_stats->correct_games / $total_stats->total_games) * 100, 1) : 0;

// Top-Spieler
$top_players = $wpdb->get_results("
    SELECT 
        u.display_name,
        COUNT(*) as total_games,
        SUM(r.is_correct) as correct_games,
        ROUND((SUM(r.is_correct) / COUNT(*)) * 100, 1) as accuracy
    FROM $table_name r
    JOIN {$wpdb->users} u ON r.user_id = u.ID
    GROUP BY r.user_id, u.display_name
    ORDER BY accuracy DESC, total_games DESC
    LIMIT 10
");

// Aktivität der letzten 7 Tage
$recent_activity = $wpdb->get_results("
    SELECT 
        DATE(timestamp) as game_date,
        COUNT(*) as games_count,
        SUM(is_correct) as correct_count
    FROM $table_name 
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(timestamp)
    ORDER BY game_date DESC
");

// Beliebteste Kategorien
$popular_categories = $wpdb->get_results("
    SELECT 
        game_mode,
        COUNT(*) as play_count,
        ROUND((SUM(is_correct) / COUNT(*)) * 100, 1) as success_rate
    FROM $table_name
    GROUP BY game_mode
    ORDER BY play_count DESC
");

// Häufige Fehler
$common_mistakes = $wpdb->get_results("
    SELECT 
        target_word,
        user_input,
        COUNT(*) as mistake_count
    FROM $table_name 
    WHERE is_correct = 0 
    GROUP BY target_word, user_input
    ORDER BY mistake_count DESC
    LIMIT 10
");
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-games"></span>
        <?php _e('Wort-Spiel Dashboard', 'wort-spiel'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Übersichts-Karten -->
    <div class="wort-spiel-stats-grid">
        <div class="wort-spiel-stat-card">
            <div class="stat-icon">🎮</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($total_stats->total_games ?? 0); ?></div>
                <div class="stat-label"><?php _e('Gespielte Wörter', 'wort-spiel'); ?></div>
            </div>
        </div>
        
        <div class="wort-spiel-stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($total_stats->total_players ?? 0); ?></div>
                <div class="stat-label"><?php _e('Aktive Spieler', 'wort-spiel'); ?></div>
            </div>
        </div>
        
        <div class="wort-spiel-stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $accuracy; ?>%</div>
                <div class="stat-label"><?php _e('Erfolgsquote', 'wort-spiel'); ?></div>
            </div>
        </div>
        
        <div class="wort-spiel-stat-card">
            <div class="stat-icon">⏱️</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo round($total_stats->avg_duration ?? 0); ?>s</div>
                <div class="stat-label"><?php _e('Ø Dauer', 'wort-spiel'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Dashboard-Grid -->
    <div class="wort-spiel-dashboard-grid">
        
        <!-- Top-Spieler -->
        <div class="wort-spiel-dashboard-widget">
            <h3><?php _e('🏆 Top-Spieler', 'wort-spiel'); ?></h3>
            <?php if (empty($top_players)): ?>
                <p class="no-data"><?php _e('Noch keine Spieler-Daten vorhanden.', 'wort-spiel'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Spieler', 'wort-spiel'); ?></th>
                            <th><?php _e('Spiele', 'wort-spiel'); ?></th>
                            <th><?php _e('Richtig', 'wort-spiel'); ?></th>
                            <th><?php _e('Quote', 'wort-spiel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_players as $player): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($player->display_name); ?></strong>
                            </td>
                            <td><?php echo number_format($player->total_games); ?></td>
                            <td><?php echo number_format($player->correct_games); ?></td>
                            <td>
                                <span class="accuracy-badge accuracy-<?php echo $player->accuracy >= 80 ? 'high' : ($player->accuracy >= 60 ? 'medium' : 'low'); ?>">
                                    <?php echo $player->accuracy; ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Aktivität -->
        <div class="wort-spiel-dashboard-widget">
            <h3><?php _e('📈 Aktivität (7 Tage)', 'wort-spiel'); ?></h3>
            <?php if (empty($recent_activity)): ?>
                <p class="no-data"><?php _e('Keine Aktivität in den letzten 7 Tagen.', 'wort-spiel'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Datum', 'wort-spiel'); ?></th>
                            <th><?php _e('Spiele', 'wort-spiel'); ?></th>
                            <th><?php _e('Richtig', 'wort-spiel'); ?></th>
                            <th><?php _e('Quote', 'wort-spiel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_activity as $day): ?>
                        <?php 
                            $day_accuracy = $day->games_count > 0 ? round(($day->correct_count / $day->games_count) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?php echo date_i18n('d.m.Y', strtotime($day->game_date)); ?></td>
                            <td><?php echo number_format($day->games_count); ?></td>
                            <td><?php echo number_format($day->correct_count); ?></td>
                            <td><?php echo $day_accuracy; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Beliebte Kategorien -->
        <div class="wort-spiel-dashboard-widget">
            <h3><?php _e('🎯 Beliebte Kategorien', 'wort-spiel'); ?></h3>
            <?php if (empty($popular_categories)): ?>
                <p class="no-data"><?php _e('Noch keine Kategorie-Daten vorhanden.', 'wort-spiel'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Kategorie', 'wort-spiel'); ?></th>
                            <th><?php _e('Gespielt', 'wort-spiel'); ?></th>
                            <th><?php _e('Erfolg', 'wort-spiel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popular_categories as $category): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($category->game_mode); ?></strong>
                            </td>
                            <td><?php echo number_format($category->play_count); ?></td>
                            <td><?php echo $category->success_rate; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Häufige Fehler -->
        <div class="wort-spiel-dashboard-widget">
            <h3><?php _e('❌ Häufige Fehler', 'wort-spiel'); ?></h3>
            <?php if (empty($common_mistakes)): ?>
                <p class="no-data"><?php _e('Keine Fehler-Daten vorhanden.', 'wort-spiel'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Zielwort', 'wort-spiel'); ?></th>
                            <th><?php _e('Eingabe', 'wort-spiel'); ?></th>
                            <th><?php _e('Häufigkeit', 'wort-spiel'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($common_mistakes as $mistake): ?>
                        <tr>
                            <td><strong><?php echo esc_html($mistake->target_word); ?></strong></td>
                            <td class="mistake-input"><?php echo esc_html($mistake->user_input); ?></td>
                            <td><?php echo number_format($mistake->mistake_count); ?>x</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
    </div>
    
    <!-- Schnellaktionen -->
    <div class="wort-spiel-quick-actions">
        <h3><?php _e('Schnellaktionen', 'wort-spiel'); ?></h3>
        <div class="quick-actions-grid">
            <a href="<?php echo admin_url('admin.php?page=wort-spiel-players'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Spieler verwalten', 'wort-spiel'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wort-spiel-stats'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('Detaillierte Statistiken', 'wort-spiel'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=wort-spiel-settings'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Einstellungen', 'wort-spiel'); ?>
            </a>
            <button id="export-all-data" class="button button-secondary button-hero">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Daten exportieren', 'wort-spiel'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.wort-spiel-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.wort-spiel-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #0073aa;
    line-height: 1;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 5px;
}

.wort-spiel-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.wort-spiel-dashboard-widget {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.wort-spiel-dashboard-widget h3 {
    background: #f8f9fa;
    margin: 0;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    font-size: 1.1rem;
}

.wort-spiel-dashboard-widget .wp-list-table {
    border: none;
    margin: 0;
}

.no-data {
    padding: 20px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.accuracy-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.9rem;
}

.accuracy-high { background: #d4edda; color: #155724; }
.accuracy-medium { background: #fff3cd; color: #856404; }
.accuracy-low { background: #f8d7da; color: #721c24; }

.mistake-input {
    color: #dc3545;
    font-weight: bold;
}

.wort-spiel-quick-actions {
    margin-top: 30px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.quick-actions-grid .button-hero {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 15px 20px;
    text-decoration: none;
}

@media (max-width: 768px) {
    .wort-spiel-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .wort-spiel-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#export-all-data').on('click', function() {
        window.location.href = '<?php echo admin_url('admin-ajax.php?action=wort_spiel_export_all_data&nonce=' . wp_create_nonce('wort_spiel_admin_nonce')); ?>';
    });
});
</script>