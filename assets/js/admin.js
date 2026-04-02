document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('menuBtn');
        const overlay = document.getElementById('overlay');

        const closeSidebar = () => {
            document.body.classList.remove('sidebar-open');
        };

        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-open');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        document.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', closeSidebar);
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });
    });
