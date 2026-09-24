<script>
    (function () {
        document.querySelectorAll('[data-update-uri], [data-module-url]').forEach(function (el) {
            ['data-update-uri', 'data-module-url'].forEach(function (attr) {
                if (! el.hasAttribute(attr)) {
                    return;
                }

                el.setAttribute(attr, window.location.origin + livewireSameOriginPath(el.getAttribute(attr)));
            });
        });

        function livewireSameOriginPath(uri) {
            if (! uri) {
                return uri;
            }

            if (uri.charAt(0) === '/' && uri.charAt(1) !== '/') {
                return uri;
            }

            if (uri.indexOf('//') === 0) {
                return '/' + uri.replace(/^\/+/, '');
            }

            try {
                var parsed = new URL(uri, window.location.origin);

                return parsed.pathname + parsed.search;
            } catch (e) {
                return uri;
            }
        }
    })();
</script>
