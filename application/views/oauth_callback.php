<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OAuth Callback</title>
</head>
<body>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Extract the state parameter from the URL
            var params = new URLSearchParams(window.location.search);
            var codeId = params.get('code');
		console.log('codeId is ', codeId);
            if (codeId) {
                // Set a flag in localStorage to indicate success
                localStorage.setItem('oauthSuccess', codeId);

                // Close the popup window
                window.close();
            } else {
                console.error('No state parameter found, cannot notify parent window');
            }
        });
    </script>
</body>
</html>

