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
        transform-origin: top center;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    .guru-mobile-view .mobile-fit-spacer {
        width: 100%;
        max-width: 100vw;
        margin: 0 auto;
        box-sizing: border-box;
        overflow: hidden;
    }

    @media print {
        html.guru-mobile-html,
        body.guru-mobile-view {
            background: #fff !important;
            overflow: visible !important;
            width: auto !important;
            max-width: none !important;
        }
        .guru-mobile-view .mobile-doc-scroll,
        .guru-mobile-view .mobile-fit-spacer {
            padding: 0 !important;
            margin: 0 !important;
            width: auto !important;
            max-width: none !important;
            height: auto !important;
            overflow: visible !important;
        }
        .guru-mobile-view .mobile-fit-target {
            zoom: 1 !important;
            transform: none !important;
            margin: 0 !important;
        }
        .guru-mobile-view .paper-preview,
        .guru-mobile-view .main-paper {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }
        .guru-mobile-view .main-content {
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>
<script>
    (function () {
        function fitMobileDocument() {
            if (!document.body.classList.contains('guru-mobile-view')) return;
            if (document.body.classList.contains('guru-printing')) return;

            var viewW = window.innerWidth || document.documentElement.clientWidth;
            var available = Math.max(viewW - 12, 280);

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

            var meta = document.querySelector('meta[name="viewport"]');
            if (meta) {
                meta.setAttribute(
                    'content',
                    'width=device-width, initial-scale=1.0, minimum-scale=0.2, maximum-scale=5.0, user-scalable=yes'
                );
            }
        }

        function scheduleFit() {
            fitMobileDocument();
            setTimeout(fitMobileDocument, 100);
            setTimeout(fitMobileDocument, 350);
            setTimeout(fitMobileDocument, 800);
        }

        function prepareGuruMobilePrint(done) {
            document.body.classList.add('guru-printing');

            document.querySelectorAll('.mobile-fit-target').forEach(function (el) {
                el.style.zoom = '1';
                el.style.transform = 'none';
            });

            document.querySelectorAll('.mobile-fit-spacer').forEach(function (el) {
                el.style.height = 'auto';
                el.style.width = '';
                el.style.overflow = 'visible';
            });

            var meta = document.querySelector('meta[name="viewport"]');
            if (meta) {
                meta.setAttribute('content', 'width=device-width, initial-scale=1.0');
            }

            setTimeout(function () {
                if (typeof done === 'function') done();
            }, 200);
        }

        function finishGuruMobilePrint() {
            document.body.classList.remove('guru-printing');
            scheduleFit();
        }

        window.fitMobileDocument = fitMobileDocument;
        window.scheduleFitMobileDocument = scheduleFit;
        window.prepareGuruMobilePrint = prepareGuruMobilePrint;
        window.finishGuruMobilePrint = finishGuruMobilePrint;

        document.addEventListener('DOMContentLoaded', scheduleFit);
        window.addEventListener('load', scheduleFit);
        window.addEventListener('orientationchange', function () {
            setTimeout(scheduleFit, 150);
            setTimeout(scheduleFit, 500);
        });
        window.addEventListener('resize', fitMobileDocument);

        window.addEventListener('beforeprint', function () {
            prepareGuruMobilePrint(function () {});
        });
        window.addEventListener('afterprint', finishGuruMobilePrint);
    })();
</script>
