<?php
/**
 * Template: Zähl-Punkte Spiel
 * 15 Punkte im Playfield -> 20 Slots (10+5+5) -> Zahl links prüfen
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
?>

<div id="wort-spiel-counting-dots-container" class="wort-spiel-game">
    
    <!-- HEADER -->
    <div class="game-header">
        <div class="header-left">
            <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
                ← Zurück
            </button>
            <h2>Zähl-Spiel</h2>
        </div>
        <div class="header-right">
            <div class="player-info">
                Spieler: <strong><?php echo esc_html($current_user->display_name); ?></strong>
            </div>
        </div>
    </div>
    
    <!-- GAME-CONTENT -->
    <div class="game-content">
        
        <!-- Playfield mit 15 Punkten -->
        <div class="dots-playfield">
            <div class="dots-grid">
                <?php for($i = 1; $i <= 15; $i++): ?>
                    <div class="dot-item" data-dot-id="<?php echo $i; ?>">
                        <div class="dot"></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- Solution Area -->
        <div class="solution-area">
            
            <!-- Target Number Display -->
            <div class="target-number-display">
                <div class="target-label">Anzahl:</div>
                <div id="target-number" class="target-number">5</div>
            </div>
            
            <!-- Solution Slots: 10 horizontal mit Abstand nach 5 -->
            <div class="solution-slots">
                <div class="slot-row">
                    <!-- Erste 5 Slots -->
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <div class="solution-slot" data-slot-id="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                    
                    <!-- Abstand -->
                    <div class="slot-spacer"></div>
                    
                    <!-- Zweite 5 Slots -->
                    <?php for($i = 6; $i <= 10; $i++): ?>
                        <div class="solution-slot" data-slot-id="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>
            
        </div>
        
    </div>
    
    <!-- FOOTER -->
    <div class="game-footer">
        <div class="game-controls">

            <button id="check-answer-btn" class="wort-spiel-btn success">
                Prüfen
            </button>
            <button id="clear-all-btn" class="wort-spiel-btn secondary">
                Alles löschen
            </button>
            <button id="new-round-btn" class="wort-spiel-btn primary">
                Neue Runde
            </button>
        </div>
        <div id="result-display" class="result-display">
            Klicke auf die Punkte, um sie zu den Slots zu bewegen!
        </div>
    </div>
    
</div>

<style>
/* SPIEL-SPEZIFISCHES CSS */

/* Playfield mit verstreuten Dots - wie counting.php */
.dots-playfield {
    flex: 1 !important;
    position: relative !important;
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    border-radius: 15px !important;
    overflow: hidden !important;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.1) !important;
    min-height: 400px !important;
    height: 100% !important;
    width: 100% !important;
}

.dots-grid {
    /* KEIN GRID - Dots werden absolut positioniert */
    position: relative !important;
    width: 100% !important;
    height: 100% !important;
}

.dot-item {
    position: absolute !important;
    cursor: pointer !important;
    transition: transform 0.2s ease !important;
}

.dot-item:hover {
    transform: scale(1.1) !important;
}

.dot {
    width: 40px !important;
    height: 40px !important;
    background: white !important;
    border-radius: 50% !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    transition: all 0.2s ease !important;
    border: 3px solid #2c3e50 !important;
}

.dot-item.used .dot {
    background: #ddd !important;
    border-color: #999 !important;
    cursor: not-allowed !important;
    opacity: 0.5 !important;
}

/* Solution Area - horizontal in der Mitte */
.solution-area {
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
    gap: 30px !important;
    background: rgba(255,255,255,0.1) !important;
    border-top: 2px solid rgba(255,255,255,0.2) !important;
}

.target-number-display {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    min-width: 80px !important;
}

.target-label {
    font-size: 16px !important;
    color: #2c3e50 !important;
    margin-bottom: 8px !important;
    font-weight: 600 !important;
}

.target-number {
    font-size: 48px !important;
    font-weight: bold !important;
    color: #2196F3 !important;
    background: white !important;
    border: 3px solid #2196F3 !important;
    border-radius: 12px !important;
    padding: 12px 18px !important;
    min-width: 70px !important;
    text-align: center !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.solution-slots {
    flex: 1 !important;
    display: flex !important;
    justify-content: center !important;
    max-width: 600px !important;
}

.slot-row {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
}

.slot-spacer {
    width: 30px !important;
    height: 2px !important;
    background: rgba(255,255,255,0.4) !important;
    margin: 0 10px !important;
}

.solution-slot {
    width: 45px !important;
    height: 45px !important;
    border: 3px dashed rgba(255,255,255,0.6) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    background: rgba(255,255,255,0.1) !important;
}

.solution-slot.filled {
    border: 3px solid white !important;
    background: rgba(255,255,255,0.9) !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
}

.solution-slot.filled .dot {
    width: 35px !important;
    height: 35px !important;
    background: #4CAF50 !important;
    border-color: #2e7d32 !important;
}

/* Animationen */
.solution-slot.correct {
    background: rgba(76, 175, 80, 0.9) !important;
    border-color: #4CAF50 !important;
    animation: correctPulse 0.6s ease !important;
}

.solution-slot.wrong {
    animation: wrongShake 0.5s ease !important;
}

@keyframes correctPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); }
}

