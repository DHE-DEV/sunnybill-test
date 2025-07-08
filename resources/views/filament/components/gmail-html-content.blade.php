<div class="gmail-email-content">
    @if(!empty($html_content))
        <div class="gmail-html-content bg-white border border-gray-200 rounded-lg p-4 max-h-96 overflow-y-auto">
            <div class="prose prose-sm max-w-none">
                {!! $html_content !!}
            </div>
        </div>
    @elseif(!empty($text_content))
        <div class="gmail-text-content bg-gray-50 border border-gray-200 rounded-lg p-4 max-h-96 overflow-y-auto">
            <div class="whitespace-pre-wrap font-mono text-sm text-gray-700">
                {{ $text_content }}
            </div>
        </div>
    @else
        <div class="gmail-no-content bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-sm">Kein E-Mail-Inhalt verfügbar</p>
            </div>
        </div>
    @endif
</div>

<style>
/* Gmail HTML Content Styling */
.gmail-html-content {
    /* Reset some default styles that might interfere */
    line-height: 1.6;
}

.gmail-html-content * {
    max-width: 100% !important;
    height: auto !important;
}

/* Style common email elements */
.gmail-html-content table {
    border-collapse: collapse;
    width: 100%;
    margin: 1em 0;
}

.gmail-html-content td, 
.gmail-html-content th {
    border: 1px solid #e5e7eb;
    padding: 8px;
    text-align: left;
}

.gmail-html-content th {
    background-color: #f9fafb;
    font-weight: 600;
}

.gmail-html-content img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

.gmail-html-content a {
    color: #3b82f6;
    text-decoration: underline;
}

.gmail-html-content a:hover {
    color: #1d4ed8;
}

.gmail-html-content blockquote {
    border-left: 4px solid #e5e7eb;
    margin: 1em 0;
    padding-left: 1em;
    color: #6b7280;
    font-style: italic;
}

.gmail-html-content pre {
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 1em;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}

.gmail-html-content code {
    background-color: #f3f4f6;
    border-radius: 2px;
    padding: 2px 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}

/* Handle Gmail-specific classes */
.gmail-html-content .gmail_quote {
    border-left: 4px solid #e5e7eb;
    margin: 1em 0;
    padding-left: 1em;
    color: #6b7280;
}

.gmail-html-content .gmail_signature {
    border-top: 1px solid #e5e7eb;
    margin-top: 1em;
    padding-top: 1em;
    color: #6b7280;
    font-size: 0.875em;
}

/* Remove potentially dangerous styles */
.gmail-html-content script,
.gmail-html-content style,
.gmail-html-content link[rel="stylesheet"] {
    display: none !important;
}

/* Ensure readability */
.gmail-html-content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    color: #374151;
}

.gmail-html-content h1,
.gmail-html-content h2,
.gmail-html-content h3,
.gmail-html-content h4,
.gmail-html-content h5,
.gmail-html-content h6 {
    color: #111827;
    margin: 1em 0 0.5em 0;
    font-weight: 600;
}

.gmail-html-content h1 { font-size: 1.5em; }
.gmail-html-content h2 { font-size: 1.3em; }
.gmail-html-content h3 { font-size: 1.1em; }
.gmail-html-content h4 { font-size: 1em; }
.gmail-html-content h5 { font-size: 0.9em; }
.gmail-html-content h6 { font-size: 0.8em; }

.gmail-html-content p {
    margin: 0.5em 0;
}

.gmail-html-content ul,
.gmail-html-content ol {
    margin: 0.5em 0;
    padding-left: 2em;
}

.gmail-html-content li {
    margin: 0.25em 0;
}

/* Text content styling */
.gmail-text-content {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.5;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .gmail-html-content {
        font-size: 13px;
    }
    
    .gmail-html-content table {
        font-size: 12px;
    }
    
    .gmail-html-content td,
    .gmail-html-content th {
        padding: 4px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .gmail-html-content {
        background-color: #1f2937;
        color: #f9fafb;
        border-color: #374151;
    }
    
    .gmail-html-content table {
        border-color: #374151;
    }
    
    .gmail-html-content td,
    .gmail-html-content th {
        border-color: #374151;
    }
    
    .gmail-html-content th {
        background-color: #374151;
    }
    
    .gmail-html-content blockquote,
    .gmail-html-content .gmail_quote {
        border-left-color: #6b7280;
        color: #d1d5db;
    }
    
    .gmail-html-content pre,
    .gmail-html-content code {
        background-color: #374151;
        border-color: #4b5563;
    }
    
    .gmail-text-content {
        background-color: #374151;
        color: #f9fafb;
        border-color: #4b5563;
    }
}
</style>

@if(!empty($html_content))
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sanitize and enhance the HTML content
    const htmlContent = document.querySelector('.gmail-html-content');
    if (htmlContent) {
        // Remove any script tags that might have slipped through
        const scripts = htmlContent.querySelectorAll('script');
        scripts.forEach(script => script.remove());
        
        // Remove any style tags
        const styles = htmlContent.querySelectorAll('style');
        styles.forEach(style => style.remove());
        
        // Remove any link tags with rel="stylesheet"
        const links = htmlContent.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => link.remove());
        
        // Handle external links - open in new tab
        const links_external = htmlContent.querySelectorAll('a[href^="http"]');
        links_external.forEach(link => {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
        
        // Handle images - add loading="lazy" and error handling
        const images = htmlContent.querySelectorAll('img');
        images.forEach(img => {
            img.setAttribute('loading', 'lazy');
            img.addEventListener('error', function() {
                this.style.display = 'none';
            });
        });
        
        // Handle tables - make them responsive
        const tables = htmlContent.querySelectorAll('table');
        tables.forEach(table => {
            const wrapper = document.createElement('div');
            wrapper.className = 'overflow-x-auto';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        });
    }
});
</script>
@endif
