<?php
/**
 * Template für das Hauptspiel
 * 
 * @package WortSpiel
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Spielmodus aus URL-Parameter oder Shortcode-Attribut
$game_mode = isset($_GET['game_mode']) ? sanitize_text_field($_GET['game_mode']) : ($atts['mode'] ?? 'animals');
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

<div id="wort-spiel-game-container" class="wort-spiel-game">
    
    <!-- Zurück Button (GLOBAL CSS) -->
    <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
        ← <?php _e('Zurück zum Menü', 'wort-spiel'); ?>
    </button>
    
    <!-- Spiel Header (GLOBAL CSS) -->
    <div class="game-header">
        <h2 id="game-mode-title"><?php echo esc_html($game_mode); ?></h2>
        <div class="player-info">
            <?php printf(__('Spieler: %s', 'wort-spiel'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?>
        </div>
    </div>
    
    <!-- Audio Bereich (GLOBAL CSS für has-audio games) -->
    <div id="audio-display" class="audio-display">
        <div id="audio-status" class="audio-status">
            <?php _e('Höre gut zu...', 'wort-spiel'); ?>
        </div>
        <button id="replay-btn" class="wort-spiel-btn primary audio-btn">
            🔊 <?php _e('Wort wiederholen', 'wort-spiel'); ?>
        </button>
    </div>
    
    <!-- Loading & Error (GLOBAL CSS) -->
    <div id="loading-message" class="loading-message" style="display:none;">
        <?php _e('Lade Audio...', 'wort-spiel'); ?>
    </div>
    <div id="error-message" class="error-message" style="display:none;">
        <?php _e('Audio konnte nicht geladen werden.', 'wort-spiel'); ?>
    </div>
    
    <!-- PLAYFIELD - Template-spezifisches CSS -->
    <div id="playfield" class="playfield wort-game-playfield">
        <!-- Buchstaben-Buttons werden hier generiert -->
    </div>
    
    <!-- WORT-LINIE - Template-spezifisches CSS -->
    <div id="word-line" class="word-line wort-game-word-line">
        <!-- Wort-Slots werden hier generiert -->
    </div>
    
    <!-- Spiel-Buttons (GLOBAL CSS) -->
    <div id="game-controls" class="game-controls">
        <button id="check-btn" class="wort-spiel-btn success">
            <?php _e('Prüfen', 'wort-spiel'); ?>
        </button>
        <button id="reset-btn" class="wort-spiel-btn secondary">
            <?php _e('Zurücksetzen', 'wort-spiel'); ?>
        </button>
        <button id="new-word-btn" class="wort-spiel-btn primary">
            <?php _e('Neues Wort', 'wort-spiel'); ?>
        </button>
    </div>
    
    <!-- Ergebnis-Anzeige (GLOBAL CSS) -->
    <div id="result-display" class="result-display">
        <!-- Ergebnis wird hier angezeigt -->
    </div>
    
    <!-- End Screen (GLOBAL CSS) -->
    <div id="end-screen" class="end-screen" style="display:none;">
        <div class="end-screen-content">
            <div class="end-title">
                🎉 <?php _e('Du hast alle Wörter geschafft!', 'wort-spiel'); ?>
            </div>
            <div class="end-actions">
                <button id="restart-game-btn" class="wort-spiel-btn primary">
                    🔁 <?php _e('Neues Spiel', 'wort-spiel'); ?>
                </button>
                <button id="back-to-menu-end-btn" class="wort-spiel-btn secondary">
                    🏠 <?php _e('Zurück zum Menü', 'wort-spiel'); ?>
                </button>
            </div>
        </div>
    </div>
    
</div>

<!-- TEMPLATE-SPEZIFISCHES CSS für Standard Wort-Spiele -->
<style>
/* ===== PLAYFIELD STYLES (Template-spezifisch) ===== */
.wort-game-playfield {
    position: relative;
    width: 100%;
    height: 400px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    margin-bottom: 30px;
    overflow: hidden;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);
}

