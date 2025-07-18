<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort ändern - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Passwort ändern</h1>
            <p class="text-gray-600 mt-2">Hallo {{ $user->name }}!</p>
            @if($hasTemporaryPassword)
                <p class="text-sm text-orange-600 mt-1">Sie müssen Ihr temporäres Passwort ändern</p>
            @else
                <p class="text-sm text-gray-500 mt-1">Ändern Sie Ihr aktuelles Passwort</p>
            @endif
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Fehler beim Ändern des Passworts</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($hasTemporaryPassword)
            <div class="bg-orange-50 border border-orange-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-orange-800">Temporäres Passwort</h3>
                        <div class="mt-2 text-sm text-orange-700">
                            <p>Sie verwenden ein temporäres Passwort. Bitte ändern Sie es aus Sicherheitsgründen.</p>
                            @if($temporaryPassword)
                                <p class="mt-1">Ihr temporäres Passwort: <code class="bg-orange-100 px-2 py-1 rounded text-orange-900 font-mono">{{ $temporaryPassword }}</code></p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            @if($hasTemporaryPassword)
                <div class="mb-4">
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Temporäres Passwort
                    </label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Geben Sie Ihr temporäres Passwort ein">
                </div>
            @endif

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Neues Passwort
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Geben Sie Ihr neues Passwort ein">
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Neues Passwort bestätigen
                </label>
                <input type="password" 
                       id="password_confirmation" 
                       name="password_confirmation" 
                       required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Bestätigen Sie Ihr neues Passwort">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200">
                Passwort ändern
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="/admin" class="text-sm text-blue-600 hover:text-blue-500">
                Zurück zum Dashboard
            </a>
        </div>
    </div>
</body>
</html>
