/**
 * Wort-Spiel WordPress Plugin JavaScript
 * 
 * @package WortSpiel
 */

jQuery(document).ready(function($) {
    
    // Nur laden wenn Spiel-Container vorhanden
    if (!$('#wort-spiel-game-container').length) return;
    
    // Spiel-Objekt
    const WortSpielGame = {
        
        // Konfiguration
        gameMode: '',
        currentWord: '',
        selectedLetters: [],
        letterButtons: [],
        currentAudio: null,
        audioCache: {},
        playlist: [],
        gameStartTime: null,
        sessionId: null,
        gameCounter: 0,
        audioTimeout: null, // NEU: Timeout-Referenz speichern
        
        // Wortlisten (Fallback)
        wordLists: {
            animals: ["KATZE", "HUND", "VOGEL", "FISCH", "PFERD", "MAUS", "FUCHS", "WOLF", "BÄR", "LÖWE"],
            nature: ["BAUM", "BLUME", "SONNE", "MOND", "STERN", "BERG", "MEER", "FLUSS", "WALD", "WIESE"],
            colors: ["ROT", "BLAU", "GRÜN", "GELB", "LILA", "ROSA", "BRAUN", "GRAU", "WEISS", "ORANGE"],
            food: ["BROT", "KÄSE", "MILCH", "APFEL", "BANANE", "PIZZA", "NUDELN", "REIS", "FLEISCH", "GEMÜSE"]
        },
        
        // Button-Layouts (FIXED!)
        layouts: [
            [
                {"left": 24.7, "top": 20.1},
                {"left": 73.7, "top": 7.0},
                {"left": 63.2, "top": 66.8},
                {"left": 82.3, "top": 61.9},
                {"left": 7.7, "top": 6.9}
            ],
            [
                {"left": 62.5, "top": 60.6},
                {"left": 28.5, "top": 66.8},
                {"left": 30.0, "top": 31.8},
                {"left": 85.5, "top": 16.0},
                {"left": 82.8, "top": 55.5}
            ]
        ],
        
        // Initialisierung
        init: function() {
            console.log('WortSpielGame.init() gestartet');
            
            // Game Mode aus URL oder Data-Attribut
            this.gameMode = this.getGameMode();
            console.log('Game Mode:', this.gameMode);
            
            if (!this.gameMode) {
                console.error('Kein Game Mode gefunden!');
                return;
            }
            
            this.sessionId = this.generateSessionId();
            this.bindEvents();
            this.initGame();
        },
        
        // Game Mode ermitteln
        getGameMode: function() {
            // Aus URL Parameter
            const urlParams = new URLSearchParams(window.location.search);
            let mode = urlParams.get('game_mode');
            
            // Aus Container Data-Attribut
            if (!mode) {
                mode = $('#wort-spiel-game-container').data('game-mode');
            }
            
            // Fallback
            if (!mode) {
                mode = 'animals';
            }
            
            return mode;
        },
        
        // Event-Handler
        bindEvents: function() {
            console.log('Events werden gebunden...');
            
            // WICHTIG: Erst alle Events entfernen!
            $('#back-to-menu-btn, #back-to-menu-end-btn').off('click');
            $('#restart-game-btn').off('click');
            $('#replay-btn').off('click');
            $('#check-btn').off('click');
            $('#reset-btn').off('click');
            $('#new-word-btn').off('click');
            $('#playfield').off('click', '.letter-btn');
            
            // Dann neu binden
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
            console.log('initGame() gestartet');
            
            // WICHTIG: Audio vom vorherigen Wort stoppen!
            this.stopCurrentAudio();
            
            const word = this.selectRandomWord();
            if (!word) {
                console.log('Kein Wort mehr, Ende des Spiels');
                return;
            }
            
            console.log('Gewähltes Wort:', word);
            
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
            console.log('createLetterButtons() für Wort:', this.currentWord);
            
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
                
                // Position setzen (mit Fallback)
                if (layout && layout[index]) {
                    btn.css({
                        left: layout[index].left + '%',
                        top: layout[index].top + '%'
                    });
                } else {
                    // Fallback-Position wenn Layout fehlt
                    btn.css({
                        left: (10 + (index * 15)) + '%',
                        top: (20 + (index % 2) * 40) + '%'
                    });
                }
                
                $('#playfield').append(btn);
                this.letterButtons.push(btn[0]);
            });
            
            console.log('Buchstaben-Buttons erstellt:', letters.length);
        },
        
        // Wort-Slots erstellen
        createWordSlots: function() {
            console.log('createWordSlots() für Länge:', this.currentWord.length);
            
            $('#word-line').empty();
            
            for (let i = 0; i < this.currentWord.length; i++) {
                const slot = $('<div class="word-slot"></div>');
                $('#word-line').append(slot);
            }
            
            // Sortable aktivieren (falls verfügbar)
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
                        console.log('Slots neu sortiert:', this.selectedLetters);
                    }
                });
            }
        },
        
        // Buchstaben-Klick behandeln
        handleLetterClick: function(e) {
            console.log('Buchstaben geklickt');
            
            const btn = $(e.target);
            const letter = btn.data('letter');
            
            if (btn.hasClass('selected')) {
                console.log('Buchstabe bereits ausgewählt');
                return;
            }
            
            // Ersten leeren Slot finden
            const emptySlot = $('#word-line .word-slot').filter(function() {
                return $(this).text().trim() === '';
            }).first();
            
            if (emptySlot.length) {
                emptySlot.text(letter).addClass('filled');
                this.selectedLetters.push(letter);
                btn.addClass('selected');
                console.log('Buchstabe hinzugefügt:', letter, 'Aktuell:', this.selectedLetters);
            } else {
                console.log('Keine leeren Slots mehr verfügbar');
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
            // Prüfe ob wortSpielAjax verfügbar ist
            if (typeof wortSpielAjax !== 'undefined' && wortSpielAjax.pluginUrl) {
                return `${wortSpielAjax.pluginUrl}assets/audio/${category}/${fileName}.m4a`;
            } else {
                // Fallback für XAMPP
                return `./wp-content/plugins/wort-spiel/assets/audio/${category}/${fileName}.m4a`;
            }
        },
        
        playAudio: function(word, category, delay = 700) {
            console.log('=== PLAY AUDIO START ===', word, category, 'Key:', `${category}_${word}`);
            
            // WICHTIG: Vorheriges Audio stoppen!
            this.stopCurrentAudio();
            
            const key = `${category}_${word}`;
            
            $('#loading-message').show();
            $('#replay-btn').prop('disabled', true);
            
            // WICHTIG: Timeout-Referenz speichern!
            this.audioTimeout = setTimeout(() => {
                if (this.audioCache[key]) {
                    console.log('Audio aus Cache laden für:', key);
                    this.currentAudio = this.audioCache[key];
                    this.currentAudio.currentTime = 0;
                    this.currentAudio.play().catch(e => console.log('Play Error:', e));
                    $('#loading-message').hide();
                    $('#replay-btn').prop('disabled', false);
                    $('#audio-status').text('🔊 Höre gut zu...');
                } else {
                    console.log('Lade neues Audio:', this.getAudioPath(word, category));
                    const audio = new Audio(this.getAudioPath(word, category));
                    
                    // WICHTIG: Event-Handler definieren BEVOR sie gesetzt werden
                    const onCanPlay = () => {
                        console.log('Audio geladen für:', key);
                        // Event-Listener sofort entfernen nach Ausführung!
                        audio.removeEventListener('canplaythrough', onCanPlay);
                        audio.removeEventListener('error', onError);
                        
                        this.audioCache[key] = audio;
                        this.currentAudio = audio;
                        audio.play().catch(e => console.log('Play Error:', e));
                        $('#loading-message').hide();
                        $('#replay-btn').prop('disabled', false);
                        $('#error-message').hide();
                        $('#audio-status').text('🔊 Höre gut zu...');
                    };
                    
                    const onError = (e) => {
                        console.log('Audio-Fehler für:', key, e);
                        // Event-Listener sofort entfernen nach Ausführung!
                        audio.removeEventListener('canplaythrough', onCanPlay);
                        audio.removeEventListener('error', onError);
                        
                        $('#loading-message').hide();
                        $('#error-message').show().text(`Audio für "${word}" nicht verfügbar`);
                        $('#replay-btn').prop('disabled', true);
                        $('#audio-status').text(`Gesuchtes Wort: ${word}`);
                    };
                    
                    // Event-Listener setzen
                    audio.addEventListener('canplaythrough', onCanPlay);
                    audio.addEventListener('error', onError);
                    
                    audio.load();
                }
                
                // Timeout ist abgelaufen
                this.audioTimeout = null;
            }, delay);
        },
        
        // Audio stoppen (ERWEITERT!)
        stopCurrentAudio: function() {
            // WICHTIG: Timeout canceln!
            if (this.audioTimeout) {
                console.log('Cancele Audio-Timeout');
                clearTimeout(this.audioTimeout);
                this.audioTimeout = null;
            }
            
            if (this.currentAudio) {
                console.log('Stoppe vorheriges Audio');
                this.currentAudio.pause();
                this.currentAudio.currentTime = 0;
                this.currentAudio = null;
            }
            
            // Auch loading-Status zurücksetzen
            $('#loading-message').hide();
            $('#replay-btn').prop('disabled', false);
        },.currentAudio.pause();
                this.currentAudio.currentTime = 0;
                this.currentAudio = null;
            }
        },
        
        replayAudio: function() {
            console.log('replayAudio()');
            if (this.currentAudio) {
                this.currentAudio.currentTime = 0;
                this.currentAudio.play().catch(e => console.log('Replay Error:', e));
                $('#audio-status').text('🔊 Wort wird wiederholt...');
            }
        },
        
        // Wort prüfen
        checkWord: function() {
            const userWord = this.selectedLetters.join('');
            console.log('checkWord():', userWord, 'vs', this.currentWord);
            
            const slots = $('#word-line .word-slot');
            
            if (userWord === this.currentWord) {
                // Richtig!
                console.log('RICHTIG!');
                slots.addClass('correct');
                $('#result-display').text(`🎉 Richtig! Das war: ${this.currentWord}`)
                    .removeClass('error').addClass('success');
                $('#audio-status').text('✅ Perfekt gelöst!');
                
                // Ergebnis speichern
                this.saveGameResult(this.currentWord, userWord, true);
                
                // Nach kurzer Verzögerung neues Wort
                setTimeout(() => {
                    this.initGame();
                }, 1000);
                
            } else {
                // Falsch
                console.log('FALSCH!');
                slots.addClass('wrong');
                $('#result-display').text(`❌ Falsch! Du hattest: "${userWord}" - Richtig: "${this.currentWord}"`)
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
            console.log('resetGame()');
            this.selectedLetters = [];
            $('.letter-btn').removeClass('selected');
            
            const slots = $('#word-line .word-slot');
            slots.text('').removeClass('filled correct wrong');
            
            $('#result-display').text('').removeClass('success error');
            $('#audio-status').text('🔊 Höre gut zu...');
        },
        
        // Spiel-Ergebnis speichern
        saveGameResult: function(targetWord, userInput, isCorrect) {
            console.log('saveGameResult():', targetWord, userInput, isCorrect);
            
            const gameEndTime = new Date();
            const duration = this.gameStartTime ? Math.round((gameEndTime - this.gameStartTime) / 1000) : 0;
            
            // Prüfe ob AJAX verfügbar ist
            if (typeof wortSpielAjax === 'undefined') {
                console.log('WordPress AJAX nicht verfügbar - Fallback zu localStorage');
                
                // Fallback: In localStorage speichern
                const gameData = {
                    timestamp: gameEndTime.toISOString(),
                    session_id: this.sessionId,
                    game_mode: this.gameMode,
                    target_word: targetWord,
                    user_input: userInput,
                    is_correct: isCorrect,
                    duration: duration
                };
                
                let history = JSON.parse(localStorage.getItem('wort_spiel_history') || '[]');
                history.push(gameData);
                localStorage.setItem('wort_spiel_history', JSON.stringify(history));
                return;
            }
            
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
                        console.error('Fehler beim Speichern:', response.data?.message || 'Unbekannt');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX-Fehler beim Speichern:', status, error);
                }
            });
        },
        
        // End-Screen anzeigen
        showEndScreen: function() {
            console.log('showEndScreen()');
            $('#game-controls').hide();
            $('#new-word-btn').prop('disabled', true);
            $('#end-screen').show();
            
            // Konfetti-Animation (falls verfügbar)
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
            console.log('restartGame()');
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
            console.log('backToMenu()');
            
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
    
    // Global verfügbar machen
    window.WortSpielGame = WortSpielGame;
    
    // Auto-Start wenn Game-Container vorhanden
    if ($('#wort-spiel-game-container').length) {
        console.log('Game-Container gefunden, starte Spiel...');
        WortSpielGame.init();
    }
});

