/**
 * Interactive SVG Controls for Siteplans (Public & Admin)
 * Features:
 * - Direct Zoom In (+), Zoom Out (-), and Reset buttons support
 * - Mouse Wheel Zoom (centered at mouse)
 * - Mouse Drag / Pan
 * - Android / Touch 1-finger Pan & 2-finger Pinch-to-Zoom
 * - Native SVG viewBox preservation on initial load (no distortion/offset)
 */

(function () {
    function initializeSVGControls(svgElement, svgContainer) {
        if (!svgElement || svgElement.dataset.zoomInitialized) return;
        svgElement.dataset.zoomInitialized = 'true';

        let scale = 1;
        let translateX = 0, translateY = 0;
        let isDragging = false;
        let startX = 0, startY = 0;
        let hasMoved = false;

        // Touch gesture variables
        let isTouchPinching = false;
        let initialPinchDistance = 0;
        let initialPinchScale = 1;
        let pinchCenter = { x: 0, y: 0 };
        let touchStartX = 0, touchStartY = 0;
        let touchStartRawX = 0, touchStartRawY = 0;

        const MIN_SCALE = 0.6;
        const MAX_SCALE = 5.0;
        const ZOOM_STEP = 0.25;

        // Find controls in container or in the surrounding tab-pane / parent
        const parentScope = svgContainer.closest('.tab-pane') || svgContainer.parentElement || document;
        const btnZoomIn = parentScope.querySelector('.btn-zoom-in') || svgContainer.querySelector('.btn-zoom-in');
        const btnZoomOut = parentScope.querySelector('.btn-zoom-out') || svgContainer.querySelector('.btn-zoom-out');
        const btnReset = parentScope.querySelector('.reset-button, .btn-zoom-reset') || svgContainer.querySelector('.reset-button, .btn-zoom-reset');
        const zoomBadge = parentScope.querySelector('.zoom-level-badge') || svgContainer.querySelector('.zoom-level-badge');

        function updateBadge() {
            if (zoomBadge) {
                zoomBadge.textContent = Math.round(scale * 100) + '%';
            }
        }

        function applyTransform() {
            if (scale === 1 && translateX === 0 && translateY === 0) {
                svgElement.style.transform = '';
            } else {
                svgElement.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
            }
            updateBadge();
        }

        function zoomAroundPoint(newScale, focalX, focalY) {
            newScale = Math.min(Math.max(MIN_SCALE, newScale), MAX_SCALE);
            if (newScale === scale) return;

            const scaleRatio = newScale / scale;
            translateX = focalX - (focalX - translateX) * scaleRatio;
            translateY = focalY - (focalY - translateY) * scaleRatio;
            scale = newScale;
            applyTransform();
        }

        // --- BUTTON EVENTS ---
        if (btnZoomIn) {
            btnZoomIn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const rect = svgContainer.getBoundingClientRect();
                zoomAroundPoint(scale + ZOOM_STEP, rect.width / 2, rect.height / 2);
            });
        }

        if (btnZoomOut) {
            btnZoomOut.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const rect = svgContainer.getBoundingClientRect();
                zoomAroundPoint(scale - ZOOM_STEP, rect.width / 2, rect.height / 2);
            });
        }

        if (btnReset) {
            btnReset.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                scale = 1;
                translateX = 0;
                translateY = 0;
                applyTransform();
            });
        }

        // --- MOUSE WHEEL ZOOM ---
        svgContainer.addEventListener('wheel', function (e) {
            e.preventDefault();
            const rect = svgContainer.getBoundingClientRect();
            const focalX = e.clientX - rect.left;
            const focalY = e.clientY - rect.top;
            const delta = e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP;
            zoomAroundPoint(scale + delta, focalX, focalY);
        }, { passive: false });

        // --- MOUSE DRAG / PAN ---
        svgContainer.addEventListener('mousedown', function (e) {
            if (e.button !== 0 || e.target.closest('button, .legend, .show-btn, .toggle-btn')) return;
            isDragging = true;
            hasMoved = false;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            svgElement.style.cursor = 'grabbing';
        });

        window.addEventListener('mousemove', function (e) {
            if (!isDragging) return;
            const currentX = e.clientX - startX;
            const currentY = e.clientY - startY;

            if (Math.abs(currentX - translateX) > 5 || Math.abs(currentY - translateY) > 5) {
                hasMoved = true;
            }

            translateX = currentX;
            translateY = currentY;
            applyTransform();
        });

        window.addEventListener('mouseup', function () {
            if (isDragging) {
                isDragging = false;
                svgElement.style.cursor = 'grab';
            }
        });

        // --- TOUCH GESTURES (Android & iOS) ---
        svgContainer.addEventListener('touchstart', function (e) {
            if (e.target.closest('button, .legend, .show-btn, .toggle-btn')) return;

            hasMoved = false;
            if (e.touches.length === 1) {
                isDragging = true;
                isTouchPinching = false;
                touchStartRawX = e.touches[0].clientX;
                touchStartRawY = e.touches[0].clientY;
                touchStartX = touchStartRawX - translateX;
                touchStartY = touchStartRawY - translateY;
            } else if (e.touches.length === 2) {
                isDragging = false;
                isTouchPinching = true;
                hasMoved = true;

                const t1 = e.touches[0];
                const t2 = e.touches[1];
                initialPinchDistance = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
                initialPinchScale = scale;

                const rect = svgContainer.getBoundingClientRect();
                pinchCenter = {
                    x: ((t1.clientX + t2.clientX) / 2) - rect.left,
                    y: ((t1.clientY + t2.clientY) / 2) - rect.top
                };
            }
        }, { passive: true });

        svgContainer.addEventListener('touchmove', function (e) {
            if (e.target.closest('button, .legend, .show-btn, .toggle-btn')) return;

            if (isTouchPinching && e.touches.length === 2) {
                e.preventDefault();
                const t1 = e.touches[0];
                const t2 = e.touches[1];
                const currentDistance = Math.hypot(t2.clientX - t1.clientX, t2.clientY - t1.clientY);
                if (initialPinchDistance > 0) {
                    const factor = currentDistance / initialPinchDistance;
                    zoomAroundPoint(initialPinchScale * factor, pinchCenter.x, pinchCenter.y);
                }
            } else if (isDragging && e.touches.length === 1) {
                const curX = e.touches[0].clientX;
                const curY = e.touches[0].clientY;

                if (Math.abs(curX - touchStartRawX) > 6 || Math.abs(curY - touchStartRawY) > 6) {
                    hasMoved = true;
                    e.preventDefault();
                }

                translateX = curX - touchStartX;
                translateY = curY - touchStartY;
                applyTransform();
            }
        }, { passive: false });

        svgContainer.addEventListener('touchend', function (e) {
            if (e.touches.length === 0) {
                isDragging = false;
                isTouchPinching = false;
            } else if (e.touches.length === 1) {
                isTouchPinching = false;
                isDragging = true;
                touchStartRawX = e.touches[0].clientX;
                touchStartRawY = e.touches[0].clientY;
                touchStartX = touchStartRawX - translateX;
                touchStartY = touchStartRawY - translateY;
            }
        });

        // Prevent click/modal if the user was dragging
        svgContainer.querySelectorAll('a.detail-button, polygon, path').forEach(elem => {
            elem.addEventListener('click', function (e) {
                if (hasMoved) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
        });
    }

    function initAllSiteplans() {
        document.querySelectorAll('.svg-container, .svg-view-container').forEach(container => {
            const svgElement = container.querySelector('svg');
            if (svgElement) {
                initializeSVGControls(svgElement, container);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllSiteplans);
    } else {
        initAllSiteplans();
    }

    if (window.jQuery) {
        window.jQuery('a[data-toggle="tab"], a[data-toggle="pill"]').on('shown.bs.tab', function () {
            setTimeout(initAllSiteplans, 60);
        });
    }
})();
