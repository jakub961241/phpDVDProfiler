/**
 * phpDVDProfiler Bootstrap - Main JavaScript
 * Handles AJAX navigation, sidebar, search, and UI interactions
 */

(function() {
    'use strict';

    // ===== State =====
    var currentMediaId = '';
    var currentCollection = 'owned';
    var currentSort = 'sorttitle';
    var currentOrder = 'asc';
    var currentSearchBy = '';
    var currentSearchText = '';
    var baseUrl = '';  // Set from PHP

    // ===== Initialization =====
    function init() {
        baseUrl = document.body.getAttribute('data-base-url') || 'index.php';
        currentCollection = document.body.getAttribute('data-collection') || 'owned';
        currentSort = document.body.getAttribute('data-sort') || 'sorttitle';
        currentOrder = document.body.getAttribute('data-order') || 'asc';
        currentSearchBy = document.body.getAttribute('data-searchby') || '';
        currentSearchText = document.body.getAttribute('data-searchtext') || '';
        currentMediaId = document.body.getAttribute('data-mediaid') || '';

        // Load initial content
        loadSidebar();
        if (currentMediaId) {
            loadContent(currentMediaId);
        }

        // Setup event handlers
        setupSearch();
        setupCollectionChange();
        setupLetterNav();
        setupSortHeaders();
    }

    // ===== AJAX helpers =====
    function fetchHtml(url) {
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        });
    }

    // ===== Sidebar (DVD list) =====
    function loadSidebar(startrow, letter) {
        var sidebar = document.getElementById('sidebar-content');
        if (!sidebar) return;

        sidebar.innerHTML = '<div class="loading-spinner">Loading...</div>';

        var url = baseUrl + '?action=menu&ajax=1'
            + '&collection=' + encodeURIComponent(currentCollection)
            + '&sort=' + encodeURIComponent(currentSort)
            + '&order=' + encodeURIComponent(currentOrder)
            + '&searchby=' + encodeURIComponent(currentSearchBy)
            + '&searchtext=' + encodeURIComponent(currentSearchText);

        if (startrow !== undefined) url += '&startrow=' + startrow;
        if (letter !== undefined) url += '&letter=' + encodeURIComponent(letter);

        fetchHtml(url).then(function(html) {
            sidebar.innerHTML = html;
            attachSidebarClicks();
        }).catch(function(err) {
            sidebar.innerHTML = '<div class="p-3 text-danger">Error loading list: ' + err.message + '</div>';
        });
    }

    function attachSidebarClicks() {
        var links = document.querySelectorAll('#sidebar-content a[data-mediaid]');
        links.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var mediaid = this.getAttribute('data-mediaid');
                loadContent(mediaid);

                // Update active state
                document.querySelectorAll('#sidebar-content .dvd-row').forEach(function(row) {
                    row.classList.remove('active');
                });
                this.closest('.dvd-row').classList.add('active');

                // On mobile, close offcanvas
                var offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('sidebarOffcanvas'));
                if (offcanvas) offcanvas.hide();
            });
        });

        // Box set toggle buttons
        var toggles = document.querySelectorAll('#sidebar-content .boxset-toggle');
        toggles.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var target = this.getAttribute('data-target');
                var el = document.getElementById(target);
                if (el) {
                    if (el.style.display === 'none') {
                        el.style.display = '';
                        this.textContent = '−';
                    } else {
                        el.style.display = 'none';
                        this.textContent = '+';
                    }
                }
            });
        });

        // Sidebar pagination links
        var pagLinks = document.querySelectorAll('#sidebar-content a[data-startrow]');
        pagLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadSidebar(this.getAttribute('data-startrow'));
            });
        });

        // Sort header clicks
        var sortLinks = document.querySelectorAll('#sidebar-content a[data-sort]');
        sortLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                currentSort = this.getAttribute('data-sort');
                currentOrder = this.getAttribute('data-order') || 'asc';
                loadSidebar();
            });
        });
    }

    // ===== Main content =====
    function loadContent(mediaid) {
        var main = document.getElementById('main-content');
        if (!main) return;

        currentMediaId = mediaid;
        main.innerHTML = '<div class="loading-spinner">Loading...</div>';

        var url;
        if (mediaid === 'Statistics' || mediaid === 'WatchedStatistics') {
            url = baseUrl + '?action=show&mediaid=' + encodeURIComponent(mediaid) + '&ajax=1';
        } else if (mediaid === 'Chooser') {
            url = 'Chooser.php?ajax=1';
        } else if (mediaid.indexOf('Gallery') === 0) {
            url = baseUrl + '?action=show&mediaid=' + encodeURIComponent(mediaid) + '&ajax=1'
                + '&ct=' + encodeURIComponent(currentCollection)
                + '&sort=' + encodeURIComponent(currentSort)
                + '&order=' + encodeURIComponent(currentOrder)
                + '&searchby=' + encodeURIComponent(currentSearchBy)
                + '&searchtext=' + encodeURIComponent(currentSearchText);
        } else {
            url = baseUrl + '?action=show&mediaid=' + encodeURIComponent(mediaid) + '&ajax=1';
        }

        fetchHtml(url).then(function(html) {
            main.innerHTML = html;
            runInlineScripts(main);
        }).catch(function(err) {
            main.innerHTML = '<div class="p-3 text-danger">Error loading content: ' + err.message + '</div>';
        });
    }

    function runInlineScripts(container) {
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function(oldScript) {
            var newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    // ===== Search =====
    function setupSearch() {
        var form = document.getElementById('search-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var searchby = form.querySelector('[name=searchby]').value;
            var searchtext = '';

            // Get the right input value based on search type
            var textbox = document.getElementById('search-textbox');
            var combo = document.getElementById('search-combo-' + searchby);

            if (combo && combo.style.display !== 'none') {
                searchtext = combo.value;
            } else if (textbox) {
                searchtext = textbox.value;
            }

            currentSearchBy = searchby;
            currentSearchText = searchtext;
            loadSidebar();
        });

        // Search type change - show/hide combo boxes
        var searchSelect = form.querySelector('[name=searchby]');
        if (searchSelect) {
            searchSelect.addEventListener('change', function() {
                switchSearchField(this.value);
            });
        }

        // Clear button
        var clearBtn = document.getElementById('search-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                currentSearchBy = '';
                currentSearchText = '';
                var textbox = document.getElementById('search-textbox');
                if (textbox) textbox.value = '';
                // Hide all combos
                document.querySelectorAll('.search-combo').forEach(function(c) {
                    c.style.display = 'none';
                });
                var textbox = document.getElementById('search-textbox');
                if (textbox) textbox.style.display = '';
                loadSidebar();
            });
        }
    }

    function switchSearchField(searchby) {
        var textbox = document.getElementById('search-textbox');
        var combos = ['genre', 'rating', 'coo', 'locale', 'purchase', 'mediatype', 'tag', 'lock', 'medialanguages', 'mediasubtitles'];

        // Hide all combos first
        combos.forEach(function(name) {
            var el = document.getElementById('search-combo-' + name);
            if (el) el.style.display = 'none';
        });

        // Show textbox by default
        if (textbox) textbox.style.display = '';

        // If this search type has a combo, show it and hide textbox
        var combo = document.getElementById('search-combo-' + searchby);
        if (combo) {
            combo.style.display = '';
            if (textbox) textbox.style.display = 'none';
        }
    }

    // ===== Collection change =====
    function setupCollectionChange() {
        var select = document.getElementById('collection-select');
        if (!select) return;

        select.addEventListener('change', function() {
            currentCollection = this.value;
            currentSearchBy = '';
            currentSearchText = '';
            loadSidebar();
        });
    }

    // ===== Letter navigation =====
    var currentLetter = '';

    function setupLetterNav() {
        document.querySelectorAll('#letter-nav a').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var letter = this.getAttribute('data-letter');
                // Toggle: click same letter again to clear filter
                if (currentLetter === letter) {
                    currentLetter = '';
                } else {
                    currentLetter = letter;
                }
                // Update active highlight
                document.querySelectorAll('#letter-nav a').forEach(function(a) {
                    a.classList.remove('active');
                });
                if (currentLetter) {
                    this.classList.add('active');
                }
                loadSidebar(0, currentLetter || undefined);
            });
        });
    }

    // ===== Sort headers =====
    function setupSortHeaders() {
        // Handled via attachSidebarClicks since sort headers are inside sidebar content
    }

    // ===== Cookie helpers =====
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
    }

    // ===== Tools menu handlers =====
    window.loadStatistics = function() {
        loadContent('Statistics');
    };

    window.loadGallery = function() {
        loadContent('Gallery');
    };

    window.loadBackGallery = function() {
        loadContent('GalleryB');
    };

    window.loadChooser = function() {
        loadContent('Chooser');
    };

    window.loadWatched = function() {
        loadContent('WatchedStatistics');
    };

    // ===== Popup (actor/crew/studio filmography) =====
    window.loadPopup = function(acttype, fullname, sortby) {
        var main = document.getElementById('main-content');
        if (!main) return;
        main.innerHTML = '<div class="loading-spinner">Loading...</div>';
        var url = 'popup.php?acttype=' + encodeURIComponent(acttype)
            + '&fullname=' + encodeURIComponent(fullname)
            + (sortby ? '&sortby=' + encodeURIComponent(sortby) : '')
            + '&ajax=1';
        fetchHtml(url).then(function(html) {
            main.innerHTML = html;
            runInlineScripts(main);
        }).catch(function(err) {
            main.innerHTML = '<div class="p-3 text-danger">Error: ' + err.message + '</div>';
        });
    };

    // ===== Language change =====
    window.changeLanguage = function(locale) {
        setCookie('locale', locale, 3650);
        window.location.reload();
    };

    // ===== Expose for PHP-generated onclick handlers =====
    window.loadDvdContent = loadContent;
    window.loadDvdSidebar = loadSidebar;

    // ===== SwitchOutRows (backward compat for cast/crew dividers) =====
    window.SwitchOutRows = function(theitems, obj) {
        var item = document.getElementById(theitems);
        if (item) {
            if (item.style.display === 'none') {
                item.style.display = '';
                obj.src = 'gfx/minus.gif';
            } else {
                item.style.display = 'none';
                obj.src = 'gfx/plus.gif';
            }
        }
    };

    // ===== Box set toggle (backward compat) =====
    window.dh = function(theitems, obj) {
        var item = document.getElementById(theitems);
        if (item) {
            if (item.style.display === 'none') {
                item.style.display = '';
                obj.src = 'gfx/minus.gif';
            } else {
                item.style.display = 'none';
                obj.src = 'gfx/plus.gif';
            }
        }
    };

    // ===== Image modal =====
    function setupImageModal() {
        document.addEventListener('click', function(e) {
            var link = e.target.closest('a[href*="?img="], a[href*="&img="]');
            if (!link) return;
            e.preventDefault();
            var href = link.getAttribute('href');
            // Extract img parameter
            var match = href.match(/[?&]img=([^&]*)/);
            if (!match) return;
            var imgSrc = decodeURIComponent(match[1]);
            // Get title from the img inside the link, or from the link title
            var title = link.getAttribute('title') || '';
            if (!title) {
                var img = link.querySelector('img');
                if (img) title = img.getAttribute('title') || img.getAttribute('alt') || '';
            }
            var modalImg = document.getElementById('imageModalImg');
            var modalTitle = document.getElementById('imageModalTitle');
            if (modalImg && modalTitle) {
                modalImg.src = imgSrc;
                modalImg.alt = title;
                modalTitle.textContent = title;
                var modal = new bootstrap.Modal(document.getElementById('imageModal'));
                modal.show();
            }
        });
    }

    // ===== Start =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { init(); setupImageModal(); });
    } else {
        init();
        setupImageModal();
    }

})();
