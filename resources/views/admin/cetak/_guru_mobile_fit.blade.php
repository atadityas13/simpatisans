<style>
    html.guru-mobile-html,
    body.guru-mobile-view {
        width: 100% !important;
        max-width: 100vw !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        display: block !important;
        box-sizing: border-box;
    }
    .guru-mobile-view .mobile-doc-scroll {
        width: 100%;
        max-width: 100vw;
        overflow-x: hidden !important;
        overflow-y: visible;
        padding: 12px 0 88px;
        margin: 0 auto;
        box-sizing: border-box;
    }
    .guru-mobile-view .mobile-fit-target {
        margin-left: auto !important;
        margin-right: auto !important;
        overflow: visible !important;
    }
    .guru-mobile-view .mobile-fit-spacer {
        width: 100%;
        max-width: 100vw;
        margin: 0 auto 12px;
        box-sizing: border-box;
        overflow: visible;
    }
</style>
<script>
    (function () {
        function measureTargetWidth(el) {
            var width = Math.max(el.scrollWidth || 0, el.offsetWidth || 0);
            if (width > 0) return width;

            var clone = el.cloneNode(true);
            clone.style.visibility = 'hidden';
            clone.style.position = 'absolute';
            clone.style.left = '-99999px';
            clone.style.width = 'max-content';
            clone.style.transform = 'none';
            clone.style.zoom = '1';
            document.body.appendChild(clone);
            width = Math.max(clone.scrollWidth || 0, clone.offsetWidth || 0);
            document.body.removeChild(clone);
            return width;
        }

        function fitMobileDocument() {
            if (!document.body.classList.contains('guru-mobile-view')) return;

            var viewW = window.innerWidth || document.documentElement.clientWidth;
            var available = Math.max(viewW - 12, 280);
            var maxNaturalW = 0;

            document.querySelectorAll('.mobile-fit-target').forEach(function (el) {
                el.style.zoom = '';
                el.style.transform = '';
                var w = measureTargetWidth(el);
                if (w > maxNaturalW) maxNaturalW = w;
            });

            if (!maxNaturalW) return;

            var scale = Math.min(1, available / maxNaturalW);
            var minScale = Math.max(0.2, scale * 0.35);
            var meta = document.querySelector('meta[name="viewport"]');
            if (meta) {
                meta.setAttribute(
                    'content',
                    'width=' + Math.round(maxNaturalW) +
                    ', initial-scale=' + scale.toFixed(4) +
                    ', minimum-scale=' + minScale.toFixed(4) +
                    ', maximum-scale=5.0, user-scalable=yes'
                );
            }
        }

        function scheduleFit() {
            fitMobileDocument();
            setTimeout(fitMobileDocument, 100);
            setTimeout(fitMobileDocument, 350);
            setTimeout(fitMobileDocument, 800);
        }

        window.fitMobileDocument = fitMobileDocument;
        window.scheduleFitMobileDocument = scheduleFit;

        document.addEventListener('DOMContentLoaded', scheduleFit);
        window.addEventListener('load', scheduleFit);
        window.addEventListener('orientationchange', function () {
            setTimeout(scheduleFit, 150);
            setTimeout(scheduleFit, 500);
        });
        window.addEventListener('resize', fitMobileDocument);
    })();
</script>
