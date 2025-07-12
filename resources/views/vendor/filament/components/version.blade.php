@php
    $commitHash = trim(exec('git log --pretty="%h" -n1 HEAD'));
@endphp
<div class="p-4 text-left text-xs text-gray-200">
    Version: {{ $commitHash }}
</div>
