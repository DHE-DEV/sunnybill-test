<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort erfolgreich geändert</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Passwort erfolgreich geändert!
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Hallo {{ $user->name }}, Ihr Passwort wurde erfolgreich geändert.
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow rounded-lg">
            <div class="space-y-6">
                <div class="text-center">
                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Erfolgreich angemeldet
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>{{ $message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center space-y-4">
                    <p class="text-sm text-gray-600">
                        Sie sind jetzt erfolgreich angemeldet und können das System nutzen.
                    </p>
                    
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Ihre Kontoinformationen:</h4>
                        <dl class="text-sm text-gray-600 space-y-1">
                            <div class="flex justify-between">
                                <dt>Name:</dt>
                                <dd class="font-medium">{{ $user->name }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>E-Mail:</dt>
                                <dd class="font-medium">{{ $user->email }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Rolle:</dt>
                                <dd class="font-medium">{{ $user->role_label }}</dd>
                            </div>
                            @if($user->department)
                            <div class="flex justify-between">
                                <dt>Abteilung:</dt>
                                <dd class="font-medium">{{ $user->department }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="text-center space-y-3">
                    <p class="text-xs text-gray-500">
                        Falls Sie Fragen haben oder Unterstützung benötigen, wenden Sie sich bitte an Ihren Administrator.
                    </p>
                    
                    <div class="flex justify-center space-x-4">
                        <button onclick="window.close()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Fenster schließen
                        </button>
                        
                        <form method="POST" action="/admin/logout" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013 3v1"></path>
                                </svg>
                                Abmelden
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-400">
                © {{ date('Y') }} {{ config('app.name', 'SunnyBill') }}. Alle Rechte vorbehalten.
            </p>
        </div>
    </div>

    <script>
        // Auto-close nach 30 Sekunden falls gewünscht
        // setTimeout(() => {
        //     window.close();
        // }, 30000);
    </script>
</body>
</html>
