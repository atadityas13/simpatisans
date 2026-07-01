<style>
    .adjustable-wrapper {
        position: absolute !important;
        cursor: move;
        border: 1px dashed transparent;
        transition: border-color 0.2s;
        display: inline-block;
    }

    .adjustable-wrapper:hover {
        border-color: #4f46e5;
        background: rgba(79, 70, 229, 0.05);
    }

    .resize-handle {
        position: absolute;
        width: 12px;
        height: 12px;
        background: #4f46e5;
        bottom: -6px;
        right: -6px;
        cursor: nwse-resize;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        z-index: 100;
        opacity: 0;
        transition: opacity 0.2s;
    }

    .adjustable-wrapper:hover .resize-handle {
        opacity: 1;
    }

    @media print {
        .adjustable-wrapper {
            border: none !important;
            background: none !important;
        }

        .resize-handle {
            display: none !important;
        }

        .no-print {
            display: none !important;
        }

        .force-hidden {
            display: none !important;
        }
    }

    /* Controls Panel Styling - Compact & Slim version */
    @media screen {
        .controls-panel {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(8px);
            padding: 12px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 170px;
            /* Slimmer width */
            color: white;
            font-family: 'Segoe UI', system-ui, sans-serif;
            transition: opacity 0.3s;
        }

        .controls-panel:hover {
            background: rgba(15, 23, 42, 0.95);
        }

        .controls-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .controls-group:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .control-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            font-size: 11px;
            /* Smaller font */
            font-weight: 500;
            padding: 2px 0;
            color: rgba(255, 255, 255, 0.8);
            transition: color 0.2s;
        }

        .control-item:hover {
            color: white;
        }

        .control-item input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: #6366f1;
            cursor: pointer;
        }

        .no-print-btn {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white !important;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 12px;
            /* Smaller font */
            text-align: center;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .no-print-btn::before {
            content: '🖨️';
            font-size: 12px;
        }

        .no-print-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
        }

        .force-hidden-screen {
            opacity: 0.15;
            filter: grayscale(1) blur(1px);
            pointer-events: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const templateKey = '{{ $templateKey ?? "default_cetak" }}';
        const adjustables = document.querySelectorAll('.adjustable-wrapper');

        // Load saved positions
        const savedData = JSON.parse(localStorage.getItem(templateKey) || '{}');

        // VISIBILITY LOGIC
        const visibilityKey = 'print_visibility_settings';
        const visibilitySettings = JSON.parse(localStorage.getItem(visibilityKey) || '{"showSign": true, "showStamp": true}');

        function injectVisibilityControls() {
            let panel = document.querySelector('.controls-panel');
            if (!panel) {
                panel = document.createElement('div');
                panel.className = 'no-print controls-panel';
                document.body.appendChild(panel);
            }

            // Ensure we have a checkbox group
            let group = panel.querySelector('#visibility-controls');
            if (!group) {
                group = document.createElement('div');
                group.id = 'visibility-controls';
                group.className = 'controls-group';

                // Add title
                const title = document.createElement('div');
                title.style.fontSize = '10px';
                title.style.fontWeight = '800';
                title.style.textTransform = 'uppercase';
                title.style.letterSpacing = '0.05em';
                title.style.color = 'rgba(255,255,255,0.4)';
                title.style.marginBottom = '4px';
                title.textContent = 'Opsi Tampilan';
                group.appendChild(title);

                group.innerHTML += `
                    <label class="control-item">
                        <input type="checkbox" id="toggle-sign" ${visibilitySettings.showSign ? 'checked' : ''}>
                        <span>Tanda Tangan</span>
                    </label>
                    <label class="control-item">
                        <input type="checkbox" id="toggle-stamp" ${visibilitySettings.showStamp ? 'checked' : ''}>
                        <span>Stempel</span>
                    </label>
                `;

                // Prepend to panel (or after print button if exists)
                const printBtn = panel.querySelector('.no-print-btn');
                if (printBtn && printBtn.nextSibling) {
                    panel.insertBefore(group, printBtn.nextSibling);
                } else if (printBtn) {
                    panel.appendChild(group);
                } else {
                    panel.insertBefore(group, panel.firstChild);
                }
            }

            document.getElementById('toggle-sign').addEventListener('change', function (e) {
                visibilitySettings.showSign = e.target.checked;
                updateVisibility();
            });

            document.getElementById('toggle-stamp').addEventListener('change', function (e) {
                visibilitySettings.showStamp = e.target.checked;
                updateVisibility();
            });
        }

        function updateVisibility() {
            localStorage.setItem(visibilityKey, JSON.stringify(visibilitySettings));

            adjustables.forEach(el => {
                const id = (el.getAttribute('data-adjustable-id') || '').toLowerCase();
                const isSign = id.includes('ttd') || id.includes('sign');
                const isStamp = id.includes('stempel') || id.includes('cap');

                if (isSign) {
                    toggleElement(el, visibilitySettings.showSign);
                } else if (isStamp) {
                    toggleElement(el, visibilitySettings.showStamp);
                }
            });
        }

        function toggleElement(el, isVisible) {
            if (isVisible) {
                el.classList.remove('force-hidden');
                el.classList.remove('force-hidden-screen');
            } else {
                el.classList.add('force-hidden');
                el.classList.add('force-hidden-screen');
            }
        }

        injectVisibilityControls();
        updateVisibility();

        // DRAGGING AND RESIZING LOGIC
        adjustables.forEach(el => {
            const id = el.getAttribute('data-adjustable-id');
            if (savedData[id]) {
                const data = savedData[id];
                el.style.left = data.left;
                el.style.top = data.top;
                if (data.width) {
                    const img = el.querySelector('img');
                    if (img) img.style.width = data.width;
                }
            }

            // Dragging Logic
            el.addEventListener('mousedown', function (e) {
                if (e.target.classList.contains('resize-handle')) return;
                if (el.classList.contains('force-hidden-screen')) return; // Disable drag if hidden

                e.preventDefault();
                let startX = e.clientX;
                let startY = e.clientY;
                let startLeft = el.offsetLeft;
                let startTop = el.offsetTop;

                function onMouseMove(e) {
                    let newLeft = startLeft + (e.clientX - startX);
                    let newTop = startTop + (e.clientY - startY);

                    el.style.left = newLeft + 'px';
                    el.style.top = newTop + 'px';
                }

                function onMouseUp() {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    saveState(id, {
                        left: el.style.left,
                        top: el.style.top
                    });
                }

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });

            // Resizing Logic
            const handle = el.querySelector('.resize-handle');
            if (handle) {
                handle.addEventListener('mousedown', function (e) {
                    if (el.classList.contains('force-hidden-screen')) return; // Disable resize if hidden
                    e.preventDefault();
                    e.stopPropagation();

                    let startX = e.clientX;
                    let startWidth = el.offsetWidth;
                    const img = el.querySelector('img');

                    function onMouseMove(e) {
                        let newWidth = startWidth + (e.clientX - startX);
                        if (newWidth > 20) {
                            if (img) {
                                img.style.width = newWidth + 'px';
                                img.style.height = 'auto';
                            }
                        }
                    }

                    function onMouseUp() {
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        saveState(id, {
                            width: img ? img.style.width : null
                        });
                    }

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            }
        });

        function saveState(id, newData) {
            const currentData = JSON.parse(localStorage.getItem(templateKey) || '{}');
            currentData[id] = { ...(currentData[id] || {}), ...newData };
            localStorage.setItem(templateKey, JSON.stringify(currentData));
        }

    });
</script>