@keyframes wrongShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}

/* Mobile Anpassungen */
@media (max-width: 768px) {
    .dot {
        width: 35px !important;
        height: 35px !important;
    }
    
    .solution-area {
        flex-direction: column !important;
        gap: 20px !important;
        padding: 15px !important;
    }
    
    .target-number {
        font-size: 36px !important;
        padding: 8px 12px !important;
    }
    
    .solution-slot {
        width: 40px !important;
        height: 40px !important;
    }
    
    .solution-slot.filled .dot {
        width: 30px !important;
        height: 30px !important;
    }
    
    .slot-spacer {
        width: 20px !important;
    }
}

@media (max-width: 480px) {
    .dot {
        width: 30px !important;
        height: 30px !important;
    }
    
    .solution-slot {
        width: 35px !important;
        height: 35px !important;
    }
    
    .solution-slot.filled .dot {
        width: 25px !important;
        height: 25px !important;
    }
    
    .slot-row {
        gap: 8px !important;
    }
    
    .slot-spacer {
        width: 15px !important;
        margin: 0 5px !important;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    if (!$('#wort-spiel-counting-dots-container').length) return;
    
    let sessionId = generateSessionId();
    let gameStartTime = new Date();
    let currentTargetNumber = 5;
    let round = 1;
    let correctAnswers = 0;
    
    function generateSessionId() {
        const now = new Date();
        return now.toISOString().replace(/[:.-]/g, '_');
    }
    
    // Layouts für 15 Dots - verschiedene Verteilungen
    const layouts = [
        // Layout 1
        [
            {"left":15.5,"top":25.2}, {"left":45.8,"top":15.1}, {"left":75.2,"top":22.8}, 
            {"left":25.1,"top":45.5}, {"left":65.3,"top":40.2}, {"left":85.7,"top":38.9},
            {"left":12.8,"top":65.1}, {"left":38.4,"top":68.7}, {"left":58.9,"top":62.3},
            {"left":78.2,"top":70.4}, {"left":92.1,"top":55.8}, {"left":35.6,"top":88.2},
            {"left":55.7,"top":85.1}, {"left":75.8,"top":88.9}, {"left":8.2,"top":82.7}
        ],
        // Layout 2  
        [
            {"left":22.3,"top":18.5}, {"left":52.1,"top":8.9}, {"left":82.4,"top":16.2},
            {"left":18.7,"top":38.1}, {"left":48.9,"top":35.7}, {"left":78.5,"top":42.8},
            {"left":15.2,"top":58.9}, {"left":35.8,"top":55.2}, {"left":65.1,"top":60.7},
            {"left":85.9,"top":58.3}, {"left":25.4,"top":78.1}, {"left":45.7,"top":82.5},
            {"left":68.2,"top":78.9}, {"left":88.1,"top":75.2}, {"left":12.5,"top":88.7}
        ],
        // Layout 3
        [
            {"left":35.2,"top":12.8}, {"left":65.7,"top":18.3}, {"left":88.4,"top":25.1},
            {"left":15.8,"top":32.5}, {"left":42.1,"top":38.7}, {"left":72.9,"top":35.2},
            {"left":25.4,"top":55.8}, {"left":55.2,"top":52.1}, {"left":82.7,"top":58.9},
            {"left":18.5,"top":72.3}, {"left":38.9,"top":78.5}, {"left":62.1,"top":75.2},
            {"left":85.2,"top":78.9}, {"left":45.8,"top":88.1}, {"left":8.7,"top":85.2}
        ],
        // Layout 4
        [
            {"left":28.9,"top":15.7}, {"left":58.2,"top":12.1}, {"left":85.7,"top":18.9},
            {"left":12.4,"top":35.2}, {"left":38.7,"top":32.8}, {"left":68.9,"top":38.5},
            {"left":22.1,"top":52.7}, {"left":52.8,"top":48.9}, {"left":78.5,"top":55.1},
            {"left":32.7,"top":68.2}, {"left":62.4,"top":72.8}, {"left":88.1,"top":68.5},
            {"left":15.8,"top":82.9}, {"left":45.2,"top":85.7}, {"left":75.8,"top":88.2}
        ],
        // Layout 5
        [
            {"left":42.7,"top":8.5}, {"left":72.1,"top":15.2}, {"left":92.8,"top":28.7},
            {"left":15.2,"top":28.9}, {"left":35.8,"top":32.1}, {"left":58.7,"top":35.8},
            {"left":25.1,"top":48.2}, {"left":48.9,"top":52.7}, {"left":75.2,"top":48.9},
            {"left":12.8,"top":65.1}, {"left":38.5,"top":68.7}, {"left":65.8,"top":72.1},
            {"left":85.2,"top":68.9}, {"left":28.7,"top":85.2}, {"left":55.8,"top":88.7}
        ]
    ];
    
    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }
    
    function applyLayout(layout) {
        const dotItems = document.querySelectorAll('.dot-item');
        
        // Layout auf Dots anwenden
        dotItems.forEach((dotItem, idx) => {
            if (layout[idx]) {
                dotItem.style.left = layout[idx].left + '%';
                dotItem.style.top = layout[idx].top + '%';
            }
        });
    }
    
    function chooseRandomLayout() { 
        const randomLayout = layouts[Math.floor(Math.random() * layouts.length)];
        applyLayout(randomLayout);
    }
    
    function generateTargetNumber() {
        return Math.floor(Math.random() * 7) + 3; // 3-9
    }
    
    function startNewRound() {
        currentTargetNumber = generateTargetNumber();
        $('#target-number').text(currentTargetNumber);
        
        // Alle Punkte zurücksetzen
        $('.dot-item').removeClass('used');
        $('.solution-slot').removeClass('filled correct wrong').empty();
        
        // Neues Layout anwenden
        chooseRandomLayout();
        
        $('#result-display').text(`Runde ${round}: Klicke ${currentTargetNumber} Punkte in die Slots!`);
        gameStartTime = new Date();
    }
    
    function saveGameResult(success, details) {
        $.ajax({
            url: wortSpielAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wort_spiel_save_game',
                nonce: wortSpielAjax.nonce,
                session_id: sessionId,
                game_mode: 'COUNTING_DOTS',
                target_word: `Runde ${round} (Ziel: ${currentTargetNumber})`,
                user_input: details,
                is_correct: success ? 1 : 0,
                duration: Math.round((new Date() - gameStartTime) / 1000)
            }
        });
    }
    
    // Punkt klicken -> zu nächstem freien Slot bewegen
    $('.dot-item').on('click', function() {
        if ($(this).hasClass('used')) return;
        
        const nextSlot = $('.solution-slot:not(.filled)').first();
        if (nextSlot.length === 0) {
            $('#result-display').text('Alle Slots sind voll!').addClass('error');
            setTimeout(() => $('#result-display').removeClass('error'), 2000);
            return;
        }
        
        // Punkt als verwendet markieren
        $(this).addClass('used');
        
        // Slot füllen
        nextSlot.addClass('filled').html('<div class="dot"></div>');
    });
    
    // Slot klicken -> Punkt zurück ins Playfield
    $(document).on('click', '.solution-slot.filled', function() {
        const slotId = $(this).data('slot-id');
        
        // Slot leeren
        $(this).removeClass('filled correct wrong').empty();
        
        // Entsprechenden Punkt wieder verfügbar machen
        // Letzten verwendeten Punkt freigeben
        const lastUsedDot = $('.dot-item.used').last();
        if (lastUsedDot.length > 0) {
            lastUsedDot.removeClass('used');
        }
        
        // Alle Slots nach diesem auch leeren (Reihenfolge beibehalten)
        $(this).nextAll('.solution-slot.filled').each(function() {
            $(this).removeClass('filled correct wrong').empty();
            const lastUsed = $('.dot-item.used').last();
            if (lastUsed.length > 0) {
                lastUsed.removeClass('used');
            }
        });
    });
    
    // Antwort prüfen
    $('#check-answer-btn').on('click', function() {
        const filledSlots = $('.solution-slot.filled').length;
        const isCorrect = filledSlots === currentTargetNumber;
        
        // Alle gefüllten Slots animieren
        $('.solution-slot.filled').addClass(isCorrect ? 'correct' : 'wrong');
        
        if (isCorrect) {
            correctAnswers++;
            $('#result-display')
                .text(`Richtig! ${filledSlots} von ${currentTargetNumber} ✓`)
                .removeClass('error')
                .addClass('success');
            
            saveGameResult(true, `${filledSlots}/${currentTargetNumber} korrekt`);
            
            // Nächste Runde nach kurzer Pause
            setTimeout(() => {
                round++;
                startNewRound();
            }, 2000);
            
        } else {
            $('#result-display')
                .text(`Falsch! Du hast ${filledSlots}, brauchst aber ${currentTargetNumber} ✗`)
                .removeClass('success')
                .addClass('error');
            
            saveGameResult(false, `${filledSlots}/${currentTargetNumber} falsch`);
            
            // Animation zurücksetzen nach 1 Sekunde
            setTimeout(() => {
                $('.solution-slot').removeClass('correct wrong');
                $('#result-display').removeClass('error');
            }, 1500);
        }
    });
    
    // Neue Runde
    $('#new-round-btn').on('click', function() {
        round++;
        startNewRound();
    });
    
    // Alles löschen
    $('#clear-all-btn').on('click', function() {
        $('.dot-item').removeClass('used');
        $('.solution-slot').removeClass('filled correct wrong').empty();
        $('#result-display').text(`Klicke ${currentTargetNumber} Punkte in die Slots!`).removeClass('success error');
    });
    
    // Zurück zum Menü
    $('#back-to-menu-btn').on('click', function() {
        const url = new URL(window.location.href);
        url.searchParams.delete('game_mode');
        url.hash = '';
        window.location.href = url.toString();
    });
    
    // Spiel starten
    chooseRandomLayout(); // Layout vor dem ersten Spiel setzen
    startNewRound();
});
</script>