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
            
            <!-- Solution Slots: 10 + 5 + 5 -->
            <div class="solution-slots">
                <!-- Erste Reihe: 10 Slots -->
                <div class="slot-row">
                    <?php for($i = 1; $i <= 10; $i++): ?>
                        <div class="solution-slot" data-slot-id="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
                
                <!-- Zweite Reihe: 5 Slots -->
                <div class="slot-row">
                    <?php for($i = 11; $i <= 15; $i++): ?>
                        <div class="solution-slot" data-slot-id="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
                
                <!-- Dritte Reihe: 5 Slots -->
                <div class="slot-row">
                    <?php for($i = 16; $i <= 20; $i++): ?>
                        <div class="solution-slot" data-slot-id="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>
            
        </div>
        
    </div>
    
    <!-- FOOTER -->
    <div class="game-footer">
        <div class="game-controls">
            <button id="new-round-btn" class="wort-spiel-btn wort-spiel-btn-primary">
                Neue Runde
            </button>
            <button id="check-answer-btn" class="wort-spiel-btn wort-spiel-btn-success">
                Prüfen
            </button>
            <button id="clear-all-btn" class="wort-spiel-btn wort-spiel-btn-secondary">
                Alles löschen
            </button>
        </div>
        <div id="result-display" class="result-display">
            Klicke auf die Punkte, um sie zu den Slots zu bewegen!
        </div>
    </div>
    
</div>

<style>
/* SPIEL-SPEZIFISCHES CSS */

/* Playfield mit Punkten */
.dots-playfield {
    flex: 2 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 20px !important;
}

.dots-grid {
    display: grid !important;
    grid-template-columns: repeat(5, 1fr) !important;
    grid-template-rows: repeat(3, 1fr) !important;
    gap: 15px !important;
    max-width: 400px !important;
    width: 100% !important;
}

.dot-item {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: transform 0.2s ease !important;
}

.dot-item:hover {
    transform: scale(1.1) !important;
}

.dot {
    width: 30px !important;
    height: 30px !important;
    background: #4CAF50 !important;
    border-radius: 50% !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
    transition: all 0.2s ease !important;
}

.dot-item.used .dot {
    background: #ddd !important;
    cursor: not-allowed !important;
}

/* Solution Area */
.solution-area {
    flex: 1 !important;
    display: flex !important;
    align-items: center !important;
    padding: 20px !important;
    gap: 20px !important;
}

.target-number-display {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    min-width: 80px !important;
}

.target-label {
    font-size: 14px !important;
    color: #666 !important;
    margin-bottom: 5px !important;
}

.target-number {
    font-size: 48px !important;
    font-weight: bold !important;
    color: #2196F3 !important;
    background: #f0f8ff !important;
    border: 2px solid #2196F3 !important;
    border-radius: 10px !important;
    padding: 10px 15px !important;
    min-width: 60px !important;
    text-align: center !important;
}

.solution-slots {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 8px !important;
    max-width: 500px !important;
}

.slot-row {
    display: flex !important;
    gap: 8px !important;
    justify-content: flex-start !important;
}

.solution-slot {
    width: 35px !important;
    height: 35px !important;
    border: 2px dashed #ccc !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    background: #fafafa !important;
}

.solution-slot.filled {
    border: 2px solid #4CAF50 !important;
    background: #4CAF50 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
}

.solution-slot.filled .dot {
    width: 25px !important;
    height: 25px !important;
    background: white !important;
}

/* Animationen */
.solution-slot.correct {
    background: #4CAF50 !important;
    border-color: #4CAF50 !important;
    animation: correctPulse 0.6s ease !important;
}

.solution-slot.wrong {
    animation: wrongShake 0.5s ease !important;
}

@keyframes correctPulse {
    0% { transform: scale(1); background: #4CAF50; }
    50% { transform: scale(1.1); background: #66BB6A; }
    100% { transform: scale(1); background: #4CAF50; }
}

@keyframes wrongShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Mobile Anpassungen */
@media (max-width: 768px) {
    .dots-grid {
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 10px !important;
        max-width: 300px !important;
    }
    
    .dot {
        width: 25px !important;
        height: 25px !important;
    }
    
    .solution-area {
        flex-direction: column !important;
        gap: 15px !important;
    }
    
    .target-number {
        font-size: 36px !important;
        padding: 8px 12px !important;
    }
    
    .solution-slot {
        width: 30px !important;
        height: 30px !important;
    }
}

@media (max-width: 480px) {
    .dots-grid {
        gap: 8px !important;
        max-width: 250px !important;
    }
    
    .dot {
        width: 20px !important;
        height: 20px !important;
    }
    
    .solution-slot {
        width: 25px !important;
        height: 25px !important;
    }
    
    .slot-row {
        gap: 6px !important;
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
    
    function generateTargetNumber() {
        return Math.floor(Math.random() * 7) + 3; // 3-9
    }
    
    function startNewRound() {
        currentTargetNumber = generateTargetNumber();
        $('#target-number').text(currentTargetNumber);
        
        // Alle Punkte zurücksetzen
        $('.dot-item').removeClass('used');
        $('.solution-slot').removeClass('filled correct wrong').empty();
        
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
        
        // Sound-Feedback (optional)
        // playSound('click');
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
    startNewRound();
});
</script>