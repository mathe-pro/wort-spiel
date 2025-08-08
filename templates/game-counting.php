<?php
/**
 * Template für das Zahlen-Spiel (1-9)
 * 
 * @package WortSpiel
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Spielmodus aus URL-Parameter oder Shortcode-Attribut
$game_mode = isset($_GET['game_mode']) ? sanitize_text_field($_GET['game_mode']) : ($atts['mode'] ?? 'counting');
$current_user = wp_get_current_user();

// Prüfen ob User diesen Modus spielen darf
$allowed_modes = get_user_meta(get_current_user_id(), 'wort_spiel_allowed_modes', true);
if (empty($allowed_modes)) {
    $allowed_modes = get_option('wort_spiel_default_modes', array('animals', 'nature'));
}

if (!in_array($game_mode, $allowed_modes)) {
    echo '<p>' . __('Sie haben keine Berechtigung für diesen Spielmodus.', 'wort-spiel') . '</p>';
    return;
}
?>

<div id="wort-spiel-counting-container" class="wort-spiel-counting">
    
    <!-- Zurück Button -->
    <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
        ← <?php _e('Zurück zum Menü', 'wort-spiel'); ?>
    </button>
    
    <!-- Spiel Header -->
    <div class="game-header">
        <h2><?php _e('Zahlen-Spiel (1-9)', 'wort-spiel'); ?></h2>
        <div class="player-info">
            <?php printf(__('Spieler: %s', 'wort-spiel'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?>
        </div>
        <div class="game-instructions">
            <?php _e('Klicke die Zahlen von 1 bis 9 in der richtigen Reihenfolge!', 'wort-spiel'); ?>
        </div>
    </div>
    
    <!-- Hauptspiel-Container -->
    <div id="container">
        <div id="playfield-wrapper">
            <div id="playfield"></div>
        </div>
        <div id="sidebar">
            <h4><?php _e('Runden', 'wort-spiel'); ?></h4>
            <table id="scoreboard">
                <tbody></tbody>
            </table>
        </div>
    </div>
    
</div>

<style>
.wort-spiel-counting {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    user-select: none;
    -webkit-user-select: none;
    touch-action: manipulation;
    position: relative;
}

.back-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
    z-index: 10;
}

.back-btn:hover {
    background: #545b62;
}

.game-header {
    text-align: center;
    margin-bottom: 20px;
    padding-top: 40px;
}

.game-header h2 {
    color: #2c3e50;
    font-size: 2rem;
    margin-bottom: 10px;
}

.player-info {
    color: #6c757d;
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.game-instructions {
    color: #495057;
    font-size: 1rem;
    background: #e9ecef;
    padding: 10px 20px;
    border-radius: 8px;
    display: inline-block;
}

#container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: flex-start;
    gap: 20px;
    padding: 10px;
    min-height: 500px;
}

#playfield-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    flex: 1;
}

#playfield {
    position: relative;
    width: 100%;
    max-width: 800px;
    height: 500px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 1px solid #ddd;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);
}

button.num-btn {
    position: absolute;
    width: 80px;
    height: 80px;
    border: none;
    border-radius: 50%;
    font-size: 2rem;
    font-weight: bold;
    background: white;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

button.num-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

button.correct { 
    background: #28a745; 
    color: #fff;
    animation: pulse-green 0.6s ease-in-out;
}

button.wrong { 
    background: #dc3545; 
    color: #fff;
    animation: shake 0.6s ease-in-out;
}

@keyframes pulse-green {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); box-shadow: 0 0 20px rgba(40, 167, 69, 0.5); }
    100% { transform: scale(1); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

#sidebar {
    width: 150px;
    max-height: 500px;
    overflow-y: auto;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 20px 15px;
}

#sidebar h4 {
    margin: 0 0 15px 0;
    text-align: center;
    color: #2c3e50;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td {
    height: 40px;
    text-align: center;
    font-weight: bold;
    border-bottom: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 2px;
}

tr:last-child td { 
    border-bottom: none; 
}

.error-cell::before {
    content: "R" counter(table-row) ": ";
    font-size: 0.8rem;
}

.green  { background-color: #28a745; color: #fff; }
.yellow { background-color: #ffc107; color: #000; }
.orange { background-color: #fd7e14; color: #fff; }
.red    { background-color: #dc3545; color: #fff; }

/* Responsive Design */
@media (max-width: 768px) {
    .wort-spiel-counting {
        padding: 15px 10px;
    }
    
    .game-header {
        padding-top: 50px;
    }
    
    .game-header h2 {
        font-size: 1.5rem;
    }
    
    #container {
        flex-direction: column;
        align-items: center;
    }
    
    #playfield {
        height: 400px;
        max-width: 95vw;
    }
    
    button.num-btn {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    #sidebar {
        width: 100%;
        max-width: 300px;
        max-height: 200px;
        order: -1;
    }
}

