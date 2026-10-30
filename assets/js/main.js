const counters = document.querySelectorAll(".counter");

counters.forEach(counter=>{

    const update=()=>{

        const target=+counter.getAttribute("data-target");

        const value=+counter.innerText;

        const increment=target/100;

        if(value<target){

            counter.innerText=Math.ceil(value+increment);

            setTimeout(update,20);

        }else{

            counter.innerText=target+"+";

        }

    }

    update();

});

const faqItems = document.querySelectorAll(".faq-item");

faqItems.forEach(item=>{

    const btn=item.querySelector(".faq-question");

    btn.addEventListener("click",()=>{

        faqItems.forEach(i=>{

            if(i!==item){

                i.classList.remove("active");

            }

        });

        item.classList.toggle("active");

    });

});

/* =========================================================
   DETAIL PRODUK — FULLSCREEN IMAGE LIGHTBOX + ZOOM
========================================================= */
(function () {
    const lightbox = document.getElementById("productLightbox");
    if (!lightbox) return;

    const stage = lightbox.querySelector("[data-lightbox-stage]");
    const image = document.getElementById("productLightboxImage");
    const openButton = document.querySelector("[data-product-lightbox-open]");
    const closeButtons = lightbox.querySelectorAll("[data-product-lightbox-close]");
    const zoomInButton = lightbox.querySelector("[data-lightbox-zoom-in]");
    const zoomOutButton = lightbox.querySelector("[data-lightbox-zoom-out]");
    const resetButton = lightbox.querySelector("[data-lightbox-reset]");
    const fullscreenButton = lightbox.querySelector("[data-lightbox-fullscreen]");
    const zoomLabel = lightbox.querySelector("[data-lightbox-zoom-label]");

    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let dragging = false;
    let dragPointerId = null;
    let startX = 0;
    let startY = 0;
    let startTranslateX = 0;
    let startTranslateY = 0;
    let lastTap = 0;
    const pointers = new Map();
    let pinchStartDistance = 0;
    let pinchStartScale = 1;

    const MIN_SCALE = 1;
    const MAX_SCALE = 4;
    const STEP = 0.25;

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function setTransform() {
        scale = clamp(scale, MIN_SCALE, MAX_SCALE);
        if (scale === 1) {
            translateX = 0;
            translateY = 0;
        }
        image.style.transform = `translate3d(${translateX}px, ${translateY}px, 0) scale(${scale})`;
        if (zoomLabel) zoomLabel.textContent = `${Math.round(scale * 100)}%`;
    }

    function resetZoom() {
        scale = 1;
        translateX = 0;
        translateY = 0;
        setTransform();
    }

    function changeZoom(amount) {
        const oldScale = scale;
        scale = clamp(scale + amount, MIN_SCALE, MAX_SCALE);
        if (scale !== oldScale && scale === 1) {
            translateX = 0;
            translateY = 0;
        }
        setTransform();
    }

    function openLightbox() {
        lightbox.classList.add("is-open");
        lightbox.setAttribute("aria-hidden", "false");
        document.body.classList.add("lightbox-open");
        resetZoom();
        window.setTimeout(() => closeButtons[0]?.focus(), 50);
    }

    function closeLightbox() {
        lightbox.classList.remove("is-open");
        lightbox.setAttribute("aria-hidden", "true");
        document.body.classList.remove("lightbox-open");
        resetZoom();
        pointers.clear();
        dragging = false;
        dragPointerId = null;
        stage.classList.remove("is-dragging");
        if (document.fullscreenElement) {
            document.exitFullscreen?.().catch(() => {});
        }
    }

    function distanceBetween(a, b) {
        return Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
    }

    openButton?.addEventListener("click", openLightbox);

    closeButtons.forEach((button) => {
        button.addEventListener("click", closeLightbox);
    });

    zoomInButton?.addEventListener("click", () => changeZoom(STEP));
    zoomOutButton?.addEventListener("click", () => changeZoom(-STEP));
    resetButton?.addEventListener("click", resetZoom);

    fullscreenButton?.addEventListener("click", async () => {
        try {
            if (!document.fullscreenElement) {
                await lightbox.requestFullscreen?.();
            } else {
                await document.exitFullscreen?.();
            }
        } catch (error) {
            // Lightbox tetap berfungsi jika Fullscreen API tidak tersedia.
        }
    });

    stage.addEventListener("wheel", (event) => {
        event.preventDefault();
        changeZoom(event.deltaY < 0 ? STEP : -STEP);
    }, { passive: false });

    stage.addEventListener("pointerdown", (event) => {
        pointers.set(event.pointerId, event);
        stage.setPointerCapture?.(event.pointerId);

        if (pointers.size === 2) {
            const [a, b] = [...pointers.values()];
            pinchStartDistance = distanceBetween(a, b);
            pinchStartScale = scale;
            dragging = false;
            stage.classList.remove("is-dragging");
            return;
        }

        if (event.pointerType === "mouse" && event.button !== 0) return;
        if (scale <= 1) return;

        dragging = true;
        dragPointerId = event.pointerId;
        startX = event.clientX;
        startY = event.clientY;
        startTranslateX = translateX;
        startTranslateY = translateY;
        stage.classList.add("is-dragging");
    });

    stage.addEventListener("pointermove", (event) => {
        if (pointers.has(event.pointerId)) {
            pointers.set(event.pointerId, event);
        }

        if (pointers.size === 2) {
            const [a, b] = [...pointers.values()];
            const distance = distanceBetween(a, b);
            if (!pinchStartDistance) return;
            scale = clamp(pinchStartScale * (distance / pinchStartDistance), MIN_SCALE, MAX_SCALE);
            setTransform();
            return;
        }

        if (!dragging || dragPointerId !== event.pointerId || scale <= 1) return;
        translateX = startTranslateX + (event.clientX - startX);
        translateY = startTranslateY + (event.clientY - startY);
        setTransform();
    });

    function endPointer(event) {
        pointers.delete(event.pointerId);
        if (pointers.size < 2) pinchStartDistance = 0;
        if (dragPointerId === event.pointerId) {
            dragging = false;
            dragPointerId = null;
            stage.classList.remove("is-dragging");
        }
    }

    stage.addEventListener("pointerup", endPointer);
    stage.addEventListener("pointercancel", endPointer);
    stage.addEventListener("pointerleave", (event) => {
        if (event.pointerType === "mouse" && dragging) endPointer(event);
    });

    stage.addEventListener("dblclick", () => {
        if (scale > 1) resetZoom();
        else scale = 2;
        setTransform();
    });

    stage.addEventListener("click", (event) => {
        const now = Date.now();
        if (now - lastTap < 280) {
            if (scale > 1) resetZoom();
            else scale = 2;
            setTransform();
        }
        lastTap = now;
        if (event.target === stage && scale === 1) closeLightbox();
    });

    document.addEventListener("keydown", (event) => {
        if (!lightbox.classList.contains("is-open")) return;
        if (event.key === "Escape") closeLightbox();
        if (event.key === "+" || event.key === "=") changeZoom(STEP);
        if (event.key === "-") changeZoom(-STEP);
        if (event.key === "0") resetZoom();
    });
})();


