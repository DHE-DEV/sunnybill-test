<div class="gmail-email-content w-full">
    @if(!empty($html_content))
        <div class="gmail-html-content bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="gmail-content-wrapper">
                {!! $html_content !!}
            </div>
        </div>
    @elseif(!empty($text_content))
        <div class="gmail-text-content bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-sm">
            <div class="whitespace-pre-wrap font-mono text-sm text-gray-700 leading-relaxed">
                {{ $text_content }}
            </div>
        </div>
    @else
        <div class="gmail-no-content bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-medium">Kein E-Mail-Inhalt verfügbar</p>
            </div>
        </div>
    @endif
</div>

<style>
/* Gmail HTML Content Styling - Optimiert für Filament */
.gmail-html-content {
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #374151;
    max-height: 600px;
    overflow-y: auto;
}

.gmail-content-wrapper {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Reset und Sicherheit */
.gmail-html-content * {
    max-width: 100% !important;
    box-sizing: border-box;
}

.gmail-html-content script,
.gmail-html-content style,
.gmail-html-content link[rel="stylesheet"],
.gmail-html-content meta,
.gmail-html-content title {
    display: none !important;
}

/* Typografie */
.gmail-html-content h1,
.gmail-html-content h2,
.gmail-html-content h3,
.gmail-html-content h4,
.gmail-html-content h5,
.gmail-html-content h6 {
    color: #111827;
    margin: 1.5em 0 0.75em 0;
    font-weight: 600;
    line-height: 1.3;
}

.gmail-html-content h1 { font-size: 1.5rem; }
.gmail-html-content h2 { font-size: 1.3rem; }
.gmail-html-content h3 { font-size: 1.1rem; }
.gmail-html-content h4 { font-size: 1rem; }
.gmail-html-content h5 { font-size: 0.9rem; }
.gmail-html-content h6 { font-size: 0.8rem; }

.gmail-html-content p {
    margin: 0.75em 0;
    line-height: 1.6;
}

.gmail-html-content ul,
.gmail-html-content ol {
    margin: 0.75em 0;
    padding-left: 1.5em;
}

.gmail-html-content li {
    margin: 0.25em 0;
}

/* Links */
.gmail-html-content a {
    color: #3b82f6;
    text-decoration: underline;
    word-break: break-all;
}

.gmail-html-content a:hover {
    color: #1d4ed8;
}

/* Bilder */
.gmail-html-content img {
    max-width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

/* Tabellen */
.gmail-html-content table {
    border-collapse: collapse;
    width: 100%;
    margin: 1em 0;
    font-size: 0.9em;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.gmail-html-content td, 
.gmail-html-content th {
    border: 1px solid #e5e7eb;
    padding: 0.75em;
    text-align: left;
    vertical-align: top;
}

.gmail-html-content th {
    background-color: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.gmail-html-content tr:nth-child(even) {
    background-color: #f9fafb;
}

/* Zitate */
.gmail-html-content blockquote,
.gmail-html-content .gmail_quote {
    border-left: 4px solid #e5e7eb;
    margin: 1em 0;
    padding-left: 1em;
    color: #6b7280;
    font-style: italic;
    background-color: #f9fafb;
    border-radius: 0 6px 6px 0;
}

/* Code */
.gmail-html-content pre {
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 1em;
    overflow-x: auto;
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 0.875em;
    line-height: 1.5;
}

.gmail-html-content code {
    background-color: #f3f4f6;
    border-radius: 3px;
    padding: 0.2em 0.4em;
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 0.875em;
}

/* Gmail-spezifische Elemente */
.gmail-html-content .gmail_signature {
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5em;
    padding-top: 1em;
    color: #6b7280;
    font-size: 0.875em;
}

/* Responsive Design */
@media (max-width: 640px) {
    .gmail-html-content {
        font-size: 13px;
        padding: 1rem;
    }
    
    .gmail-html-content table {
        font-size: 11px;
    }
    
    .gmail-html-content td,
    .gmail-html-content th {
        padding: 0.5em;
    }
    
    .gmail-html-content h1 { font-size: 1.3rem; }
    .gmail-html-content h2 { font-size: 1.2rem; }
    .gmail-html-content h3 { font-size: 1.1rem; }
}

/* Scrollbar Styling */
.gmail-html-content::-webkit-scrollbar {
    width: 8px;
}

.gmail-html-content::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.gmail-html-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.gmail-html-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Text Content Styling */
.gmail-text-content {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 13px;
    line-height: 1.6;
    max-height: 600px;
    overflow-y: auto;
}
</style>

@if(!empty($html_content))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const htmlContent = document.querySelector('.gmail-html-content');
    if (htmlContent) {
        // Sicherheits-Sanitization
        const dangerousElements = htmlContent.querySelectorAll('script, style, link[rel="stylesheet"], meta, title');
        dangerousElements.forEach(el => el.remove());
        
        // Links sicher machen
        const externalLinks = htmlContent.querySelectorAll('a[href^="http"]');
        externalLinks.forEach(link => {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
        
        // Bilder optimieren
        const images = htmlContent.querySelectorAll('img');
        images.forEach(img => {
            img.setAttribute('loading', 'lazy');
            img.addEventListener('error', function() {
                this.style.display = 'none';
            });
        });
        
        // Tabellen responsive machen
        const tables = htmlContent.querySelectorAll('table');
        tables.forEach(table => {
            if (!table.parentElement.classList.contains('overflow-x-auto')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'overflow-x-auto';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
        });
    }
});
</script>
@endif
