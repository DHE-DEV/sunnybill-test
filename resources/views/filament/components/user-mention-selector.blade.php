<div class="user-mention-selector">
    <div class="mb-2">
        <span class="text-sm font-medium text-gray-700">Klicken Sie auf einen Benutzer, um ihn zu erwähnennnn:</span>
    </div>
    
    <div class="flex flex-wrap gap-2">
        @foreach($users as $user)
            <button 
                type="button"
                class="user-mention-btn inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                onclick="insertUserMention('{{ $user->name }}')"
                title="@{{ $user->name }} erwähnen"
            >
                <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <span>{{ $user->name }}</span>
                </div>
            </button>
        @endforeach
    </div>
</div>

<script>
function insertUserMention(username) {
    console.log('👤 Benutzer-Button geklickt:', username);
    
    // Finde das Textarea
    const textarea = document.getElementById('task-note-content') || 
                    document.querySelector('textarea[data-mention-enabled="true"]') ||
                    document.querySelector('textarea');
    
    if (!textarea) {
        console.error('❌ Textarea nicht gefunden');
        return;
    }
    
    console.log('📝 Textarea gefunden:', textarea.id);
    
    // Aktueller Cursor-Position und Text
    const cursorPos = textarea.selectionStart;
    const currentText = textarea.value;
    
    // Text vor und nach Cursor
    const textBefore = currentText.substring(0, cursorPos);
    const textAfter = currentText.substring(cursorPos);
    
    // Neuen Text zusammensetzen: " @Username "
    const mentionText = ' @' + username + ' ';
    const newText = textBefore + mentionText + textAfter;
    
    // Text setzen
    textarea.value = newText;
    
    // Cursor nach dem eingefügten Text positionieren
    const newCursorPos = cursorPos + mentionText.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    
    // Focus auf Textarea setzen
    textarea.focus();
    
    // Input Event triggern für Filament
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.dispatchEvent(new Event('change', { bubbles: true }));
    
    console.log('✅ Mention eingefügt:', mentionText);
    console.log('📍 Neue Cursor-Position:', newCursorPos);
    
    // Visuelles Feedback
    const button = event.target.closest('.user-mention-btn');
    if (button) {
        const originalBg = button.style.backgroundColor;
        button.style.backgroundColor = '#10b981';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.style.backgroundColor = originalBg;
            button.style.color = '';
        }, 300);
    }
}

// Debug-Funktion
window.debugUserSelector = function() {
    console.log('🔧 User Selector Debug:');
    console.log('- Textarea gefunden:', !!document.getElementById('task-note-content'));
    console.log('- Benutzer-Buttons:', document.querySelectorAll('.user-mention-btn').length);
    console.log('- Aktueller Textarea-Wert:', document.getElementById('task-note-content')?.value || 'Nicht gefunden');
};

console.log('✅ User Mention Selector geladen');
console.log('💡 Verwende window.debugUserSelector() für Debug-Infos');
</script>

<style>
.user-mention-selector {
    margin-top: 12px;
    padding: 16px;
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

.user-mention-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.user-mention-btn:active {
    transform: translateY(0);
}

.user-mention-btn:focus {
    outline: none;
    ring: 2px;
    ring-color: #3b82f6;
    ring-offset: 2px;
}
</style>