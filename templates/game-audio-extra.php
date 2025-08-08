<?php
// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Berechtigung prüfen
$game_mode = isset($_GET['game_mode']) ? sanitize_text_field($_GET['game_mode']) : 'audio-extra';
$current_user = wp_get_current_user();

$allowed_modes = get_user_meta(get_current_user_id(), 'wort_spiel_allowed_modes', true);
if (empty($allowed_modes)) {
    $allowed_modes = get_option('wort_spiel_default_modes', array('animals', 'nature'));
}

if (!in_array($game_mode, $allowed_modes)) {
    echo '<p>Sie haben keine Berechtigung für diesen Spielmodus.</p>';
    return;
}
?>

<div id="wort-spiel-audio-extra-container" class="wort-spiel-audio-extra">
    
    <!-- Zurück Button -->
    <button id="back-to-menu-btn" class="wort-spiel-btn back-btn">
        ← Zurück zum Menü
    </button>
    
    <!-- Spiel Header -->
    <div class="game-header">
        <h2>🔊 Audio-Wörter Extra</h2>
        <div class="player-info">
            Spieler: <strong><?php echo esc_html($current_user->display_name); ?></strong>
        </div>
        <div class="game-instructions">
            Höre das Wort und finde die richtigen Buchstaben! Klicke auf Buchstaben in der Lösungszeile, um sie zu entfernen.
        </div>
    </div>
    
    <!-- Audio-Bereich -->
    <div id="audio-display">
        <div id="audio-status">Höre gut zu...</div>
        <button id="replay-btn" class="wort-spiel-btn audio-btn">🔊 Wort wiederholen</button>
    </div>
    
    <div id="loading" style="display:none;">Lade Audio...</div>
    <div id="error" style="display:none;">Audio konnte nicht geladen werden.</div>
    
    <!-- Spiel-Bereich -->
    <div id="game-area">
        <div id="playfield"></div>
        <div id="word-line"></div>
        
        <div id="controls">
            <button id="check-btn" class="wort-spiel-btn success-btn">Prüfen</button>
            <button id="reset-btn" class="wort-spiel-btn danger-btn">Zurücksetzen</button>
            <button id="new-word-btn" class="wort-spiel-btn primary-btn">Neues Wort</button>
        </div>
        
        <div id="result"></div>
    </div>
    
    <!-- End Screen -->
    <div id="end-screen" style="display:none;">
        <div class="end-message">🎉 Du hast alle Wörter geschafft!</div>
        <div class="end-controls">
            <button id="restart-btn" class="wort-spiel-btn primary-btn">🔁 Neues Spiel</button>
            <button id="exit-btn" class="wort-spiel-btn secondary-btn">🚪 Beenden</button>
        </div>
    </div>
    
</div>

<style>
.wort-spiel-audio-extra {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    user-select: none;
    -webkit-user-select: none;
    touch-action: manipulation;
}

.back-btn {
    position: absolute;
    top: 20px;
    left: 20px;
    background: #666;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

.back-btn:hover {
    background: #555;
    transform: translateY(-2px);
}

.game-header {
    text-align: center;
    margin-bottom: 30px;
    margin-top: 60px;
}

.game-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 2.5rem;
}

.player-info {
    font-size: 1.2rem;
    color: #7f8c8d;
    margin-bottom: 15px;
}

.game-instructions {
    background: #e8f4fd;
    padding: 15px;
    border-radius: 10px;
    color: #2980b9;
    font-weight: 500;
    max-width: 600px;
    margin: 0 auto;
}

/* Audio-Bereich */
#audio-display {
    text-align: center;
    margin-bottom: 30px;
}

#audio-status {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 15px;
    min-height: 30px;
}

.audio-btn {
    background: #2196f3;
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 10px;
    font-size: 1.2rem;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.audio-btn:hover {
    background: #1976d2;
    transform: translateY(-2px);
}

.audio-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

#loading, #error {
    text-align: center;
    font-size: 1.2rem;
    margin-bottom: 20px;
}

#loading {
    color: #666;
}

#error {
    color: #f44336;
}

/* Spielfeld */
#playfield {
    position: relative;
    width: 100%;
    height: 400px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    margin-bottom: 30px;
    overflow: hidden;
}

