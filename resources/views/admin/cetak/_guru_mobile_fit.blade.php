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
        background: #e8ecf0 !important;
    }
    .guru-mobile-view .mobile-doc-scroll {
        width: 100%;
        max-width: 100vw;
        overflow-x: hidden !important;
        overflow-y: visible;
        padding: 8px 0 88px;
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

    @media screen {
        .guru-mobile-view .paper-preview {
            width: 210mm;
            min-height: auto;
            padding: 0.5cm 0.7cm;
            box-sizing: border-box;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            margin: 0 auto;
        }
    }

    body.guru-printing .mobile-doc-scroll,
    body.guru-printing .mobile-fit-spacer {
        padding: 0 !important;
        margin: 0 !important;
        height: auto !important;
        overflow: visible !important;
    }
    body.guru-printing .mobile-fit-target {
        transform: none !important;
        zoom: 1 !important;
    }

    @media print {
        html.guru-mobile-html,
        body.guru-mobile-view {
            background: #fff !important;
            overflow: visible !important;
        }
        .guru-mobile-view .mobile-doc-scroll,
        .guru-mobile-view .mobile-fit-spacer {
            padding: 0 !important;
            margin: 0 !important;
            height: auto !important;
            overflow: visible !important;
        }
        .guru-mobile-view .mobile-fit-target {
            transform: none !important;
            zoom: 1 !important;
        }
        .guru-mobile-view .paper-preview {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            box-shadow: none !important;
            page-break-inside: avoid;
            page-break-after: avoid;
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
                el.style.width = '';
                el.style.maxWidth = '';

                var naturalW = el.offsetWidth;
                if (!naturalW) return;

                var scale = Math.min(1, available / naturalW);
                var spacer = el.parentElement;
                var hasSpacer = spacer && spacer.classList.contains('mobile-fit-spacer');

                if (scale < 0.999) {
                    el.style.transform = 'scale(' + scale + ')';
                    el.style.transformOrigin = 'top center';
                }

                if (hasSpacer) {
                    spacer.style.width = '100%';
                    spacer.style.maxWidth = '100vw';
                    spacer.style.margin = '0 auto';
                    spacer.style.overflow = 'hidden';
                    spacer.style.height = Math.ceil(el.getBoundingClientRect().height) + 'px';
                }
            });
        }

        function scheduleFit() {
            fitMobileDocument();
            setTimeout(fitMobileDocument, 150);
            setTimeout(fitMobileDocument, 500);
            setTimeout(fitMobileDocument, 1000);
        }

        function prepareGuruMobilePrint(done) {
            document.body.classList.add('guru-printing');
            document.querySelectorAll('.mobile-fit-target').forEach(function (el) {
                el.style.transform = 'none';
                el.style.zoom = '1';
            });
            document.querySelectorAll('.mobile-fit-spacer').forEach(function (el) {
                el.style.height = 'auto';
                el.style.overflow = 'visible';
            });
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
            setTimeout(scheduleFit, 200);
            setTimeout(scheduleFit, 600);
        });
        window.addEventListener('resize', fitMobileDocument);
    })();
</script>
