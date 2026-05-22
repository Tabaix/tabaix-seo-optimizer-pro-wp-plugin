document.addEventListener('DOMContentLoaded', function () {
    function smoothScrollIntoView(target) {
        if (!target) return;
        var offset = 90;
        var position = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({ top: position, behavior: 'smooth' });
    }

    document.querySelectorAll('.tabaix-seo-toc-wrap, .tabai-toc-container').forEach(function (container) {
        var list = container.querySelector('.tabaix-seo-toc-list, .tabai-toc-list');
        var title = container.querySelector('.tabaix-seo-toc-title, .tabai-toc-header');
        var toggleBtn = container.querySelector('.tabaix-seo-toc-toggle, .tabai-toc-toggle');
        var links = container.querySelectorAll('.tabaix-seo-toc-list a, .tabai-toc-link');

        if (toggleBtn && list) {
            toggleBtn.addEventListener('click', function () {
                var isHidden = list.style.display === 'none';
                list.style.display = isHidden ? '' : 'none';
                toggleBtn.textContent = isHidden ? '▼' : '▶';
                toggleBtn.classList.toggle('active', isHidden);
            });
        }

        if (title && list && !toggleBtn) {
            title.addEventListener('click', function () {
                var isHidden = list.style.display === 'none';
                list.style.display = isHidden ? '' : 'none';
            });
        }

        links.forEach(function (link) {
            link.addEventListener('click', function (e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    smoothScrollIntoView(target);
                }
            });
        });

        var observerOptions = {
            root: null,
            rootMargin: '-100px 0px -60% 0px',
            threshold: 0
        };

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var activeLink = container.querySelector('[href="#' + entry.target.id + '"]');
                if (!activeLink) return;
                container.querySelectorAll('.is-active').forEach(function (activeItem) {
                    activeItem.classList.remove('is-active');
                });
                var listItem = activeLink.closest('li');
                if (listItem) {
                    listItem.classList.add('is-active');
                }
            });
        }, observerOptions);

        links.forEach(function (link) {
            var id = link.getAttribute('href');
            if (!id || id.charAt(0) !== '#') return;
            var target = document.getElementById(id.substring(1));
            if (target) {
                observer.observe(target);
            }
        });
    });

    document.querySelectorAll('.tabaix-seo-toc-mobile-trigger, .tabai-toc-mobile-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector('.tabaix-seo-toc-wrap, .tabai-toc-container');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    document.querySelectorAll('#tabaix-seo-back-top, .tabai-toc-back-to-top').forEach(function (btn) {
        btn.style.display = 'none';
        window.addEventListener('scroll', function () {
            btn.style.display = window.scrollY > 400 ? 'flex' : 'none';
        });
        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
});
