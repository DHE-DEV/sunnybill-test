<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF-Analyse Fehler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-md p-8 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="text-red-500 text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">PDF-Analyse Fehler</h1>
            <p class="text-gray-600 mb-6">{{ $error }}</p>
            <button onclick="window.close()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg">
                Fenster schließen
            </button>
        </div>
    </div>
</body>
</html>