.letter-btn {
    position: absolute;
    width: 70px;
    height: 70px;
    border: none;
    border-radius: 50%;
    font-size: 1.8rem;
    font-weight: bold;
    background: #ecf0f1;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.letter-btn:hover {
    background: #bdc3c7;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.letter-btn.selected {
    background: #3498db;
    color: white;
    transform: scale(0.9);
    opacity: 0.7;
}

/* Wort-Linie */
#word-line {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 30px;
    min-height: 80px;
    align-items: center;
    flex-wrap: wrap;
}

.word-slot {
    width: 70px;
    height: 70px;
    border: 3px dashed #bdc3c7;
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.8rem;
    font-weight: bold;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}

/* Drag & Drop Styles für Slots */
.slot-ghost {
    opacity: 0.4;
    background: #e3f2fd !important;
}

.slot-chosen {
    transform: scale(1.05);
    box-shadow: 0 6px 12px rgba(33, 150, 243, 0.3);
}

.slot-drag {
    transform: rotate(5deg);
    z-index: 1000;
}

.word-slot {
    cursor: move; /* Zeigt an dass Slots bewegbar sind */
}

.word-slot.filled {
    background: #e3f2fd;
    border: 3px solid #2196f3;
    color: #1976d2;
    cursor: pointer; /* Klickbar zum Entfernen */
}

.word-slot:hover {
    transform: translateY(-2px);
    transition: all 0.2s;
}

.word-slot.correct {
    background: #4caf50;
    color: white;
    border: 3px solid #4caf50;
    animation: correctPulse 0.6s ease;
}

.word-slot.wrong {
    background: #f44336;
    color: white;
    border: 3px solid #f44336;
    animation: wrongShake 0.6s ease;
}

@keyframes correctPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@keyframes wrongShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Controls */
#controls {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.wort-spiel-btn {
    padding: 15px 30px;
    font-size: 1.1rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    min-width: 120px;
}

.primary-btn {
    background: #2196f3;
    color: white;
}

.primary-btn:hover {
    background: #1976d2;
    transform: translateY(-2px);
}

.success-btn {
    background: #4caf50;
    color: white;
}

.success-btn:hover {
    background: #45a049;
    transform: translateY(-2px);
}

.danger-btn {
    background: #f44336;
    color: white;
}

.danger-btn:hover {
    background: #da190b;
    transform: translateY(-2px);
}

.secondary-btn {
    background: #95a5a6;
    color: white;
}

.secondary-btn:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

/* Result */
#result {
    text-align: center;
    font-size: 1.5rem;
    font-weight: bold;
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.success {
    color: #4caf50;
}

.error {
    color: #f44336;
}

/* End Screen */
#end-screen {
    text-align: center;
    flex-direction: column;
    align-items: center;
    gap: 30px;
    margin-top: 50px;
}

