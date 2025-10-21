<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Nepal Van Java'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .room-code {
            font-family: monospace;
        }
        .header-frame {
            width: 100%;
            border: none;
            height: 85px; /* Sesuaikan tinggi header */
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Header WordPress via iframe -->
    <iframe 
        src="https://nepal-vanjava.com/elementor-hf/header/"
        class="header-frame"
        scrolling="no"
        onload="resizeIframe(this)">
    </iframe>

    <script>
    function resizeIframe(iframe) {
        try {
            iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
        } catch (e) {
            console.warn('Tidak bisa auto-resize iframe karena beda domain.');
        }
    }
    </script>
