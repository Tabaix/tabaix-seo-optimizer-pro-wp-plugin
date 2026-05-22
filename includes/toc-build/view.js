document.addEventListener('DOMContentLoaded', function () {
    const tocContainers = document.querySelectorAll('.tabai-toc-container, .tabaix-seo-toc-wrap');

    tocContainers.forEach((container) => {
        const toggleBtn = container.querySelector('.tabai-toc-toggle, .tabaix-seo-toc-toggle');
        const list = container.querySelector('.tabai-toc-list, .tabaix-seo-toc-list');
        const links = container.querySelectorAll('.tabai-toc-link, .tabaix-seo-toc-list a');
        const backToTopBtn = document.querySelector('.tabai-toc-back-to-top, #tabaix-seo-back-top');

        if (toggleBtn && list) {
            toggleBtn.addEventListener('click', () => {
                const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
                toggleBtn.setAttribute('aria-expanded', !isExpanded);
                list.classList.toggle('is-hidden');
                toggleBtn.classList.toggle('active');
            });
        }

        links.forEach((link) => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                if (!href || href.charAt(0) !== '#') return;
                const targetId = href.substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    e.preventDefault();
                    const offset = 100;
                    const bodyRect = document.body.getBoundingClientRect().top;
                    const elementRect = targetElement.getBoundingClientRect().top;
                    const elementPosition = elementRect - bodyRect;
                    const offsetPosition = elementPosition - offset;
                    window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                    history.pushState(null, null, '#' + targetId);
                }
            });
        });

        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                backToTopBtn.style.display = window.scrollY > 400 ? 'flex' : 'none';
            });
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // Scroll spy
        const observerOptions = {
            root: null,
            rootMargin: '-100px 0px -60% 0px',
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    links.forEach(l => l.parentElement.classList.remove('is-active'));
                    const activeLink = container.querySelector(`.tabai-toc-link[href="#${entry.target.id}"], .tabaix-seo-toc-list a[href="#${entry.target.id}"]`);
                    if (activeLink) activeLink.parentElement.classList.add('is-active');
                }
            });
        }, observerOptions);

        links.forEach((link) => {
            const href = link.getAttribute('href');
            if (!href || href.charAt(0) !== '#') return;
            const target = document.getElementById(href.substring(1));
            if (target) observer.observe(target);
        });
    });
});