.end-message {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.end-controls {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

/* Responsive */
@media (max-width: 768px) {
    #playfield {
        height: 300px;
    }
    
    .letter-btn {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .word-slot {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    #controls {
        flex-direction: column;
        align-items: center;
    }
    
    .wort-spiel-btn {
        min-width: 200px;
    }
    
    .game-header h2 {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .wort-spiel-audio-extra {
        padding: 10px;
    }
    
    .letter-btn {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    .word-slot {
        width: 50px;
        height: 50px;
        font-size: 1.3rem;
    }
    
    #word-line {
        gap: 8px;
    }
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Nur laden wenn Container vorhanden
    if (!$('#wort-spiel-audio-extra-container').length) return;
    
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
    const audioStatus = $('#audio-status');
    const replayBtn = $('#replay-btn');
    const checkBtn = $('#check-btn');
    const resetBtn = $('#reset-btn');
    const newWordBtn = $('#new-word-btn');
    const result = $('#result');
    const loading = $('#loading');
    const errorDiv = $('#error');
    const endScreen = $('#end-screen');
    
    // Session-ID generieren
    function generateSessionId() {
        const now = new Date();
        return now.toISOString().replace(/[:.-]/g, '_');
    }
    
    // Game Mode ermitteln
    function getGameMode() {
        const urlParams = new URLSearchParams(window.location.search);
        const mode = urlParams.get('game_mode') || 'audio-extra';
        
        // Kategorie aus dem Modus extrahieren (z.B. animals-audio-extra -> animals)
        const parts = mode.split('-');
        if (parts.length > 1 && wordLists[parts[0]]) {
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
        // PFAD ANPASSEN: Hier kannst du den Audio-Pfad ändern
        return `${pluginPath}assets/audio/${category}/${fileName}.m4a`;
        
        // ALTERNATIVE PFADE (auskommentiert):
        // return `${pluginPath}audio/${category}/${fileName}.m4a`;  // Andere Dateiendung
        // return `/wp-content/uploads/audio/${category}/${fileName}.mp3`;  // Uploads-Ordner
        // return `./audio/${category}/${fileName}.mp3`;  // Relativer Pfad
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
            loading.show();
            replayBtn.prop('disabled', true);
            
            const audio = await loadAudio(word, category);
            currentAudio = audio;
            
            setTimeout(() => {
                loading.hide();
                replayBtn.prop('disabled', false);
                errorDiv.hide();
                
                audio.currentTime = 0;
                audio.play();
                
                audioStatus.text('');
            }, delay);
            
        } catch (error) {
            loading.hide();
            errorDiv.show().text(`Audio für "${word}" nicht verfügbar`);
            replayBtn.prop('disabled', true);
            audioStatus.text('');
        }
    }
    
    function replayAudio() {
        if (currentAudio) {
            currentAudio.currentTime = 0;
            currentAudio.play();
            audioStatus.text('');
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
        
        result.text('').removeClass();
        errorDiv.hide();
        audioStatus.text('');
        
        createLetterButtons();
        createWordSlots();
        
        await playAudio(currentWord, currentCategory);
    }
    
    function checkWord() {
        const userWord = selectedLetters.join('');
        const slots = wordLine.find('.word-slot');
        
        if (userWord === currentWord) {
            slots.addClass('correct');
            result.text('🎉 Richtig!').addClass('success');
            audioStatus.text('');
            
            saveGameResult(currentWord, userWord, true);
            
            setTimeout(() => {
                initGame();
            }, 1500);
            
        } else {
            result.text('❌ Falsch').addClass('error');
            saveGameResult(currentWord, userWord, false);
            
            setTimeout(() => {
                slots.removeClass('wrong');
            }, 2000);
        }
    }
    
    function resetGame() {
        selectedLetters = [];
        letterButtons.forEach(btn => btn.removeClass('selected'));
        
        const slots = wordLine.find('.word-slot');
        slots.each(function() {
            $(this).text('').removeClass('filled correct wrong');
        });
        
        result.text('').removeClass();
        audioStatus.text('');
    }
    
    function showEndScreen() {
        $('#controls').hide();
        newWordBtn.prop('disabled', true);
        endScreen.show();
        
        // Konfetti-Effekt (falls verfügbar)
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
                game_mode: 'audio-extra-' + currentCategory,
                target_word: targetWord,
                user_input: userInput,
                is_correct: isCorrect ? 1 : 0,
                duration: duration
            },
            success: function(response) {
                console.log('Ergebnis gespeichert:', response);
            },
            error: function(xhr, status, error) {
                console.error('Fehler beim Speichern:', error);
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
        const btn = letterButtons.find(b => 
            b.attr('data-letter') === letter && b.hasClass('selected')
        );
        if (btn) {
            btn.removeClass('selected');
        }
    });
    
    // Control Buttons
    checkBtn.on('click', checkWord);
    resetBtn.on('click', resetGame);
    newWordBtn.on('click', initGame);
    replayBtn.on('click', replayAudio);
    
    // End Screen Buttons
    $('#restart-btn').on('click', function() {
        playlist = [];
        playlist.usedUpOnce = false;
        endScreen.hide();
        $('#controls').show();
        newWordBtn.prop('disabled', false);
        initGame();
    });
    
    $('#exit-btn').on('click', function() {
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
    
    // Spiel starten
    console.log('Starte Audio-Extra-Spiel mit Kategorie:', currentCategory);
    initGame();
    
});
</script>