/* =========================================================
   MOBILE NAVIGATION — PORTAL MENU ABOVE HERO
   Menu mobile dipindahkan sementara ke <body> agar tidak
   terjebak di dalam stacking/overflow header. Desktop tetap.
========================================================= */
(function () {
    function initMobileNavigation() {
        const toggler = document.querySelector('.navbar-toggler');
        const menu = document.getElementById('menu');
        const navbar = document.querySelector('.custom-navbar');
        const header = document.querySelector('.main-header');
        if (!toggler || !menu || !navbar || !header) return;

        const originalParent = menu.parentElement;
        const placeholder = document.createComment('mobile-menu-placeholder');
        originalParent.insertBefore(placeholder, menu);
        let isPortal = false;

        function isMobile() {
            return window.innerWidth <= 991;
        }

        function moveToPortal() {
            if (!isMobile() || isPortal) return;
            document.body.appendChild(menu);
            menu.classList.add('mobile-menu-portal');
            isPortal = true;

            // Bootstrap tidak perlu mengelola hamburger/dropdown pada mobile.
            toggler.removeAttribute('data-bs-toggle');
            toggler.removeAttribute('data-bs-target');
            menu.querySelectorAll('.dropdown-toggle').forEach(function (link) {
                link.removeAttribute('data-bs-toggle');
                link.removeAttribute('data-bs-target');
            });
            updateMenuPosition();
        }

        function restoreFromPortal() {
            if (isMobile() || !isPortal) return;
            menu.classList.remove('mobile-menu-portal', 'mobile-menu-open', 'show');
            menu.removeAttribute('style');
            placeholder.parentNode.insertBefore(menu, placeholder.nextSibling);
            isPortal = false;
            toggler.setAttribute('data-bs-toggle', 'collapse');
            toggler.setAttribute('data-bs-target', '#menu');
            toggler.setAttribute('aria-expanded', 'false');
            navbar.classList.remove('mobile-menu-active');
        }

        function updateMenuPosition() {
            if (!isMobile() || !isPortal) return;
            const rect = toggler.getBoundingClientRect();
            const headerRect = header.getBoundingClientRect();
            const gap = 6;
            const side = 8;
            const top = Math.max(0, Math.round(Math.max(headerRect.bottom, rect.bottom) + gap));
            menu.style.setProperty('--mobile-menu-top', top + 'px');
            menu.style.setProperty('--mobile-menu-left', side + 'px');
            menu.style.setProperty('--mobile-menu-right', side + 'px');
        }

        function setProductDropdown(link, open) {
            const item = link.closest('.dropdown');
            if (!item) return;
            const dropdown = item.querySelector('.dropdown-menu');
            if (!dropdown) return;
            dropdown.classList.toggle('show', open);
            link.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function closeMenu() {
            menu.classList.remove('mobile-menu-open', 'show');
            menu.setAttribute('aria-hidden', 'true');
            toggler.setAttribute('aria-expanded', 'false');
            toggler.setAttribute('aria-label', 'Buka menu');
            navbar.classList.remove('mobile-menu-active');
            menu.querySelectorAll('.dropdown-menu.show').forEach(function (dropdown) {
                dropdown.classList.remove('show');
            });
            menu.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach(function (link) {
                link.setAttribute('aria-expanded', 'false');
            });
        }

        function openMenu() {
            moveToPortal();
            updateMenuPosition();
            menu.classList.add('mobile-menu-open', 'show');
            menu.setAttribute('aria-hidden', 'false');
            toggler.setAttribute('aria-expanded', 'true');
            toggler.setAttribute('aria-label', 'Tutup menu');
            navbar.classList.add('mobile-menu-active');
        }

        toggler.addEventListener('click', function (event) {
            if (!isMobile()) return;
            event.preventDefault();
            event.stopPropagation();
            if (menu.classList.contains('mobile-menu-open')) closeMenu();
            else openMenu();
        }, true);

        menu.addEventListener('click', function (event) {
            if (!isMobile()) return;
            const dropdownToggle = event.target.closest('.dropdown-toggle');
            if (dropdownToggle) {
                event.preventDefault();
                event.stopPropagation();
                const dropdown = dropdownToggle.closest('.dropdown')?.querySelector('.dropdown-menu');
                setProductDropdown(dropdownToggle, !(dropdown && dropdown.classList.contains('show')));
                return;
            }
            const link = event.target.closest('a');
            if (link) closeMenu();
        });

        document.addEventListener('click', function (event) {
            if (!isMobile() || !menu.classList.contains('mobile-menu-open')) return;
            if (menu.contains(event.target) || toggler.contains(event.target)) return;
            closeMenu();
        });

        window.addEventListener('scroll', updateMenuPosition, { passive: true });
        window.addEventListener('resize', function () {
            if (isMobile()) {
                moveToPortal();
                syncFixedHeaderSpace();
            } else {
                closeMenu();
                restoreFromPortal();
                syncFixedHeaderSpace();
            }
        });

        function syncFixedHeaderSpace() {
            if (!isMobile()) {
                document.body.classList.remove('mobile-header-fixed-space');
                document.body.style.removeProperty('--mobile-header-height');
                return;
            }
            const height = Math.ceil(header.getBoundingClientRect().height);
            document.body.style.setProperty('--mobile-header-height', height + 'px');
            document.body.classList.add('mobile-header-fixed-space');
            if (menu.classList.contains('mobile-menu-open')) updateMenuPosition();
        }

        if (isMobile()) {
            moveToPortal();
            syncFixedHeaderSpace();
        } else {
            restoreFromPortal();
            syncFixedHeaderSpace();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNavigation);
    } else {
        initMobileNavigation();
    }
})();


/* =========================================================
   CATALOG — ICON FILTER HARGA
========================================================= */
(function () {
    function initPriceFilter() {
        const trigger = document.querySelector('.price-filter-trigger');
        const menu = document.querySelector('.price-filter-menu');
        if (!trigger || !menu) return;

        function close() {
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const willOpen = menu.hidden;
            menu.hidden = !willOpen;
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        menu.addEventListener('click', function (event) { event.stopPropagation(); });
        document.addEventListener('click', close);
        window.addEventListener('resize', close);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initPriceFilter);
    else initPriceFilter();
})();


/* =========================================================
   AUTO-SLIDE TESTIMONIAL CAROUSEL (MOBILE)
========================================================= */
document.addEventListener("DOMContentLoaded", function () {
    const testimonialCarousel = document.getElementById("testimonialMobileCarousel");
    if (testimonialCarousel && typeof bootstrap !== "undefined" && bootstrap.Carousel) {
        new bootstrap.Carousel(testimonialCarousel, {
            interval: 3000,
            ride: "carousel",
            touch: true,
            wrap: true
        });
    }
});

/* =========================================================
   PROGRESSIVE WEB APP (PWA) — SERVICE WORKER & BANNER PASANG APLIKASI
   - Hanya tampil di Mobile & Tablet (<= 991px), tidak tampil di Desktop
   - Otomatis sembunyi jika sudah diinstal (mode standalone) atau ditutup ('X')
   - Sinkronisasi real-time dengan website (Network-First Service Worker)
========================================================= */
(function () {
    const DISMISS_KEY = "rip_pwa_banner_dismissed";
    let deferredPrompt = null;

    // 1. Registrasi Service Worker (sw.js) & auto-sync saat aplikasi dibuka kembali
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", function () {
            const swUrl = window.__PWA_SW_URL__ || "/sw.js";
            navigator.serviceWorker
                .register(swUrl)
                .then(function (registration) {
                    // Periksa pembaruan setiap kali user membuka kembali aplikasi dari layar utama
                    document.addEventListener("visibilitychange", function () {
                        if (document.visibilityState === "visible") {
                            registration.update().catch(function () {});
                        }
                    });
                })
                .catch(function () {
                    // Tetap berjalan normal meski gagal registrasi SW
                });
        });
    }

    function isStandaloneApp() {
        return (
            (window.matchMedia && window.matchMedia("(display-mode: standalone)").matches) ||
            (window.matchMedia && window.matchMedia("(display-mode: fullscreen)").matches) ||
            window.navigator.standalone === true ||
            document.referrer.indexOf("android-app://") === 0
        );
    }

    function isMobileOrTablet() {
        return window.innerWidth <= 991;
    }

    function isDismissed() {
        try {
            return sessionStorage.getItem(DISMISS_KEY) === "1";
        } catch (e) {
            return false;
        }
    }

    function markDismissed() {
        try {
            sessionStorage.setItem(DISMISS_KEY, "1");
        } catch (e) {}
    }

    function updateBannerVisibility() {
        const banner = document.getElementById("pwaInstallBanner");
        if (!banner) return;

        // Sembunyikan jika di desktop (> 991px), sudah mode standalone (terinstal), atau sudah ditutup 'X'
        if (!isMobileOrTablet() || isStandaloneApp() || isDismissed()) {
            banner.hidden = true;
            return;
        }

        banner.hidden = false;
    }

    // 2. Tangkap event native browser 'beforeinstallprompt' (Chrome / Edge / Android)
    window.addEventListener("beforeinstallprompt", function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (typeof deferredPrompt.click !== "function") {
            deferredPrompt.click = function () {
                return deferredPrompt.prompt();
            };
        }
        updateBannerVisibility();
    });

    // 3. Sembunyikan banner otomatis saat aplikasi berhasil diinstal
    window.addEventListener("appinstalled", function () {
        deferredPrompt = null;
        const banner = document.getElementById("pwaInstallBanner");
        const guideModal = document.getElementById("pwaInstallGuideModal");
        if (banner) banner.hidden = true;
        if (guideModal) guideModal.hidden = true;
    });

    function initPwaBannerUI() {
        const banner = document.getElementById("pwaInstallBanner");
        const installBtn = document.getElementById("pwaInstallBtn");
        const dismissBtn = document.getElementById("pwaDismissBtn");
        const guideModal = document.getElementById("pwaInstallGuideModal");
        if (!banner || !installBtn || !dismissBtn) return;

        updateBannerVisibility();

        // Ketika tombol 'Pasang' diklik
        installBtn.addEventListener("click", async function () {
            if (deferredPrompt) {
                try {
                    // Panggil prompt instalasi native browser
                    await deferredPrompt.click();
                    const choiceResult = await deferredPrompt.userChoice;
                    if (choiceResult && choiceResult.outcome === "accepted") {
                        banner.hidden = true;
                    }
                    deferredPrompt = null;
                } catch (err) {
                    deferredPrompt = null;
                }
                return;
            }

            // Fallback untuk iOS Safari atau browser mobile yang membutuhkan instalasi via menu browser
            if (guideModal) {
                const ua = (navigator.userAgent || "").toLowerCase();
                const isIOS = /iphone|ipad|ipod/.test(ua);
                const iosStep = document.getElementById("pwaGuideIOS");
                const androidStep = document.getElementById("pwaGuideAndroid");
                if (iosStep && androidStep) {
                    if (isIOS) {
                        iosStep.style.display = "block";
                        androidStep.style.display = "none";
                    } else {
                        iosStep.style.display = "block";
                        androidStep.style.display = "block";
                    }
                }
                guideModal.hidden = false;
                guideModal.setAttribute("aria-hidden", "false");
            }
        });

        // Ketika tombol 'X' diklik
        dismissBtn.addEventListener("click", function () {
            markDismissed();
            banner.hidden = true;
        });

        // Tutup modal panduan
        if (guideModal) {
            guideModal.querySelectorAll("[data-pwa-guide-close]").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    guideModal.hidden = true;
                    guideModal.setAttribute("aria-hidden", "true");
                });
            });
        }

        // Pantau perubahan ukuran layar (agar otomatis hilang di desktop dan muncul di mobile/tablet)
        window.addEventListener("resize", updateBannerVisibility);

        if (window.matchMedia) {
            const standaloneQuery = window.matchMedia("(display-mode: standalone)");
            if (typeof standaloneQuery.addEventListener === "function") {
                standaloneQuery.addEventListener("change", updateBannerVisibility);
            }
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPwaBannerUI);
    } else {
        initPwaBannerUI();
    }
})();

