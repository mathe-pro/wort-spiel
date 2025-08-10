<?php
/**
 * Template für das Audio-Extra Spiel
 * NEUE 3-BEREICHE LAYOUT VERSION
 * 
 * @package WortSpiel
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Berechtigung prüfen
$game_mode = isset($_GET['game_mode']) ? sanitize_text_field($_GET['game_mode']) : 'animals-audio-extra';
$current_user = wp_get_current_user();

$allowed_modes = get_user_meta(get_current_user_id(), 'wort_spiel_allowed_modes', true);
if (empty($allowed_modes)) {
    $allowed_modes = get_option('wort_spiel_default_modes', array('animals', 'nature'));
}

if (!in_array($game_mode, $allowed_modes)) {
    echo '<p>' . __('Sie haben keine Berechtigung für diesen Spielmodus.', 'wort-spiel') . '</p>';
    return;
}
?>

<div id="wort-spiel-audio-extra-container" class="wort-spiel-game">
    
    <!-- ===== HEADER ===== -->
    <div class="game-header">
        <div class="header-left">
            <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
                ← <?php _e('Zurück', 'wort-spiel'); ?>
            </button>
            <h2 id="game-mode-title">🔊 <?php _e('Audio-Extra', 'wort-spiel'); ?></h2>
        </div>
        <div class="header-right">
            <div class="player-info">
                <?php printf(__('Spieler: %s', 'wort-spiel'), '<strong>' . esc_html($current_user->display_name) . '</strong>'); ?>
            </div>
            <button id="replay-btn" class="wort-spiel-btn audio-btn">
                🔊 <?php _e('Wiederholen', 'wort-spiel'); ?>
            </button>
        </div>
    </div>
    
    <!-- ===== GAME-CONTENT ===== -->
    <div class="game-content">
        
        <!-- Audio-Anzeige -->
        <div id="audio-display" class="audio-display">
            <div id="audio-status" class="audio-status">
                <?php _e('Höre das Wort und finde die richtigen Buchstaben!', 'wort-spiel'); ?>
            </div>
        </div>
        
        <!-- Loading & Error Messages -->
        <div id="loading-message" class="loading-message" style="display:none;">
            <?php _e('Lade Audio...', 'wort-spiel'); ?>
        </div>
        <div id="error-message" class="error-message" style="display:none;">
            <?php _e('Audio konnte nicht geladen werden.', 'wort-spiel'); ?>
        </div>
        
        <!-- PLAYFIELD -->
        <div id="playfield" class="playfield audio-extra-playfield">
            <!-- Buchstaben-Buttons werden hier generiert -->
        </div>
        
        <!-- WORD-LINE -->
        <div id="word-line" class="word-line audio-extra-word-line">
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

<!-- ===== AUDIO-EXTRA SPEZIFISCHES CSS ===== -->
<style>
/* PLAYFIELD - Grau/Neutral für Audio-Extra */
.audio-extra-playfield {
    flex: 1 !important;
    position: relative !important;
    background: linear-gradient(135deg, #95a5a6 0%, #bdc3c7 100%) !important;
    border-radius: 15px !important;
    margin-bottom: 20px !important;
    overflow: hidden !important;
    box-shadow: inset 0 2px 10px rgba(0,0,0,0.1) !important;
    min-height: 250px !important;
    width: 100% !important;
    display: block !important;
    visibility: visible !important;
}

/* BUCHSTABEN-BUTTONS - Mehr Buchstaben möglich */
.audio-extra-playfield .letter-btn {
    position: absolute !important;
    width: 60px !important;
    height: 60px !important;
    border: none !important;
    border-radius: 50% !important;
    font-size: 1.5rem !important;
    font-weight: bold !important;
    background: white !important;
    color: #2c3e50 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.audio-extra-playfield .letter-btn:hover {
    background: #ecf0f1 !important;
    transform: translateY(-3px) !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3) !important;
}

.audio-extra-playfield .letter-btn.selected {
    background: #3498db !important;
    color: white !important;
    transform: scale(0.9) !important;
    opacity: 0.7 !important;
}