.wort-game-playfield .letter-btn {
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

.wort-game-playfield .letter-btn:hover {
    background: #f8f9fa;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
}

.wort-game-playfield .letter-btn.selected {
    background: #007cba;
    color: white;
    transform: scale(0.9);
}

/* ===== WORD LINE STYLES (Template-spezifisch) ===== */
.wort-game-word-line {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 30px;
    min-height: 80px;
    align-items: center;
    flex-wrap: wrap;
}

.wort-game-word-line .word-slot {
    width: 70px;
    height: 70px;
    border: 3px dashed #dee2e6;
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.8rem;
    font-weight: bold;
    background: white;
    transition: all 0.3s ease;
    cursor: pointer;
}

.wort-game-word-line .word-slot.filled {
    background: #e3f2fd;
    border-color: #007cba;
    border-style: solid;
}

.wort-game-word-line .word-slot.correct {
    background: #28a745;
    color: red;
    border-color: #28a745;
    border-style: solid;
    animation: pulse-green 1.7s ease-in-out;
}

.wort-game-word-line .word-slot.wrong {
    /*background: #dc3545;
    color: white;
    border-color: #dc3545;
    border-style: solid;*/
    animation: shake 0.6s ease-in-out;
}

@keyframes pulse-green {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 0 20px rgba(40, 167, 69, 0.5); }
    100% { transform: scale(1); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

/* ===== RESPONSIVE für Template-Elemente ===== */
@media (max-width: 768px) {
    .wort-game-playfield {
        height: 300px;
    }
    
    .wort-game-playfield .letter-btn {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .wort-game-word-line .word-slot {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Nur laden wenn Container vorhanden
    if (!$('#wort-spiel-game-container').length) return;
    
    // Spiel-Objekt
    const WortSpielGame = {
        
        // Konfiguration
        gameMode: '<?php echo esc_js($game_mode); ?>',
        currentWord: '',
        selectedLetters: [],
        letterButtons: [],
        currentAudio: null,
        audioCache: {},
        playlist: [],
        gameStartTime: null,
        sessionId: null,
        gameCounter: 0,
        
        // Wortlisten (Fallback)
        wordLists: {
            animals: ["KATZE", "HUND", "VOGEL", "FISCH", "PFERD", "MAUS", "FUCHS", "WOLF", "BÄR", "LÖWE"],
            nature: ["BAUM", "BLUME", "SONNE", "MOND", "STERN", "BERG", "MEER", "FLUSS", "WALD", "WIESE"],
            colors: ["ROT", "BLAU", "GRÜN", "GELB", "LILA", "ROSA", "BRAUN", "GRAU", "WEISS", "ORANGE"],
            food: ["BROT", "KÄSE", "MILCH", "APFEL", "BANANE", "PIZZA", "NUDELN", "REIS", "FLEISCH", "GEMÜSE"]
        },
        
        // Button-Layouts
        layouts: [
            [{"left":24.7,"top":20.1},{"left":73.7,"top":7.0},{"left":63.2,"top":66.8},{"left":82.3,"top":61.9},{"left":7.7,"top":6.9}],
            [{"left":62.5,"top":60.6},{"left":28.5,"top":66.8},{"left":30.0,"top":31.8},{"left":85.5,"top":16.0},{"left":82.8,"top":55.5}]
        ],
        
        // Initialisierung
        init: function() {
            this.sessionId = this.generateSessionId();
            this.bindEvents();
            this.initGame();
        },
        
        // Event-Handler
        bindEvents: function() {
            $('#back-to-menu-btn, #back-to-menu-end-btn').on('click', this.backToMenu.bind(this));
            $('#restart-game-btn').on('click', this.restartGame.bind(this));
            $('#replay-btn').on('click', this.replayAudio.bind(this));
            $('#check-btn').on('click', this.checkWord.bind(this));
            $('#reset-btn').on('click', this.resetGame.bind(this));
            $('#new-word-btn').on('click', this.initGame.bind(this));
            
            // Playfield-Klicks
            $('#playfield').on('click', '.letter-btn', this.handleLetterClick.bind(this));
        },
        
        // Session-ID generieren
        generateSessionId: function() {
            const now = new Date();
            return now.toISOString().replace(/[:.-]/g, '_');
        },
        
        // Spiel initialisieren
        initGame: function() {
            const word = this.selectRandomWord();
            if (!word) return;
            
            this.currentWord = word;
            this.gameStartTime = new Date();
            this.selectedLetters = [];
            
            // UI zurücksetzen
            $('#result-display').text('').removeClass('success error');
            $('#error-message').hide();
            $('#end-screen').hide();
            $('#game-controls').show();
            
            // Spiel-Elemente erstellen
            this.createLetterButtons();
            this.createWordSlots();
            
            // Audio laden und abspielen
            this.playAudio(this.currentWord, this.getCategory());
        },
        
        // Kategorie aus Spielmodus ermitteln
        getCategory: function() {
            return this.gameMode.replace('-learning', '').replace('-extra', '');
        },
        
        // Zufälliges Wort auswählen
        selectRandomWord: function() {
            if (this.playlist.length === 0) {
                const category = this.getCategory();
                const words = this.wordLists[category];
                if (!words || words.length === 0) {
                    console.error('Keine Wörter für Kategorie:', category);
                    return null;
                }
                
                if (this.playlist.usedUpOnce) {
                    this.showEndScreen();
                    return null;
                }
                
                this.playlist = [...words];
                this.shuffleArray(this.playlist);
                this.playlist.usedUpOnce = true;
            }
            
            return this.playlist.pop();
        },
        
        // Array mischen
        shuffleArray: function(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        },
        
        // Buchstaben-Buttons erstellen
        createLetterButtons: function() {
            $('#playfield').empty();
            this.letterButtons = [];
            
            const letters = [...this.currentWord];
            this.shuffleArray(letters);
            const layout = this.layouts[Math.floor(Math.random() * this.layouts.length)];
            
            letters.forEach((letter, index) => {
                const btn = $(`
                    <button class="letter-btn" data-letter="${letter}">
                        ${letter}
                    </button>
                `);
                
                if (layout[index]) {
                    btn.css({
                        left: layout[index].left + '%',
                        top: layout[index].top + '%'
                    });
                }
                
                $('#playfield').append(btn);
                this.letterButtons.push(btn[0]);
            });
        },
        
        // Wort-Slots erstellen
        createWordSlots: function() {
            $('#word-line').empty();
            
            for (let i = 0; i < this.currentWord.length; i++) {
                const slot = $('<div class="word-slot"></div>');
                $('#word-line').append(slot);
            }
            
            // Sortable aktivieren
            if (typeof Sortable !== 'undefined') {
                Sortable.create(document.getElementById('word-line'), {
                    animation: 150,
                    onSort: () => {
                        const slots = $('#word-line .word-slot');
                        this.selectedLetters = [];
                        slots.each(function() {
                            const letter = $(this).text().trim();
                            if (letter) {
                                WortSpielGame.selectedLetters.push(letter);
                            }
                        });
                    }
                });
            }
        },
        
        // Buchstaben-Klick behandeln
        handleLetterClick: function(e) {
            const btn = $(e.target);
            const letter = btn.data('letter');
            
            if (btn.hasClass('selected')) return;
            
            // Ersten leeren Slot finden
            const emptySlot = $('#word-line .word-slot').filter(function() {
                return $(this).text().trim() === '';
            }).first();
            
            if (emptySlot.length) {
                emptySlot.text(letter).addClass('filled');
                this.selectedLetters.push(letter);
                btn.addClass('selected');
            }
        },
        
        // Audio-Funktionen
        getAudioFileName: function(word) {
            return word.toLowerCase()
                .replace('ä', 'ae')
                .replace('ö', 'oe')
                .replace('ü', 'ue')
                .replace('ß', 'ss');
        },
        
        getAudioPath: function(word, category) {
            const fileName = this.getAudioFileName(word);
            return `${wortSpielAjax.pluginUrl}assets/audio/${category}/${fileName}.m4a`;
        },
        
        playAudio: function(word, category, delay = 700) {
            const key = `${category}_${word}`;
            
            $('#loading-message').show();
            $('#replay-btn').prop('disabled', true);
            
            setTimeout(() => {
                if (this.audioCache[key]) {
                    this.currentAudio = this.audioCache[key];
                    this.currentAudio.currentTime = 0;
                    this.currentAudio.play();
                    $('#loading-message').hide();
                    $('#replay-btn').prop('disabled', false);
                    $('#audio-status').text('🔊 <?php _e('Höre gut zu...', 'wort-spiel'); ?>');
                } else {
                    const audio = new Audio(this.getAudioPath(word, category));
                    
                    audio.addEventListener('canplaythrough', () => {
                        this.audioCache[key] = audio;
                        this.currentAudio = audio;
                        audio.play();
                        $('#loading-message').hide();
                        $('#replay-btn').prop('disabled', false);
                        $('#error-message').hide();
                        $('#audio-status').text('🔊 <?php _e('Höre gut zu...', 'wort-spiel'); ?>');
                    });
                    
                    audio.addEventListener('error', () => {
                        $('#loading-message').hide();
                        $('#error-message').show().text(`<?php _e('Audio für', 'wort-spiel'); ?> "${word}" <?php _e('nicht verfügbar', 'wort-spiel'); ?>`);
                        $('#replay-btn').prop('disabled', true);
                        $('#audio-status').text(`<?php _e('Gesuchtes Wort:', 'wort-spiel'); ?> ${word}`);
                    });
                    
                    audio.load();
                }
            }, delay);
        },
        
        replayAudio: function() {
            if (this.currentAudio) {
                this.currentAudio.currentTime = 0;
                this.currentAudio.play();
                $('#audio-status').text('🔊 <?php _e('Wort wird wiederholt...', 'wort-spiel'); ?>');
            }
        },
        
        // Wort prüfen
        checkWord: function() {
            const userWord = this.selectedLetters.join('');
            const slots = $('#word-line .word-slot');
            
            if (userWord === this.currentWord) {
                // Richtig!
                slots.addClass('correct');
                $('#result-display').text(`🎉 <?php _e('Richtig! Das war:', 'wort-spiel'); ?> ${this.currentWord}`)
                    .removeClass('error').addClass('success');
                $('#audio-status').text('✅ <?php _e('Perfekt gelöst!', 'wort-spiel'); ?>');
                
                // Ergebnis speichern
                this.saveGameResult(this.currentWord, userWord, true);
                
                // Nach kurzer Verzögerung neues Wort
                setTimeout(() => {
                    this.initGame();
                }, 2000);
                
            } else {
                // Falsch
                slots.removeClass('correct').addClass('wrong');
                
                $('#result-display').text(`❌ <?php _e('Falsch! Du hattest:', 'wort-spiel'); ?> "${userWord}" - <?php _e('Richtig:', 'wort-spiel'); ?> "${this.currentWord}"`)
                    .removeClass('success').addClass('error');
                
                // Ergebnis speichern
                this.saveGameResult(this.currentWord, userWord, false);
                
                // Nach 3 Sekunden Animation entfernen
                setTimeout(() => {
                    slots.removeClass('wrong');
                }, 3000);
            }
        },
        
        // Spiel zurücksetzen
        resetGame: function() {
            this.selectedLetters = [];
            $('.letter-btn').removeClass('selected');
            
            const slots = $('#word-line .word-slot');
            slots.text('').removeClass('filled correct wrong');
            
            $('#result-display').text('').removeClass('success error');
            $('#audio-status').text('🔊 <?php _e('Höre gut zu...', 'wort-spiel'); ?>');
        },
        
        // Spiel-Ergebnis speichern
        saveGameResult: function(targetWord, userInput, isCorrect) {
            const gameEndTime = new Date();
            const duration = this.gameStartTime ? Math.round((gameEndTime - this.gameStartTime) / 1000) : 0;
            
            $.ajax({
                url: wortSpielAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wort_spiel_save_game',
                    nonce: wortSpielAjax.nonce,
                    session_id: this.sessionId,
                    game_mode: this.gameMode,
                    target_word: targetWord,
                    user_input: userInput,
                    is_correct: isCorrect ? 1 : 0,
                    duration: duration
                },
                success: function(response) {
                    if (response.success) {
                        console.log('<?php _e('Spiel-Ergebnis gespeichert', 'wort-spiel'); ?>');
                    } else {
                        console.error('<?php _e('Fehler beim Speichern:', 'wort-spiel'); ?>', response.data.message);
                    }
                },
                error: function() {
                    console.error('<?php _e('Netzwerk-Fehler beim Speichern', 'wort-spiel'); ?>');
                }
            });
        },
        
        // End-Screen anzeigen
        showEndScreen: function() {
            $('#game-controls').hide();
            $('#new-word-btn').prop('disabled', true);
            $('#end-screen').show();
            
            // Konfetti-Animation
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 200,
                    spread: 100,
                    origin: { y: 0.6 }
                });
            }
        },
        
        // Spiel neustarten
        restartGame: function() {
            this.playlist = [];
            this.playlist.usedUpOnce = false;
            this.gameCounter = 0;
            $('#end-screen').hide();
            $('#game-controls').show();
            $('#new-word-btn').prop('disabled', false);
            this.initGame();
        },
        
        // Zurück zum Menü
        backToMenu: function() {
            // Audio stoppen
            if (this.currentAudio) {
                this.currentAudio.pause();
                this.currentAudio = null;
            }
            
            // URL ohne game_mode Parameter
            const url = new URL(window.location.href);
            url.searchParams.delete('game_mode');
            url.hash = '';
            window.location.href = url.toString();
        }
    };
    
    // Spiel-Objekt initialisieren
    WortSpielGame.init();
    
    // Spiel-Modus-Titel setzen
    const gameModeNames = {
        'animals': '<?php _e('Tiere', 'wort-spiel'); ?>',
        'animals-learning': '<?php _e('Tiere (Lernmodus)', 'wort-spiel'); ?>',
        'nature': '<?php _e('Natur', 'wort-spiel'); ?>',
        'nature-learning': '<?php _e('Natur (Lernmodus)', 'wort-spiel'); ?>',
        'colors': '<?php _e('Farben', 'wort-spiel'); ?>',
        'colors-learning': '<?php _e('Farben (Lernmodus)', 'wort-spiel'); ?>',
        'food': '<?php _e('Essen', 'wort-spiel'); ?>',
        'food-learning': '<?php _e('Essen (Lernmodus)', 'wort-spiel'); ?>',
        'food-extra': '<?php _e('Essen (Extra)', 'wort-spiel'); ?>'
    };
    
    $('#game-mode-title').text(gameModeNames[WortSpielGame.gameMode] || WortSpielGame.gameMode);
});
</script>