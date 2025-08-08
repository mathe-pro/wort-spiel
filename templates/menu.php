<?php
/**
 * Template für das Spiel-Menü
 * 
 * @package WortSpiel
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// WICHTIG: Prüfen ob Spiel-Modus gewählt wurde
$game_mode = isset($_GET['game_mode']) ? sanitize_text_field($_GET['game_mode']) : '';

// Wenn Game-Mode gesetzt ist, lade das entsprechende Template
if (!empty($game_mode)) {
    // Game-Mode für Template verfügbar machen
    $atts = array('mode' => $game_mode);
    
    // Prüfen ob User diesen Modus spielen darf
    $allowed_modes = get_user_meta(get_current_user_id(), 'wort_spiel_allowed_modes', true);
    if (empty($allowed_modes)) {
        $allowed_modes = get_option('wort_spiel_default_modes', array('animals', 'nature'));
    }

    if (in_array($game_mode, $allowed_modes)) {
        // Spezielle Templates für verschiedene Spielarten
        if ($game_mode === 'counting') {
            include WORT_SPIEL_PLUGIN_PATH . 'templates/game-counting.php';
            return;
        } elseif (strpos($game_mode, '-learning') !== false) {
            include WORT_SPIEL_PLUGIN_PATH . 'templates/game-learning.php';
            return;
        } elseif (strpos($game_mode, '-extra') !== false) {
            include WORT_SPIEL_PLUGIN_PATH . 'templates/game-extra.php';
            return;
        } else {
            // Standard Wort-Spiel Template
            include WORT_SPIEL_PLUGIN_PATH . 'templates/game.php';
            return;
        }
    } else {
        echo '<div class="wort-spiel-error">❌ Sie haben keine Berechtigung für diesen Spielmodus.</div>';
        return;
    }
}

// Normales Menü anzeigen (wenn kein game_mode Parameter)
$current_user = wp_get_current_user();
$user_name = $current_user->display_name;
?>

<div id="wort-spiel-container" class="wort-spiel-menu">
    <div class="wort-spiel-header">
        <h1><?php _e('Wort-Spiel', 'wort-spiel'); ?></h1>
        <div class="welcome-message">
            <?php printf(__('Hallo <strong>%s</strong>!', 'wort-spiel'), esc_html($user_name)); ?>
        </div>
    </div>
    
    <div id="loading-message" class="loading-message">
        <?php _e('Lade deine Spielmodi...', 'wort-spiel'); ?>
    </div>
    
    <div id="game-modes-grid" class="game-modes-grid" style="display: none;">
        <!-- Wird per JavaScript gefüllt -->
    </div>
    
    <div id="no-modes-message" class="no-modes-message" style="display: none;">
        <h3><?php _e('🚫 Keine Spielmodi freigeschaltet', 'wort-spiel'); ?></h3>
        <p><?php _e('Dein Administrator hat noch keine Spielmodi für dich aktiviert.', 'wort-spiel'); ?></p>
        <p><?php _e('Wende dich an deinen Admin!', 'wort-spiel'); ?></p>
    </div>
    
    <div class="menu-actions">
        <button id="show-history-btn" class="wort-spiel-btn secondary">
            📊 <?php _e('Meine Statistiken', 'wort-spiel'); ?>
        </button>
        
        <?php if (current_user_can('manage_wort_spiel')): ?>
        <a href="<?php echo admin_url('admin.php?page=wort-spiel-admin'); ?>" class="wort-spiel-btn admin">
            ⚙️ <?php _e('Admin-Bereich', 'wort-spiel'); ?>
        </a>
        <?php endif; ?>
        
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="wort-spiel-btn logout">
            <?php _e('Abmelden', 'wort-spiel'); ?>
        </a>
    </div>
    
    <!-- History Modal -->
    <div id="history-modal" class="wort-spiel-modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><?php _e('Meine Statistiken', 'wort-spiel'); ?></h2>
            <div id="user-stats-content">
                <!-- Wird per AJAX geladen -->
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const wortSpielMenu = {
        
        init: function() {
            this.loadUserModes();
            this.bindEvents();
        },
        
        bindEvents: function() {
            // History anzeigen
            $('#show-history-btn').on('click', this.showHistory.bind(this));
            
            // Modal schließen
            $('.close-modal').on('click', function() {
                $('#history-modal').hide();
            });
            
            // Modal schließen bei Klick außerhalb
            $('#history-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        },
        
        loadUserModes: function() {
            $.ajax({
                url: wortSpielAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wort_spiel_get_user_modes',
                    nonce: wortSpielAjax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.allowed_modes.length > 0) {
                        wortSpielMenu.displayGameModes(response.data.allowed_modes);
                    } else {
                        wortSpielMenu.showNoModes();
                    }
                },
                error: function() {
                    wortSpielMenu.showError();
                }
            });
        },
        
        displayGameModes: function(allowedModes) {
            // Alle verfügbaren Spielmodi (aus dem Plugin)
            const allGameModes = {
                'animals': {
                    id: 'animals',
                    title: '<?php _e('Tiere', 'wort-spiel'); ?>',
                    description: '<?php _e('Katze, Hund, Vogel und mehr', 'wort-spiel'); ?>',
                    icon: '🐱'
                },
                'animals-learning': {
                    id: 'animals-learning',
                    title: '<?php _e('Tiere (Lernmodus)', 'wort-spiel'); ?>',
                    description: '<?php _e('Mit sichtbarem Wort - Katze, Hund, Vogel und mehr', 'wort-spiel'); ?>',
                    icon: '🐱📖'
                },
                'nature': {
                    id: 'nature',
                    title: '<?php _e('Natur', 'wort-spiel'); ?>',
                    description: '<?php _e('Baum, Blume, Sonne und mehr', 'wort-spiel'); ?>',
                    icon: '🌳'
                },
                'nature-learning': {
                    id: 'nature-learning',
                    title: '<?php _e('Natur (Lernmodus)', 'wort-spiel'); ?>',
                    description: '<?php _e('Mit sichtbarem Wort - Baum, Blume, Sonne und mehr', 'wort-spiel'); ?>',
                    icon: '🌳📖'
                },
                'colors': {
                    id: 'colors',
                    title: '<?php _e('Farben', 'wort-spiel'); ?>',
                    description: '<?php _e('Rot, Blau, Grün und mehr', 'wort-spiel'); ?>',
                    icon: '🎨'
                },
                'colors-learning': {
                    id: 'colors-learning',
                    title: '<?php _e('Farben (Lernmodus)', 'wort-spiel'); ?>',
                    description: '<?php _e('Mit sichtbarem Wort - Rot, Blau, Grün und mehr', 'wort-spiel'); ?>',
                    icon: '🎨📖'
                },
                'food': {
                    id: 'food',
                    title: '<?php _e('Essen', 'wort-spiel'); ?>',
                    description: '<?php _e('Brot, Käse, Apfel und mehr', 'wort-spiel'); ?>',
                    icon: '🍎'
                },
                'food-learning': {
                    id: 'food-learning',
                    title: '<?php _e('Essen (Lernmodus)', 'wort-spiel'); ?>',
                    description: '<?php _e('Mit sichtbarem Wort - Brot, Käse, Apfel und mehr', 'wort-spiel'); ?>',
                    icon: '🍎📖'
                },
                'food-extra': {
                    id: 'food-extra',
                    title: '<?php _e('Essen (Extra)', 'wort-spiel'); ?>',
                    description: '<?php _e('Erweiterte Essen-Wörter mit besonderen Features', 'wort-spiel'); ?>',
                    icon: '🍎✨'
                },

                    'counting': {
                    id: 'counting',
                    title: '<?php _e('Zählen 1-9'); ?>',
                    description: '<?php _e('Erweiterte Essen-Wörter mit besonderen Features', 'wort-spiel'); ?>',
                    icon: '🍎✨'
                }

            };
            
            let modesHtml = '';
            
            allowedModes.forEach(function(modeId) {
                const mode = allGameModes[modeId];
                if (mode) {
                    modesHtml += `
                        <div class="game-mode-card" data-mode="${mode.id}">
                            <div class="mode-icon">${mode.icon}</div>
                            <div class="mode-title">${mode.title}</div>
                            <div class="mode-description">${mode.description}</div>
                        </div>
                    `;
                }
            });
            
            $('#game-modes-grid').html(modesHtml);
            $('#loading-message').hide();
            $('#game-modes-grid').show();
            
            // Click-Handler für Spielmodi
            $('.game-mode-card').on('click', function() {
                const modeId = $(this).data('mode');
                wortSpielMenu.startGame(modeId);
            });
        },
        
        startGame: function(modeId) {
            // Hier können wir entweder zu einer neuen Seite weiterleiten
            // oder das Spiel direkt auf der aktuellen Seite laden
            
            // Option 1: Weiterleitung zu Spiel-Seite
            const gameUrl = new URL(window.location.href);
            gameUrl.searchParams.set('game_mode', modeId);
            gameUrl.hash = 'game';
            window.location.href = gameUrl.toString();
            
            // Option 2: Spiel inline laden (für Single-Page-Experience)
            // this.loadGameInline(modeId);
        },
        
        showHistory: function() {
            $('#history-modal').show();
            this.loadUserHistory();
        },
        
        loadUserHistory: function() {
            $('#user-stats-content').html('<div class="loading"><?php _e('Lade Statistiken...', 'wort-spiel'); ?></div>');
            
            $.ajax({
                url: wortSpielAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wort_spiel_get_game_history',
                    nonce: wortSpielAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wortSpielMenu.displayUserStats(response.data.history);
                    } else {
                        $('#user-stats-content').html('<p><?php _e('Fehler beim Laden der Statistiken.', 'wort-spiel'); ?></p>');
                    }
                },
                error: function() {
                    $('#user-stats-content').html('<p><?php _e('Verbindungsfehler.', 'wort-spiel'); ?></p>');
                }
            });
        },
        
        displayUserStats: function(history) {
            if (!history || history.length === 0) {
                $('#user-stats-content').html('<p><?php _e('Noch keine Spiele gespielt.', 'wort-spiel'); ?></p>');
                return;
            }
            
            // Statistiken berechnen
            const stats = this.calculateStats(history);
            
            let statsHtml = `
                <div class="stats-overview">
                    <div class="stat-item">
                        <div class="stat-value">${stats.totalGames}</div>
                        <div class="stat-label"><?php _e('Gespielte Wörter', 'wort-spiel'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.accuracy}%</div>
                        <div class="stat-label"><?php _e('Erfolgsquote', 'wort-spiel'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">${stats.avgDuration}s</div>
                        <div class="stat-label"><?php _e('Ø Dauer', 'wort-spiel'); ?></div>
                    </div>
                </div>
                
                <h3><?php _e('Letzte Spiele', 'wort-spiel'); ?></h3>
                <div class="recent-games">
            `;
            
            history.slice(0, 10).forEach(function(game) {
                const statusIcon = game.is_correct == 1 ? '✅' : '❌';
                const statusClass = game.is_correct == 1 ? 'correct' : 'wrong';
                const date = new Date(game.timestamp).toLocaleDateString('de-DE');
                const time = new Date(game.timestamp).toLocaleTimeString('de-DE');
                
                statsHtml += `
                    <div class="game-history-item ${statusClass}">
                        <div class="game-result">
                            ${statusIcon} <strong>${game.target_word}</strong>
                            ${game.is_correct != 1 ? ` → "${game.user_input}"` : ''}
                        </div>
                        <div class="game-meta">
                            ${date} ${time} | ${game.duration}s | ${game.game_mode}
                        </div>
                    </div>
                `;
            });
            
            statsHtml += '</div>';
            
            $('#user-stats-content').html(statsHtml);
        },
        
        calculateStats: function(history) {
            const totalGames = history.length;
            const correctGames = history.filter(g => g.is_correct == 1).length;
            const accuracy = totalGames > 0 ? Math.round((correctGames / totalGames) * 100) : 0;
            const avgDuration = totalGames > 0 ? 
                Math.round(history.reduce((sum, g) => sum + parseInt(g.duration), 0) / totalGames) : 0;
            
            return {
                totalGames,
                correctGames,
                accuracy,
                avgDuration
            };
        },
        
        showNoModes: function() {
            $('#loading-message').hide();
            $('#no-modes-message').show();
        },
        
        showError: function() {
            $('#loading-message').html('<?php _e('Fehler beim Laden der Spielmodi.', 'wort-spiel'); ?>');
        }
    };
    
    // Plugin initialisieren
    wortSpielMenu.init();
});
</script>