/* WORD LINE - Drag & Drop optimiert */
.audio-extra-word-line {
    height: 80px !important;
    min-height: 80px !important;
    max-height: 80px !important;
    display: flex !important;
    justify-content: center !important;
    gap: 12px !important;
    align-items: center !important;
    flex-wrap: wrap !important;
    flex-shrink: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.audio-extra-word-line .word-slot {
    width: 60px !important;
    height: 60px !important;
    border: 3px dashed #bdc3c7 !important;
    border-radius: 12px !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    font-size: 1.5rem !important;
    font-weight: bold !important;
    background: white !important;
    cursor: move !important;
    transition: all 0.3s ease !important;
}

.audio-extra-word-line .word-slot:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.audio-extra-word-line .word-slot.filled {
    background: #e3f2fd !important;
    border: 3px solid #2196f3 !important;
    color: #1976d2 !important;
    cursor: pointer !important;
}

.audio-extra-word-line .word-slot.correct {
    background: #4caf50 !important;
    color: white !important;
    border: 3px solid #4caf50 !important;
    animation: correctPulse 0.6s ease !important;
}

.audio-extra-word-line .word-slot.wrong {
    background: #f44336 !important;
    color: white !important;
    border: 3px solid #f44336 !important;
    animation: wrongShake 0.6s ease !important;
}

/* DRAG & DROP STYLES */
.slot-ghost {
    opacity: 0.4 !important;
    background: #e3f2fd !important;
}

.slot-chosen {
    transform: scale(1.05) !important;
    box-shadow: 0 6px 12px rgba(33, 150, 243, 0.3) !important;
}

.slot-drag {
    transform: rotate(5deg) !important;
    z-index: 1000 !important;
}

/* ANIMATIONEN */
@keyframes correctPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 0 25px rgba(76, 175, 80, 0.6); }
}

@keyframes wrongShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .audio-extra-playfield .letter-btn {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.3rem !important;
    }
    
    .audio-extra-word-line {
        height: 70px !important;
        min-height: 70px !important;
        max-height: 70px !important;
        gap: 10px !important;
    }
    
    .audio-extra-word-line .word-slot {
        width: 50px !important;
        height: 50px !important;
        font-size: 1.3rem !important;
    }
}

