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
        touch-action: pan-y pinch-zoom;
        display: block !important;
        box-sizing: border-box;
    }
    .guru-mobile-view .mobile-doc-scroll {
        width: 100%;
        max-width: 100vw;
        overflow-x: hidden !important;
        overflow-y: visible;
        padding: 4px 0 80px;
        margin: 0 auto;
        box-sizing: border-box;
    }
    .guru-mobile-view .mobile-fit-target {
        transform-origin: top center;
        margin-left: auto !important;
        margin-right: auto !important;
        max-width: none;
    }
    .guru-mobile-view .mobile-fit-spacer {
        width: 100%;
        max-width: 100vw;
        overflow: hidden;
        margin: 0 auto 12px;
        box-sizing: border-box;
    }
</style>
<script>
    (function () {
        function fitMobileDocument() {
            if (!document.body.classList.contains('guru-mobile-view')) return;

            var viewW = window.innerWidth || document.documentElement.clientWidth;
            var available = Math.max(viewW - 8, 280);

            document.querySelectorAll('.mobile-fit-target').forEach(function (el) {
                el.style.zoom = '';
                el.style.transform = '';
                el.style.marginBottom = '';

                var naturalW = el.offsetWidth;
                if (!naturalW) return;

                var scale = Math.min(1, available / naturalW);
                var spacer = el.parentElement;
                var hasSpacer = spacer && spacer.classList.contains('mobile-fit-spacer');

                if (scale < 0.999) {
                    if ('zoom' in el.style) {
                        el.style.zoom = String(scale);
                    } else {
                        el.style.transform = 'scale(' + scale + ')';
                        el.style.transformOrigin = 'top center';
                        if (hasSpacer) {
                            spacer.style.width = Math.ceil(naturalW * scale) + 'px';
                        }
                    }
                } else if (hasSpacer) {
                    spacer.style.width = '';
                }

                if (hasSpacer) {
                    spacer.style.maxWidth = '100%';
                    spacer.style.margin = '0 auto';
                    spacer.style.overflow = 'hidden';
                    spacer.style.height = Math.ceil(el.getBoundingClientRect().height) + 'px';
                }
            });
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
