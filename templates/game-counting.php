<?php
/**
 * Template für das Zahlen-Spiel (1-9)
 * NEUE 3-BEREICHE LAYOUT VERSION
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

<div id="wort-spiel-counting-container" class="wort-spiel-game">
    
    <!-- ===== HEADER ===== -->
    <div class="game-header">
        <div class="header-left">
            <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
                ← <?php _e('Zurück', 'wort-spiel'); ?>
            </button>
            <h2><?php _e('Zahlen 1-9', 'wort-spiel'); ?></h2>
        </div>
        <div class="header-right">
            <div class="player-info">
                <?php printf(__('Spieler: %s', 'wort-spiel'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?>
            </div>
            <!-- Kein Audio-Button bei Counting-Spiel -->
        </div>
    </div>
    
    <!-- ===== GAME-CONTENT ===== -->
    <div class="game-content">
        
        <!-- Anleitung -->
        <div class="game-instructions">
            <?php _e('Klicke die Zahlen von 1 bis 9 in der richtigen Reihenfolge!', 'wort-spiel'); ?>
        </div>
        
        <!-- Hauptspiel-Container -->
        <div id="counting-container">
            
            <!-- Playfield -->
            <div id="playfield-wrapper">
                <div id="playfield" class="counting-playfield">
                    <!-- Zahlen-Buttons werden hier generiert -->
                </div>
            </div>
            
            <!-- Sidebar -->
            <div id="sidebar" class="counting-sidebar">
                <h4><?php _e('Runden', 'wort-spiel'); ?></h4>
                <table id="scoreboard">
                    <tbody></tbody>
                </table>
            </div>
            
        </div>
        
    </div>
    
    <!-- ===== FOOTER ===== -->
    <div class="game-footer">
        <div class="game-controls">
            <!-- Kein Text mehr -->
        </div>
        <div id="result-display" class="result-display">
            <!-- Ergebnis wird hier angezeigt -->
        </div>
    </div>
    
</div>

<!-- ===== COUNTING-SPEZIFISCHES CSS ===== -->
<style>
/* GAME INSTRUCTIONS */
.game-instructions {
    text-align: center;
    color: #495057;
    font-size: 1rem;
    background: rgba(255,255,255,0.9);
    padding: 12px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border: 2px solid #e9ecef;
    flex-shrink: 0;
}

/* HAUPTCONTAINER */
#counting-container {
    flex: 1;
    display: flex;
    gap: 20px;
    align-items: flex-start;
    overflow: hidden;
    min-height: 0; /* Wichtig für Flex-Shrinking */
}

/* PLAYFIELD WRAPPER */
#playfield-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

/* PLAYFIELD (passt sich an verfügbare Höhe an) */
.counting-playfield {
    flex: 1;
    position: relative;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);
    min-height: 200px;
    width: 100%;
}

/* ZAHLEN-BUTTONS */
.num-btn {
    position: absolute;
    width: 70px;
    height: 70px;
    border: none;
    border-radius: 50%;
    font-size: 1.8rem;
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

.num-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    background: #f8f9fa;
}

.num-btn.correct { 
    background: #28a745 !important; 
    color: #fff !important;
    animation: pulse-green 0.6s ease-in-out;
    transform: scale(1.05);
}

.num-btn.wrong { 
    background: #dc3545 !important; 
    color: #fff !important;
    animation: shake 0.6s ease-in-out;
}

/* SIDEBAR */
.counting-sidebar {
    width: 140px;               /* SCHMALER */
    flex-shrink: 0;
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 15px 10px;         /* WENIGER PADDING */
    border: 2px solid #e9ecef;
    overflow-y: auto;
    max-height: 100%;
}

.counting-sidebar h4 {
    margin: 0 0 10px 0;         /* WENIGER MARGIN */
    text-align: center;
    color: #2c3e50;
    font-size: 1rem;            /* KLEINER */
    font-weight: 600;
}

/* SCOREBOARD */
#scoreboard {
    width: 100%;
    border-collapse: collapse;
}

#scoreboard td {
    height: 30px;               /* KLEINER */
    text-align: center;
    font-weight: bold;
    font-size: 1rem;            /* GRÖSSER für bessere Lesbarkeit */
    border-bottom: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 2px;
    padding: 3px;               /* WENIGER PADDING */
}

#scoreboard tr:last-child td { 
    border-bottom: none; 
}

/* KEIN TEXT VOR DEN ERGEBNISSEN */
.error-cell {
    /* Prefix entfernt */
}

