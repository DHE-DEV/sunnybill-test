<div class="fi-fo-field-wrp">
    <div class="grid gap-y-2">
        <div class="flex items-center gap-x-3 justify-between">
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Inhalt
                </span>
            </label>
        </div>
        
        <div class="fi-fo-textarea">
            <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 dark:ring-white/20">
                <div class="fi-input block w-full border-none py-3 px-3 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-transparent prose prose-sm max-w-none dark:prose-invert note-content">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alle Links in Notizen sollen in neuem Tab öffnen
    const noteContent = document.querySelectorAll('.note-content');
    noteContent.forEach(function(container) {
        const links = container.querySelectorAll('a');
        links.forEach(function(link) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    });
});
</script>