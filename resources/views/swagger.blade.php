<!DOCTYPE html>
<html>
<head>
    <title>Swagger UI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.1.3/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.1.3/swagger-ui-bundle.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.1.3/swagger-ui-standalone-preset.js" defer></script>

    <script defer>
      document.addEventListener("DOMContentLoaded", function() {
         route
        const configUrl = @json($configUrl);
        const url = @json($url);

        // default local spec
        const defaultSpec = "{{ url('swagger/openapi.yaml') }}";

        
        const specUrl = configUrl || url || defaultSpec;

        SwaggerUIBundle({
          url: specUrl,
          dom_id: "#swagger-ui",
          presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
          layout: "StandaloneLayout"
        });
      });
    </script>
</body>
</html>
