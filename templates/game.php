<?php
/**
 * Template für das Hauptspiel
 * NO SCROLL + AUDIO BUTTON IMMER VERSION
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

// Audio-Spiele erkennen (aber Button IMMER anzeigen)
$has_audio = true; // IMMER TRUE - Button immer da
$audio_enabled = (strpos($game_mode, 'audio') !== false); // Für JavaScript
?>

<div id="wort-spiel-game-container" class="wort-spiel-game">
    
    <!-- ===== HEADER ===== -->
    <div class="game-header">
        <div class="header-left">
            <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
                ← <?php _e('Zurück', 'wort-spiel'); ?>
            </button>
            <h2 id="game-mode-title"><?php echo esc_html($game_mode); ?></h2>
        </div>
        <div class="header-right">
            <div class="player-info">
                <?php printf(__('Spieler: %s', 'wort-spiel'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?>
            </div>
            <!-- AUDIO-BUTTON IMMER ANZEIGEN -->
            <button id="replay-btn" class="wort-spiel-btn audio-btn">
                🔊 <?php _e('Wiederholen', 'wort-spiel'); ?>
            </button>
        </div>
    </div>
    
    <!-- ===== GAME-CONTENT ===== -->
    <div class="game-content">
        
        <!-- Audio-Status (nur bei Audio-Spielen anzeigen) -->
        <?php if ($audio_enabled): ?>
        <div id="audio-display" class="audio-display">
            <div id="audio-status" class="audio-status">
                <?php _e('Höre gut zu...', 'wort-spiel'); ?>
            </div>
        </div>
        
        <!-- Loading & Error Messages -->
        <div id="loading-message" class="loading-message" style="display:none;">
            <?php _e('Lade Audio...', 'wort-spiel'); ?>
        </div>
        <div id="error-message" class="error-message" style="display:none;">
            <?php _e('Audio konnte nicht geladen werden.', 'wort-spiel'); ?>
        </div>
        <?php endif; ?>
        
        <!-- PLAYFIELD -->
        <div id="playfield" class="playfield wort-game-playfield">
            <!-- Buchstaben-Buttons werden hier generiert -->
        </div>
        
        <!-- WORD-LINE -->
        <div id="word-line" class="word-line wort-game-word-line">
            <!-- Wort-Slots werden hier generiert -->
        </div>
        
    </div>
    
    <!-- ===== FOOTER ===== -->
    <div class="game-footer">
        <div class="game-controls">
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
        <div id="result-display" class="result-display">
            <!-- Ergebnis wird hier angezeigt -->
        </div>
    </div>
    
    <!-- End Screen -->
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

<!-- ===== TEMPLATE CSS - NO SCROLL ===== -->
<style>
/* PLAYFIELD - EXAKTE HÖHEN-BERECHNUNG */
.wort-game-playfield {
    flex: 1;                    /* Nimmt verfügbaren Platz */
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    margin-bottom: 15px;        /* KLEINER MARGIN */
    overflow: hidden;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);
    min-height: 150px;          /* KLEINERE MINDESTHÖHE */
    width: 100%;
    box-sizing: border-box;
}

