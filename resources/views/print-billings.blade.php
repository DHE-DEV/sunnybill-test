<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrechnungen drucken</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        .pdf-page {
            width: 100%;
            height: 100vh;
            page-break-after: always;
            position: relative;
            background: white;
        }

        .pdf-page:last-child {
            page-break-after: auto;
        }

        .pdf-iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        @media print {
            body {
                background: white;
            }

            .pdf-page {
                page-break-after: always;
                height: 100vh;
            }

            .pdf-page:last-child {
                page-break-after: auto;
            }

            @page {
                size: A4;
                margin: 0;
            }
        }

        @media screen {
            .pdf-page {
                margin: 0;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
        }
    </style>
</head>
<body>
    @if(count($pdfContents) > 0)
        @foreach($pdfContents as $index => $pdfContent)
            <div class="pdf-page">
                <iframe
                    class="pdf-iframe"
                    src="data:application/pdf;base64,{{ $pdfContent['content'] }}"
                    type="application/pdf"
                ></iframe>
            </div>
        @endforeach

        <script>
            // Automatisch drucken wenn die Seite geladen wird
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 1000);
            });
        </script>
    @else
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh;">
            <p style="color: #6b7280; font-size: 1rem;">Keine Abrechnungen zum Drucken verfügbar</p>
        </div>
    @endif
</body>
</html>