/* SCOREBOARD FARBEN */
.green  { background-color: #28a745; color: #fff; }
.yellow { background-color: #ffc107; color: #000; }
.orange { background-color: #fd7e14; color: #fff; }
.red    { background-color: #dc3545; color: #fff; }

/* FOOTER INFO - ENTFERNT */
/* .counting-info entfernt */

/* ANIMATIONEN */
@keyframes pulse-green {
    0% { transform: scale(1.05); }
    50% { transform: scale(1.2); box-shadow: 0 0 25px rgba(40, 167, 69, 0.6); }
    100% { transform: scale(1.05); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-6px); }
    20%, 40%, 60%, 80% { transform: translateX(6px); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    #counting-container {
        flex-direction: column;
        gap: 15px;
    }
    
    .counting-sidebar {
        width: 100%;
        max-height: 120px;      /* KLEINER */
        order: -1;
        padding: 10px 8px;      /* WENIGER PADDING */
    }
    
    .num-btn {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .game-instructions {
        font-size: 0.9rem;
        padding: 10px 15px;
    }
}

@media (max-width: 480px) {
    .num-btn {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    .counting-sidebar {
        padding: 8px 6px;       /* NOCH WENIGER PADDING */
        max-height: 100px;      /* NOCH KLEINER */
    }
    
    #scoreboard td {
        height: 25px;           /* KLEINER */
        font-size: 0.9rem;      /* ANGEPASST */
    }
    
    .game-instructions {
        font-size: 0.85rem;
        padding: 8px 12px;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Nur laden wenn Counting-Container vorhanden
    if (!$('#wort-spiel-counting-container').length) return;
    
    console.log('Zahlen-Spiel initialisiert - Neues Layout');
    
    const TOTAL_ROUNDS = 10;
    const playfield = document.getElementById('playfield');
    const scoreboard = document.querySelector('#scoreboard tbody');
    const resultDisplay = document.getElementById('result-display');

    let expected = 1;
    let errors = 0;
    let roundNumber = 1;
    let gameOver = false;
    let sessionId = generateSessionId();
    let roundStartTime = new Date();
    let wrongClicks = [];

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
    function createNumberButtons() {
        playfield.innerHTML = ''; // Clear existing buttons
        
        for (let i = 1; i <= 9; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.dataset.number = i;
            btn.className = 'num-btn';
            playfield.appendChild(btn);
        }
    }

    // Layout-Definitionen (gleich wie vorher)
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
        const numButtons = [...document.querySelectorAll('.num-btn')];
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
            
            // Alle falschen Markierungen entfernen
            document.querySelectorAll('.num-btn').forEach(b => b.classList.remove('wrong'));
            expected++;

            // Update Result Display
            resultDisplay.textContent = `✅ Richtig! Weiter mit ${expected > 9 ? 'neuer Runde' : expected}`;
            resultDisplay.className = 'result-display success';

            if (expected > 9) {
                // Runde beendet
                updateScoreCell(roundNumber - 1, errors);
                saveRoundResult();

                resultDisplay.textContent = `🎉 Runde ${roundNumber} beendet mit ${errors} Fehlern!`;

                setTimeout(() => {
                    document.querySelectorAll('.num-btn').forEach(b => b.classList.remove('correct'));
                    
                    if (roundNumber >= TOTAL_ROUNDS) {
                        gameOver = true;
                        resultDisplay.textContent = `🏆 Alle ${TOTAL_ROUNDS} Runden geschafft!`;
                        resultDisplay.className = 'result-display success';
                        launchConfetti();
                    } else {
                        roundNumber++;
                        resetForNextRound();
                        resultDisplay.textContent = `🚀 Runde ${roundNumber} - Los geht's!`;
                        resultDisplay.className = 'result-display';
                    }
                }, 1500);
            }

        } else {
            // Falsch geklickt
            if (!btn.classList.contains('correct')) {
                if (btn.classList.contains('wrong')) {
                    btn.classList.remove('wrong');
                } else {
                    btn.classList.add('wrong');
                    errors++;
                    
                    // Update Result Display
                    resultDisplay.textContent = `❌ Falsch! Du klicktest ${value}, aber ${expected} ist gesucht.`;
                    resultDisplay.className = 'result-display error';
                    
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
        wrongClicks = [];
        roundStartTime = new Date();
        chooseRandomLayout();
    }

    function launchConfetti() {
        if (typeof confetti !== 'function') return;
        const end = Date.now() + 3000;
        (function frame() {
            confetti({
                particleCount: 30,
                spread: 90,
                startVelocity: 40,
                origin: { x: Math.random(), y: Math.random() * 0.7 }
            });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    }

    // Runden-Ergebnis speichern
    function saveRoundResult() {
        const roundEndTime = new Date();
        const duration = Math.round((roundEndTime - roundStartTime) / 1000);
        
        console.log('Speichere Runde:', roundNumber, 'Fehler:', errors);
        
        let wrongClicksText = `${errors} Fehler`;
        if (wrongClicks.length > 0) {
            const clickedNumbers = wrongClicks.map(click => click.clickedNumber).join(', ');
            wrongClicksText += ` (${clickedNumbers})`;
        }
        
        $.ajax({
            url: wortSpielAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wort_spiel_save_game',
                nonce: wortSpielAjax.nonce,
                session_id: sessionId,
                game_mode: 'counting',
                target_word: `Runde ${roundNumber}`,
                user_input: wrongClicksText,
                is_correct: errors === 0 ? 1 : 0,
                duration: duration
            },
            success: function(response) {
                if (response.success) {
                    console.log('Runde gespeichert');
                } else {
                    console.error('Fehler beim Speichern:', response.data?.message);
                }
            },
            error: function() {
                console.error('AJAX-Fehler beim Speichern der Runde');
            }
        });
    }

    // Zurück zum Menü
    $('#back-to-menu-btn').on('click', function() {
        const url = new URL(window.location.href);
        url.searchParams.delete('game_mode');
        url.hash = '';
        window.location.href = url.toString();
    });

    // Spiel initialisieren
    createNumberButtons();
    chooseRandomLayout();
    initScoreboard();
    
    // Initial Result Display - LEER LASSEN
    resultDisplay.textContent = ``;
    resultDisplay.className = 'result-display';
    
});
</script>