.wort-game-playfield .letter-btn {
    position: absolute;
    width: 60px;                /* KLEINER */
    height: 60px;               /* KLEINER */
    border: none;
    border-radius: 50%;
    font-size: 1.5rem;          /* KLEINER */
    font-weight: bold;
    background: white;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 3px 12px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.wort-game-playfield .letter-btn:hover {
    background: #f8f9fa;
    transform: scale(1.1);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.wort-game-playfield .letter-btn.selected {
    background: #007cba;
    color: white;
    transform: scale(0.9);
}

/* WORD LINE - FESTE KOMPAKTE HÖHE */
.wort-game-word-line {
    height: 70px;               /* KOMPAKTE HÖHE */
    min-height: 70px;
    max-height: 70px;
    display: flex;
    justify-content: center;
    gap: 10px;                  /* KLEINER GAP */
    align-items: center;
    flex-wrap: wrap;
    flex-shrink: 0;             /* NICHT SCHRUMPFEN */
    width: 100%;
    box-sizing: border-box;
}

.wort-game-word-line .word-slot {
    width: 60px;                /* KLEINER */
    height: 60px;               /* KLEINER */
    border: 2px dashed #dee2e6; /* DÜNNER BORDER */
    border-radius: 10px;        /* KLEINER RADIUS */
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.5rem;          /* KLEINER */
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
    color: white;
    border-color: #28a745;
    border-style: solid;
    animation: pulse-green 1.7s ease-in-out;
}

.wort-game-word-line .word-slot.wrong {
    animation: shake 0.6s ease-in-out;
}

/* RESPONSIVE - NOCH KLEINERE ELEMENTE */
@media (max-width: 768px) {
    .wort-game-playfield .letter-btn {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    .wort-game-word-line {
        height: 60px;
        min-height: 60px;
        max-height: 60px;
    }
    
    .wort-game-word-line .word-slot {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
}

@media (max-width: 480px) {
    .wort-game-playfield .letter-btn {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
    }
    
    .wort-game-word-line {
        height: 55px;
        min-height: 55px;
        max-height: 55px;
        gap: 8px;
    }
    
    .wort-game-word-line .word-slot {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
    }
}
</style>

<!-- JavaScript bleibt GLEICH - nur audioEnabled Variable ändern -->
<script type="text/javascript">
// Audio-Feature für JavaScript
const audioEnabled = <?php echo $audio_enabled ? 'true' : 'false'; ?>;

jQuery(document).ready(function($) {
    
    // Nur laden wenn Container vorhanden
    if (!$('#wort-spiel-game-container').length) return;
    
    // ALLE BESTEHENDE JAVASCRIPT-LOGIK BLEIBT GLEICH
    // Nur bei Audio-Funktionen: if (audioEnabled) verwenden
    
    // Spiel-Objekt mit angepasster Audio-Logik
    const WortSpielGame = {
        
        // Bestehende Konfiguration...
        gameMode: '<?php echo esc_js($game_mode); ?>',
        currentWord: '',
        selectedLetters: [],
        letterButtons: [],
        currentAudio: null,
        audioCache: {},
        playlist: [],
        gameStartTime: null,
        sessionId: null,
        
        // Wortlisten
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
        
        // BESTEHENDE METHODEN BLEIBEN GLEICH...
        // Nur Audio-Methoden bekommen audioEnabled-Check
        
        init: function() {
            this.sessionId = this.generateSessionId();
            this.bindEvents();
            this.initGame();
        },
        
        bindEvents: function() {
            $('#back-to-menu-btn, #back-to-menu-end-btn').on('click', this.backToMenu.bind(this));
            $('#restart-game-btn').on('click', this.restartGame.bind(this));
            $('#replay-btn').on('click', this.replayAudio.bind(this));
            $('#check-btn').on('click', this.checkWord.bind(this));
            $('#reset-btn').on('click', this.resetGame.bind(this));
            $('#new-word-btn').on('click', this.initGame.bind(this));
            $('#playfield').on('click', '.letter-btn', this.handleLetterClick.bind(this));
        },
        
        generateSessionId: function() {
            const now = new Date();
            return now.toISOString().replace(/[:.-]/g, '_');
        },
        
        initGame: function() {
            const word = this.selectRandomWord();
            if (!word) return;
            
            this.currentWord = word;
            this.gameStartTime = new Date();
            this.selectedLetters = [];
            
            $('#result-display').text('').removeClass('success error');
            $('#error-message').hide();
            $('#end-screen').hide();
            
            this.createLetterButtons();
            this.createWordSlots();
            
            // Audio nur bei Audio-Modi laden
            if (audioEnabled) {
                this.playAudio(this.currentWord, this.getCategory());
            }
        },
        
        getCategory: function() {
            return this.gameMode.replace('-learning', '').replace('-extra', '').replace('-audio', '');
        },
        
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
        
        shuffleArray: function(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        },
        
        createLetterButtons: function() {
            $('#playfield').empty();
            this.letterButtons = [];
            
            const letters = [...this.currentWord];
            this.shuffleArray(letters);
            const layout = this.layouts[Math.floor(Math.random() * this.layouts.length)];
            
            letters.forEach((letter, index) => {
                const btn = $(`<button class="letter-btn" data-letter="${letter}">${letter}</button>`);
                
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
        
        createWordSlots: function() {
            $('#word-line').empty();
            
            for (let i = 0; i < this.currentWord.length; i++) {
                const slot = $('<div class="word-slot"></div>');
                $('#word-line').append(slot);
            }
            
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
        
        handleLetterClick: function(e) {
            const btn = $(e.target);
            const letter = btn.data('letter');
            
            if (btn.hasClass('selected')) return;
            
            const emptySlot = $('#word-line .word-slot').filter(function() {
                return $(this).text().trim() === '';
            }).first();
            
            if (emptySlot.length) {
                emptySlot.text(letter).addClass('filled');
                this.selectedLetters.push(letter);
                btn.addClass('selected');
            }
        },
        
        // Audio-Funktionen (nur wenn audioEnabled)
        playAudio: function(word, category, delay = 700) {
            if (!audioEnabled) return; // SKIP wenn kein Audio-Modus
            
            const key = `${category}_${word}`;
            
            $('#loading-message').show();
            $('#replay-btn').prop('disabled', true);
            
            setTimeout(() => {
                const audio = new Audio(`${wortSpielAjax.pluginUrl}assets/audio/${category}/${word.toLowerCase()}.m4a`);
                
                audio.addEventListener('canplaythrough', () => {
                    this.currentAudio = audio;
                    audio.play();
                    $('#loading-message').hide();
                    $('#replay-btn').prop('disabled', false);
                    $('#error-message').hide();
                    $('#audio-status').text('🔊 Höre gut zu...');
                });
                
                audio.addEventListener('error', () => {
                    $('#loading-message').hide();
                    $('#error-message').show().text(`Audio für "${word}" nicht verfügbar`);
                    $('#replay-btn').prop('disabled', true);
                    $('#audio-status').text(`Gesuchtes Wort: ${word}`);
                });
                
                audio.load();
            }, delay);
        },
        
        replayAudio: function() {
            if (!audioEnabled) {
                // Bei Nicht-Audio-Spielen: Wort kurz anzeigen
                $('#result-display').text(`💡 Gesuchtes Wort: ${this.currentWord}`).removeClass('success error');
                setTimeout(() => {
                    $('#result-display').text('').removeClass('success error');
                }, 2000);
                return;
            }
            
            if (this.currentAudio) {
                this.currentAudio.currentTime = 0;
                this.currentAudio.play();
                $('#audio-status').text('🔊 Wort wird wiederholt...');
            }
        },
        
        checkWord: function() {
            const userWord = this.selectedLetters.join('');
            const slots = $('#word-line .word-slot');
            
            if (userWord === this.currentWord) {
                slots.addClass('correct');
                $('#result-display').text(`🎉 Richtig! Das war: ${this.currentWord}`)
                    .removeClass('error').addClass('success');
                
                this.saveGameResult(this.currentWord, userWord, true);
                
                setTimeout(() => {
                    this.initGame();
                }, 2000);
                
            } else {
                slots.removeClass('correct').addClass('wrong');
                
                $('#result-display').text(`❌ Falsch! Du hattest: "${userWord}" - Richtig: "${this.currentWord}"`)
                    .removeClass('success').addClass('error');
                
                this.saveGameResult(this.currentWord, userWord, false);
                
                setTimeout(() => {
                    slots.removeClass('wrong');
                }, 3000);
            }
        },
        
        resetGame: function() {
            this.selectedLetters = [];
            $('.letter-btn').removeClass('selected');
            
            const slots = $('#word-line .word-slot');
            slots.text('').removeClass('filled correct wrong');
            
            $('#result-display').text('').removeClass('success error');
            if ($('#audio-status').length) {
                $('#audio-status').text('🔊 Höre gut zu...');
            }
        },
        
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
                        console.log('Spiel-Ergebnis gespeichert');
                    } else {
                        console.error('Fehler beim Speichern:', response.data.message);
                    }
                },
                error: function() {
                    console.error('Netzwerk-Fehler beim Speichern');
                }
            });
        },
        
        showEndScreen: function() {
            $('#end-screen').show();
            
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 200,
                    spread: 100,
                    origin: { y: 0.6 }
                });
            }
        },
        
        restartGame: function() {
            this.playlist = [];
            this.playlist.usedUpOnce = false;
            $('#end-screen').hide();
            this.initGame();
        },
        
        backToMenu: function() {
            if (this.currentAudio) {
                this.currentAudio.pause();
                this.currentAudio = null;
            }
            
            const url = new URL(window.location.href);
            url.searchParams.delete('game_mode');
            url.hash = '';
            window.location.href = url.toString();
        }
    };
    
    // Spiel initialisieren
    WortSpielGame.init();
    
    // Spiel-Modus-Titel setzen
    const gameModeNames = {
        'animals': 'Tiere',
        'animals-learning': 'Tiere (Lernmodus)',
        'animals-audio-extra': 'Tiere Audio-Extra',
        'nature': 'Natur',
        'nature-learning': 'Natur (Lernmodus)',
        'colors': 'Farben',
        'colors-learning': 'Farben (Lernmodus)',
        'food': 'Essen',
        'food-learning': 'Essen (Lernmodus)',
        'food-extra': 'Essen (Extra)'
    };
    
    $('#game-mode-title').text(gameModeNames[WortSpielGame.gameMode] || WortSpielGame.gameMode);
    
});
</script>