// Menu-Funktionen (falls auf derselben Seite)
jQuery(document).ready(function($) {
    
    // Nur laden wenn Menu-Container vorhanden
    if (!$('#wort-spiel-container').length) return;
    
    const wortSpielMenu = {
        
        init: function() {
            console.log('Menu init gestartet');
            this.loadUserModes();
            this.bindEvents();
        },
        
        bindEvents: function() {
            // WICHTIG: Doppelbindung verhindern!
            $('#show-history-btn').off('click');
            $('.close-modal').off('click');
            $('#history-modal').off('click');
            
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
            console.log('loadUserModes gestartet');
            
            // Prüfe ob AJAX verfügbar
            if (typeof wortSpielAjax === 'undefined') {
                console.log('WordPress AJAX nicht verfügbar - zeige alle Modi');
                this.displayGameModes(['animals', 'nature', 'food', 'food-extra']);
                return;
            }
            
            $.ajax({
                url: wortSpielAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wort_spiel_get_user_modes',
                    nonce: wortSpielAjax.nonce
                },
                success: function(response) {
                    console.log('AJAX Response:', response);
                    if (response.success && response.data.allowed_modes.length > 0) {
                        wortSpielMenu.displayGameModes(response.data.allowed_modes);
                    } else {
                        wortSpielMenu.showNoModes();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Fehler:', status, error);
                    wortSpielMenu.showError();
                }
            });
        },
        
        displayGameModes: function(allowedModes) {
            console.log('displayGameModes:', allowedModes);
            
            // Alle verfügbaren Spielmodi
            const allGameModes = {
                'animals': {
                    id: 'animals',
                    title: 'Tiere',
                    description: 'Katze, Hund, Vogel und mehr',
                    icon: '🐱'
                },
                'animals-learning': {
                    id: 'animals-learning',
                    title: 'Tiere (Lernmodus)',
                    description: 'Mit sichtbarem Wort - Katze, Hund, Vogel und mehr',
                    icon: '🐱📖'
                },
                'nature': {
                    id: 'nature',
                    title: 'Natur',
                    description: 'Baum, Blume, Sonne und mehr',
                    icon: '🌳'
                },
                'nature-learning': {
                    id: 'nature-learning',
                    title: 'Natur (Lernmodus)',
                    description: 'Mit sichtbarem Wort - Baum, Blume, Sonne und mehr',
                    icon: '🌳📖'
                },
                'colors': {
                    id: 'colors',
                    title: 'Farben',
                    description: 'Rot, Blau, Grün und mehr',
                    icon: '🎨'
                },
                'colors-learning': {
                    id: 'colors-learning',
                    title: 'Farben (Lernmodus)',
                    description: 'Mit sichtbarem Wort - Rot, Blau, Grün und mehr',
                    icon: '🎨📖'
                },
                'food': {
                    id: 'food',
                    title: 'Essen',
                    description: 'Brot, Käse, Apfel und mehr',
                    icon: '🍎'
                },
                'food-learning': {
                    id: 'food-learning',
                    title: 'Essen (Lernmodus)',
                    description: 'Mit sichtbarem Wort - Brot, Käse, Apfel und mehr',
                    icon: '🍎📖'
                },
                'food-extra': {
                    id: 'food-extra',
                    title: 'Essen (Extra)',
                    description: 'Erweiterte Essen-Wörter mit besonderen Features',
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
            
            // Click-Handler für Spielmodi (OHNE DOPPELBINDUNG)
            $('.game-mode-card').off('click').on('click', function() {
                const modeId = $(this).data('mode');
                console.log('Spielmodus gewählt:', modeId);
                wortSpielMenu.startGame(modeId);
            });
        },
        
        startGame: function(modeId) {
            console.log('startGame:', modeId);
            
            // URL mit game_mode Parameter
            const gameUrl = new URL(window.location.href);
            gameUrl.searchParams.set('game_mode', modeId);
            
            console.log('Weiterleitung zu:', gameUrl.toString());
            window.location.href = gameUrl.toString();
        },
        
        showHistory: function() {
            console.log('showHistory');
            $('#history-modal').show();
            this.loadUserHistory();
        },
        
        loadUserHistory: function() {
            // Implementierung folgt...
            $('#user-stats-content').html('<p>History-Funktion wird noch implementiert...</p>');
        },
        
        showNoModes: function() {
            $('#loading-message').hide();
            $('#no-modes-message').show();
        },
        
        showError: function() {
            $('#loading-message').html('Fehler beim Laden der Spielmodi.');
        }
    };
    
    // Menu initialisieren
    wortSpielMenu.init();
});