@media (max-width: 480px) {
    .audio-extra-playfield .letter-btn {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.2rem !important;
    }
    
    .audio-extra-word-line {
        height: 60px !important;
        min-height: 60px !important;
        max-height: 60px !important;
        gap: 8px !important;
    }
    
    .audio-extra-word-line .word-slot {
        width: 45px !important;
        height: 45px !important;
        font-size: 1.2rem !important;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Nur laden wenn Container vorhanden
    if (!$('#wort-spiel-audio-extra-container').length) return;
    
    console.log('Audio-Extra-Spiel initialisiert - Neues Layout');
    
    // SortableJS Library für Drag & Drop einbinden
    if (typeof Sortable === 'undefined') {
        $('<script>').attr('src', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js').appendTo('head');
    }
    
    // Wortlisten mit zusätzlichen Buchstaben (Distraktoren)
    const wordLists = {
        animals: {
            words: ["KATZE", "HUND", "VOGEL", "FISCH", "PFERD", "MAUS", "FUCHS", "WOLF", "BÄR", "LÖWE"],
            extraLetters: ["P", "X", "Y", "B", "J", "Q", "W", "K"]
        },
        nature: {
            words: ["BAUM", "BLUME", "SONNE", "MOND", "STERN", "BERG", "MEER", "FLUSS", "WALD", "WIESE"],
            extraLetters: ["K", "X", "P", "Q", "J", "Y", "V", "C"]
        },
        colors: {
            words: ["ROT", "BLAU", "GRÜN", "GELB", "LILA", "ROSA", "BRAUN", "GRAU", "WEISS", "ORANGE"],
            extraLetters: ["X", "K", "P", "Q", "J", "Y", "V", "C"]
        },
        food: {
            words: ["BROT", "KÄSE", "MILCH", "APFEL", "BANANE", "PIZZA", "NUDELN", "REIS", "FLEISCH", "GEMÜSE"],
            extraLetters: ["X", "K", "Y", "Q", "J", "W", "V", "C"]
        }
    };
    
    // Erweiterte Layouts für mehr Buchstaben
    const layouts = [
        [
            {"left":15,"top":15},{"left":45,"top":10},{"left":75,"top":15},
            {"left":85,"top":45},{"left":75,"top":75},{"left":45,"top":80},
            {"left":15,"top":75},{"left":5,"top":45},{"left":30,"top":35},
            {"left":60,"top":35},{"left":30,"top":55},{"left":60,"top":55}
        ],
        [
            {"left":25,"top":20},{"left":55,"top":15},{"left":75,"top":25},
            {"left":80,"top":50},{"left":70,"top":75},{"left":40,"top":80},
            {"left":20,"top":70},{"left":10,"top":45},{"left":40,"top":40},
            {"left":65,"top":45},{"left":50,"top":60},{"left":35,"top":60}
        ],
        [
            {"left":20,"top":10},{"left":50,"top":5},{"left":80,"top":10},
            {"left":85,"top":35},{"left":85,"top":65},{"left":60,"top":85},
            {"left":30,"top":85},{"left":5,"top":65},{"left":5,"top":35},
            {"left":35,"top":30},{"left":65,"top":30},{"left":50,"top":50}
        ]
    ];
    
    let currentWord = '';
    let selectedLetters = [];
    let letterButtons = [];
    let currentCategory = getGameMode();
    let currentAudio = null;
    let audioCache = {};
    let playlist = [];
    let sessionId = generateSessionId();
    let gameStartTime = new Date();
    
    // DOM Elemente
    const playfield = $('#playfield');
    const wordLine = $('#word-line');
    /*const audioStatus = $('#audio-status');*/
    const replayBtn = $('#replay-btn');
    const checkBtn = $('#check-btn');
    const resetBtn = $('#reset-btn');
    const newWordBtn = $('#new-word-btn');
    const resultDisplay = $('#result-display');
    const loadingMessage = $('#loading-message');
    const errorMessage = $('#error-message');
    const endScreen = $('#end-screen');
    
    // Session-ID generieren
    function generateSessionId() {
        const now = new Date();
        return now.toISOString().replace(/[:.-]/g, '_');
    }
    
    // Game Mode ermitteln
    function getGameMode() {
        const urlParams = new URLSearchParams(window.location.search);
        const mode = urlParams.get('game_mode') || 'animals-audio-extra';
        
        // Kategorie aus dem Modus extrahieren (z.B. animals-audio-extra -> animals)
        const parts = mode.split('-');
        if (parts.length > 0 && wordLists[parts[0]]) {
            return parts[0];
        }
        
        return 'animals'; // Fallback
    }
    
    // Audio-Verwaltung
    function getAudioFileName(word) {
        return word.toLowerCase()
            .replace('ä', 'ae')
            .replace('ö', 'oe')
            .replace('ü', 'ue')
            .replace('ß', 'ss');
    }
    
    function getAudioPath(word, category) {
        const fileName = getAudioFileName(word);
        const pluginPath = wortSpielAjax ? wortSpielAjax.pluginUrl : '/wp-content/plugins/wort-spiel/';
        return `${pluginPath}assets/audio/${category}/${fileName}.m4a`;
    }
    
    async function loadAudio(word, category) {
        const key = `${category}_${word}`;
        
        if (audioCache[key]) {
            return audioCache[key];
        }
        
        return new Promise((resolve, reject) => {
            const audio = new Audio(getAudioPath(word, category));
            
            audio.addEventListener('canplaythrough', () => {
                audioCache[key] = audio;
                resolve(audio);
            });
            
            audio.addEventListener('error', () => {
                console.error(`Audio nicht gefunden: ${getAudioPath(word, category)}`);
                reject(new Error(`Audio für "${word}" nicht gefunden`));
            });
            
            audio.load();
        });
    }
    
    async function playAudio(word, category, delay = 700) {
        try {
            loadingMessage.show();
            replayBtn.prop('disabled', true);
            
            const audio = await loadAudio(word, category);
            currentAudio = audio;
            
            setTimeout(() => {
                loadingMessage.hide();
                replayBtn.prop('disabled', false);
                errorMessage.hide();
                
                audio.currentTime = 0;
                audio.play();
                
                /*audioStatus.text('🔊 Höre gut zu...');*/
            }, delay);
            
        } catch (error) {
            loadingMessage.hide();
            errorMessage.show().text(`Audio für "${word}" nicht verfügbar`);
            replayBtn.prop('disabled', true);
            /*audioStatus.text(`Gesuchtes Wort: ${word}`);*/
        }
    }
    
    function replayAudio() {
        if (currentAudio) {
            currentAudio.currentTime = 0;
            currentAudio.play();
            /*audioStatus.text('🔊 Wort wird wiederholt...');*/
        }
    }
    
    // Hilfsfunktionen
    function shuffle(array) {
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }
    
    function selectRandomWord() {
        if (playlist.length === 0) {
            const wordData = wordLists[currentCategory];
            const words = wordData ? wordData.words : [];
            
            if (!words || words.length === 0) {
                console.log('Keine Wörter gefunden für Kategorie:', currentCategory);
                return '';
            }
            
            if (playlist.usedUpOnce) {
                showEndScreen();
                return null;
            }
            
            playlist = [...words];
            shuffle(playlist);
            playlist.usedUpOnce = true;
        }
        
        return playlist.pop();
    }
    
    function createLetterButtons() {
        playfield.empty();
        letterButtons = [];
        
        // Buchstaben aus dem aktuellen Wort
        const wordLetters = [...currentWord];
        
        // Zusätzliche Buchstaben (Distraktoren)
        const categoryData = wordLists[currentCategory];
        const extraLetters = categoryData ? categoryData.extraLetters : [];
        
        // Alle Buchstaben kombinieren
        const allLetters = [...wordLetters, ...extraLetters];
        shuffle(allLetters);
        
        // Layout auswählen
        const layout = layouts[Math.floor(Math.random() * layouts.length)];
        
        allLetters.forEach((letter, index) => {
            const btn = $('<button></button>')
                .text(letter)
                .addClass('letter-btn')
                .attr('data-letter', letter);
            
            if (layout[index]) {
                btn.css({
                    left: layout[index].left + '%',
                    top: layout[index].top + '%'
                });
            }
            
            playfield.append(btn);
            letterButtons.push(btn);
        });
    }
    
    function createWordSlots() {
        wordLine.empty();
        for (let i = 0; i < currentWord.length; i++) {
            const slot = $('<div></div>')
                .addClass('word-slot')
                .attr('data-index', i);
            wordLine.append(slot);
        }
        
        // Drag & Drop für Slots aktivieren
        initSortableSlots();
    }
    
    function initSortableSlots() {
        // SortableJS für Drag & Drop der Slots
        if (typeof Sortable !== 'undefined') {
            new Sortable(wordLine[0], {
                animation: 150,
                ghostClass: 'slot-ghost',
                chosenClass: 'slot-chosen',
                dragClass: 'slot-drag',
                onSort: function(evt) {
                    // selectedLetters Array nach dem Sortieren aktualisieren
                    const newSlots = wordLine.find('.word-slot');
                    selectedLetters = [];
                    
                    newSlots.each(function() {
                        const letter = $(this).text().trim();
                        if (letter) {
                            selectedLetters.push(letter);
                        }
                    });
                    
                    console.log('Neue Reihenfolge:', selectedLetters);
                }
            });
        } else {
            console.warn('SortableJS nicht verfügbar - Drag & Drop deaktiviert');
        }
    }
    
    async function initGame() {
        if (!currentCategory) return;
        
        const word = selectRandomWord();
        if (!word) return;
        
        currentWord = word;
        gameStartTime = new Date();
        selectedLetters = [];
        
        resultDisplay.text('').removeClass('success error');
        errorMessage.hide();
        /*audioStatus.text('Lade neues Wort...');*/
        
        createLetterButtons();
        createWordSlots();
        
        await playAudio(currentWord, currentCategory);
    }
    
    function checkWord() {
        const userWord = selectedLetters.join('');
        const slots = wordLine.find('.word-slot');
        
        if (userWord === currentWord) {
            slots.addClass('correct');
            resultDisplay.text('🎉 Richtig! Das war: ' + currentWord).addClass('success');
            /*audioStatus.text('✅ Perfekt gelöst!');*/
            
            saveGameResult(currentWord, userWord, true);
            
            setTimeout(() => {
                initGame();
            }, 2000);
            
        } else {
            slots.addClass('wrong');
            resultDisplay.text('❌ Falsch! Du hattest: "' + userWord + '" - Richtig: "' + currentWord + '"').addClass('error');
            
            saveGameResult(currentWord, userWord, false);
            
            setTimeout(() => {
                slots.removeClass('wrong');
            }, 3000);
        }
    }
    
    function resetGame() {
        selectedLetters = [];
        letterButtons.forEach(btn => btn.removeClass('selected'));
        
        const slots = wordLine.find('.word-slot');
        slots.each(function() {
            $(this).text('').removeClass('filled correct wrong');
        });
        
        resultDisplay.text('').removeClass('success error');
        /*audioStatus.text('🔊 Höre gut zu...');*/
    }
    
    function showEndScreen() {
        endScreen.show();
        
        // Konfetti-Effekt
        if (typeof confetti !== 'undefined') {
            confetti({
                particleCount: 200,
                spread: 100,
                origin: { y: 0.6 }
            });
        }
    }
    
    // Spiel-Ergebnis speichern
    function saveGameResult(targetWord, userInput, isCorrect) {
        const gameEndTime = new Date();
        const duration = Math.round((gameEndTime - gameStartTime) / 1000);
        
        $.ajax({
            url: wortSpielAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wort_spiel_save_game',
                nonce: wortSpielAjax.nonce,
                session_id: sessionId,
                game_mode: currentCategory + '-audio-extra',
                target_word: targetWord,
                user_input: userInput,
                is_correct: isCorrect ? 1 : 0,
                duration: duration
            },
            success: function(response) {
                if (response.success) {
                    console.log('Ergebnis gespeichert');
                } else {
                    console.error('Fehler beim Speichern:', response.data?.message);
                }
            },
            error: function() {
                console.error('AJAX-Fehler beim Speichern');
            }
        });
    }
    
    // Event Listeners
    
    // Buchstaben im Playfield anklicken
    playfield.on('click', '.letter-btn', function() {
        const btn = $(this);
        const letter = btn.attr('data-letter');
        
        if (btn.hasClass('selected')) return;
        
        // Ersten leeren Slot finden
        const emptySlot = wordLine.find('.word-slot').filter(function() {
            return $(this).text() === '';
        }).first();
        
        if (emptySlot.length) {
            emptySlot.text(letter).addClass('filled');
            selectedLetters.push(letter);
            btn.addClass('selected');
        }
    });
    
    // Buchstaben in der Lösungszeile anklicken (entfernen)
    wordLine.on('click', '.word-slot', function() {
        const slot = $(this);
        const letter = slot.text().trim();
        
        if (!letter) return;
        
        // Slot leeren
        slot.text('').removeClass('filled');
        
        // Buchstaben aus selectedLetters entfernen
        const index = selectedLetters.indexOf(letter);
        if (index !== -1) {
            selectedLetters.splice(index, 1);
        }
        
        // Passenden Button wieder aktivieren
        const matchingBtn = letterButtons.find(b => 
            $(b).attr('data-letter') === letter && $(b).hasClass('selected')
        );
        if (matchingBtn) {
            $(matchingBtn).removeClass('selected');
        }
    });
    
    // Control Buttons
    checkBtn.on('click', checkWord);
    resetBtn.on('click', resetGame);
    newWordBtn.on('click', initGame);
    replayBtn.on('click', replayAudio);
    
    // End Screen Buttons
    $('#restart-game-btn').on('click', function() {
        playlist = [];
        playlist.usedUpOnce = false;
        endScreen.hide();
        initGame();
    });
    
    $('#back-to-menu-end-btn').on('click', function() {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }
        
        const url = new URL(window.location.href);
        url.searchParams.delete('game_mode');
        url.hash = '';
        window.location.href = url.toString();
    });
    
    // Zurück zum Menü
    $('#back-to-menu-btn').on('click', function() {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }
        
        const url = new URL(window.location.href);
        url.searchParams.delete('game_mode');
        url.hash = '';
        window.location.href = url.toString();
    });
    
    // Spiel-Modus-Titel setzen
    const gameModeNames = {
        'animals': 'Tiere Audio-Extra',
        'nature': 'Natur Audio-Extra',
        'colors': 'Farben Audio-Extra',
        'food': 'Essen Audio-Extra'
    };
    
    $('#game-mode-title').text('🔊 ' + (gameModeNames[currentCategory] || 'Audio-Extra'));
    
    // Spiel starten
    console.log('Starte Audio-Extra-Spiel mit Kategorie:', currentCategory);
    initGame();
    
});
</script>