@media (max-width: 480px) {
    #playfield {
        height: 300px;
    }
    
    button.num-btn {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Nur laden wenn Counting-Container vorhanden
    if (!$('#wort-spiel-counting-container').length) return;
    
    console.log('Zahlen-Spiel initialisiert');
    
    const TOTAL_ROUNDS = 10;
    const playfield = document.getElementById('playfield');
    const scoreboard = document.querySelector('#scoreboard tbody');

    let expected = 1;
    let errors = 0;
    let roundNumber = 1;
    let gameOver = false;
    let sessionId = generateSessionId();
    let roundStartTime = new Date();
    let wrongClicks = []; // Speichert alle falschen Klicks pro Runde

    // Session-ID generieren
    function generateSessionId() {
        const now = new Date();
        return now.toISOString().replace(/[:.-]/g, '_');
    }

    // Scoreboard initialisieren
    function initScoreboard() {
        scoreboard.innerHTML = '';
        for (let i = 1; i <= TOTAL_ROUNDS; i++) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="error-cell"></td>`;
            scoreboard.appendChild(tr);
        }
    }

    function updateScoreCell(index, errors) {
        const cell = document.querySelectorAll('.error-cell')[index];
        cell.textContent = errors;
        cell.className = 'error-cell';
        if (errors === 0) cell.classList.add('green');
        else if (errors === 1) cell.classList.add('yellow');
        else if (errors === 2) cell.classList.add('orange');
        else cell.classList.add('red');
    }

    // Zahlen-Buttons erstellen
    for (let i = 1; i <= 9; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.dataset.number = i;
        btn.className = 'num-btn';
        playfield.appendChild(btn);
    }
    const numButtons = [...document.querySelectorAll('.num-btn')];

    // Layout-Definitionen
    const layouts = [
        [{"left":24.7,"top":20.1},{"left":73.7,"top":7.0},{"left":63.2,"top":66.8},{"left":82.3,"top":61.9},{"left":7.7,"top":6.9},{"left":72.5,"top":32.4},{"left":33.3,"top":77.4},{"left":81.7,"top":85.4},{"left":56.3,"top":44.5}],
        [{"left":62.5,"top":60.6},{"left":28.5,"top":66.8},{"left":30.0,"top":31.8},{"left":85.5,"top":16.0},{"left":82.8,"top":55.5},{"left":76.0,"top":32.4},{"left":80.8,"top":84.0},{"left":49.5,"top":47.1},{"left":42.2,"top":8.5}],
        [{"left":25.5,"top":34.2},{"left":8.0,"top":61.5},{"left":30.8,"top":86.6},{"left":45.7,"top":6.9},{"left":77.5,"top":71.2},{"left":74.0,"top":55.4},{"left":33.2,"top":56.5},{"left":23.7,"top":71.0},{"left":91.7,"top":89.8}],
        [{"left":43.5,"top":81.6},{"left":75.8,"top":82.2},{"left":61.0,"top":66.6},{"left":82.3,"top":52.2},{"left":89.2,"top":17.4},{"left":33.3,"top":19.4},{"left":47.0,"top":36.5},{"left":59.3,"top":7.5},{"left":79.5,"top":35.0}],
        [{"left":51.5,"top":32.5},{"left":41.5,"top":46.6},{"left":73.8,"top":50.4},{"left":43.8,"top":18.2},{"left":19.5,"top":21.5},{"left":8.8,"top":72.5},{"left":59.3,"top":68.1},{"left":89.0,"top":64.8},{"left":82.8,"top":10.6}]
    ];

    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    function applyLayout(layout) {
        const shuffledButtons = [...numButtons];
        shuffle(shuffledButtons);

        shuffledButtons.forEach((btn, idx) => {
            btn.style.left = layout[idx].left + '%';
            btn.style.top = layout[idx].top + '%';
        });
    }

    function chooseRandomLayout() { 
        applyLayout(layouts[Math.floor(Math.random() * layouts.length)]); 
    }

    // Spiel-Logik
    playfield.addEventListener('click', e => {
        if (gameOver) return;
        const btn = e.target.closest('.num-btn');
        if (!btn) return;
        const value = +btn.dataset.number;

        if (value === expected) {
            // Richtig geklickt
            btn.classList.remove('wrong');
            btn.classList.add('correct');
            numButtons.forEach(b => b.classList.remove('wrong'));
            expected++;

            if (expected > 9) {
                // Runde beendet
                updateScoreCell(roundNumber - 1, errors);
                
                // Runden-Ergebnis speichern
                saveRoundResult();

                setTimeout(() => {
                    numButtons.forEach(b => b.classList.remove('correct'));
                    if (roundNumber >= TOTAL_ROUNDS) {
                        gameOver = true;
                        launchConfetti();
                        saveGameSession(); // Gesamtes Spiel speichern
                    } else {
                        roundNumber++;
                        resetForNextRound();
                    }
                }, 700);
            }

        } else {
            // Falsch geklickt
            if (!btn.classList.contains('correct')) {
                if (btn.classList.contains('wrong')) {
                    btn.classList.remove('wrong');
                } else {
                    btn.classList.add('wrong');
                    errors++;
                    
                    // Falschen Klick registrieren
                    wrongClicks.push({
                        clickedNumber: value,
                        expectedNumber: expected,
                        timestamp: new Date().toISOString(),
                        position: { left: btn.style.left, top: btn.style.top }
                    });
                    
                    console.log('Falscher Klick registriert:', value, 'erwartet:', expected);
                }
            }
        }
    });

    function resetForNextRound() {
        expected = 1;
        errors = 0;
        wrongClicks = []; // Reset für neue Runde
        roundStartTime = new Date();
        chooseRandomLayout();
    }

    function launchConfetti() {
        if (typeof confetti !== 'function') return;
        const end = Date.now() + 5000;
        (function frame() {
            confetti({
                particleCount: 25,
                spread: 80,
                startVelocity: 35,
                origin: { x: Math.random(), y: Math.random() * 0.6 }
            });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    }

    // Runden-Ergebnis speichern
    function saveRoundResult() {
        const roundEndTime = new Date();
        const duration = Math.round((roundEndTime - roundStartTime) / 1000);
        
        const roundData = {
            sessionId: sessionId,
            gameMode: 'counting',
            roundNumber: roundNumber,
            errors: errors,
            wrongClicks: wrongClicks,
            duration: duration,
            timestamp: roundEndTime.toISOString()
        };
        
        console.log('Speichere Runden-Ergebnis:', roundData);
        
        // An WordPress senden
        if (typeof wortSpielAjax !== 'undefined') {
            $.ajax({
                url: wortSpielAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wort_spiel_save_counting_round',
                    nonce: wortSpielAjax.nonce,
                    round_data: JSON.stringify(roundData)
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Runden-Ergebnis gespeichert');
                    } else {
                        console.error('Fehler beim Speichern:', response.data?.message);
                    }
                },
                error: function() {
                    console.error('AJAX-Fehler beim Speichern der Runde');
                }
            });
        } else {
            // Fallback: LocalStorage
            let localData = JSON.parse(localStorage.getItem('counting_game_data') || '[]');
            localData.push(roundData);
            localStorage.setItem('counting_game_data', JSON.stringify(localData));
        }
    }

    // Gesamtes Spiel-Session speichern
    function saveGameSession() {
        console.log('Spiel beendet - speichere Session');
        
        // Session-Zusammenfassung
        const sessionData = {
            sessionId: sessionId,
            gameMode: 'counting',
            totalRounds: TOTAL_ROUNDS,
            completedRounds: roundNumber,
            gameCompleted: true,
            timestamp: new Date().toISOString()
        };
        
        if (typeof wortSpielAjax !== 'undefined') {
            $.ajax({
                url: wortSpielAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wort_spiel_save_counting_session',
                    nonce: wortSpielAjax.nonce,
                    session_data: JSON.stringify(sessionData)
                },
                success: function(response) {
                    console.log('Spiel-Session gespeichert');
                },
                error: function() {
                    console.error('Fehler beim Speichern der Session');
                }
            });
        }
    }

    // Zurück zum Menü
    $('#back-to-menu-btn').on('click', function() {
        const url = new URL(window.location.href);
        url.searchParams.delete('game_mode');
        url.hash = '';
        window.location.href = url.toString();
    });

    // Spiel initialisieren
    chooseRandomLayout();
    initScoreboard();
});
</script>