/**
 * Swap Design - Blog Editor JS
 * Rich toolbar, auto-save, revision management, taxonomy sync.
 */
(function () {
    'use strict';

    var editor  = document.querySelector('.blog-editor__content');
    if (!editor) return;

    var titleInput  = document.getElementById('blog-title');
    var slugInput   = document.getElementById('blog-slug');
    var descInput   = document.getElementById('blog-desc');
    var postId      = document.getElementById('blog-post-id').value || '';
    var saveStatus  = document.getElementById('blog-save-status');
    var toolbar     = document.getElementById('blog-toolbar');

    /* ================================================================
       Toolbar
       ================================================================ */
    toolbar.addEventListener('click', function (e) {
        var btn = e.target.closest('button');
        if (!btn) return;
        e.preventDefault();

        var cmd = btn.getAttribute('data-cmd');
        var arg = btn.getAttribute('data-arg') || null;

        if (cmd === 'createLink') {
            var url = prompt('Enter URL:');
            if (url) document.execCommand('createLink', false, url);
            return;
        }

        document.execCommand(cmd, false, arg);
        editor.focus();
    });

    /* Insert Image */
    document.getElementById('blog-insert-image').addEventListener('click', function () {
        var url = prompt('Enter image URL:');
        if (url) {
            document.execCommand('insertHTML', false, '<figure><img src="' + url + '" alt="" style="max-width:100%"><figcaption>Caption</figcaption></figure>');
        }
    });

    /* Insert Code Block */
    document.getElementById('blog-insert-code').addEventListener('click', function () {
        document.execCommand('insertHTML', false, '<pre><code>// Your code here</code></pre>');
    });

    /* Insert Callout */
    document.getElementById('blog-insert-callout').addEventListener('click', function () {
        document.execCommand('insertHTML', false, '<div class="callout callout--info"><strong>Note:</strong> Your callout text here.</div>');
    });

    /* Insert Video */
    document.getElementById('blog-insert-video').addEventListener('click', function () {
        var url = prompt('Enter YouTube/Vimeo embed URL:');
        if (url) {
            document.execCommand('insertHTML', false, '<div class="video-embed"><iframe src="' + url + '" frameborder="0" allowfullscreen></iframe></div>');
        }
    });

    /* Insert TOC placeholder */
    document.getElementById('blog-generate-toc').addEventListener('click', function () {
        document.execCommand('insertHTML', false, '<!--toc-->');
    });

    /* ================================================================
       Auto-generate slug from title
       ================================================================ */
    titleInput.addEventListener('blur', function () {
        if (!slugInput.value) {
            slugInput.value = slugify(titleInput.value);
        }
    });

    /* ================================================================
       Auto-save (debounce 3s)
       ================================================================ */
    var autoSaveTimer;

    function collectData() {
        return {
            action: 'save',
            id: postId,
            title: titleInput.value,
            slug: slugInput.value,
            short_description: descInput.value,
            content: editor.innerHTML,
            featured_image: document.getElementById('blog-featured-image').value,
            gallery: JSON.stringify(getGalleryUrls()),
            author_id: document.getElementById('blog-author').value,
            published_at: document.getElementById('blog-published-at').value,
            status: document.getElementById('blog-status').value,
            is_featured: document.getElementById('blog-is-featured').checked ? '1' : '',
            is_sticky: document.getElementById('blog-is-sticky').checked ? '1' : '',
            seo_title: document.getElementById('blog-seo-title').value,
            meta_description: document.getElementById('blog-meta-desc').value,
            focus_keyword: document.getElementById('blog-focus-keyword').value,
            canonical_url: document.getElementById('blog-canonical').value,
            og_image: document.getElementById('blog-og-image').value,
            twitter_card: document.getElementById('blog-twitter-card').value,
            categories: getCheckedIds('blog-categories'),
            tags: document.getElementById('blog-tags-input').value,
            related_services: getCheckedIds('blog-rel-services'),
            related_portfolio: getCheckedIds('blog-rel-portfolio'),
            related_posts: getCheckedIds('blog-rel-posts'),
        };
    }

    function triggerAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
            ajaxPost(collectData(), function (res) {
                if (res.ok) {
                    showSaved();
                    if (res.id && !postId) {
                        postId = res.id;
                        document.getElementById('blog-post-id').value = postId;
                    }
                    if (res.slug && !slugInput.value) {
                        slugInput.value = res.slug;
                    }
                }
            });
        }, 3000);
    }

    [titleInput, slugInput, descInput, editor].forEach(function (el) {
        if (!el) return;
        el.addEventListener(el === editor ? 'input' : 'keyup', triggerAutoSave);
    });

    /* ================================================================
       Save buttons
       ================================================================ */
    function savePost(status) {
        document.getElementById('blog-status').value = status;
        var data = collectData();
        if (status === 'published' && !data.published_at) {
            data.published_at = new Date().toISOString().slice(0, 16);
        }
        ajaxPost(data, function (res) {
            if (res.ok) {
                showSaved();
                if (res.id) {
                    postId = res.id;
                    document.getElementById('blog-post-id').value = postId;
                }
                if (!res.id) {
                    window.location.reload();
                }
            }
        });
    }

    document.getElementById('blog-save-publish').addEventListener('click', function () { savePost('published'); });
    document.getElementById('blog-save-draft').addEventListener('click', function () { savePost('draft'); });

    /* Save Revision */
    document.getElementById('blog-save-revision').addEventListener('click', function () {
        var note = prompt('Revision note (optional):') || '';
        ajaxPost({ action: 'save_revision', id: postId, note: note }, function (res) {
            if (res.ok) window.location.reload();
        });
    });

    /* Restore revision */
    document.addEventListener('click', function (e) {
        var restoreBtn = e.target.closest('.blog-revision__restore');
        if (!restoreBtn) return;
        var revId = restoreBtn.getAttribute('data-rev-id');
        if (!confirm('Restore this revision? Current content will be replaced.')) return;
        ajaxPost({ action: 'restore_revision', revision_id: revId }, function (res) {
            if (res.ok) window.location.reload();
        });
    });

    /* ================================================================
       Add category via AJAX
       ================================================================ */
    document.getElementById('blog-add-category').addEventListener('click', function () {
        var input = document.getElementById('blog-new-category');
        var name = input.value.trim();
        if (!name) return;
        ajaxPost({ action: 'add_category', name: name }, function (res) {
            if (res.ok && res.category) {
                var div = document.getElementById('blog-categories');
                var label = document.createElement('label');
                label.className = 'blog-editor__checkitem';
                label.innerHTML = '<input type="checkbox" value="' + res.category.id + '" checked><span>' + res.category.name + ' (0)</span>';
                div.appendChild(label);
                input.value = '';
            }
        });
    });

    /* ================================================================
       Suggested tag click
       ================================================================ */
    document.addEventListener('click', function (e) {
        var sug = e.target.closest('.blog-editor__suggested-tag');
        if (!sug) return;
        var tagName = sug.getAttribute('data-tag');
        var tagsInput = document.getElementById('blog-tags-input');
        var current = tagsInput.value.split(',').map(function (t) { return t.trim(); });
        if (current.indexOf(tagName) === -1) {
            current.push(tagName);
            tagsInput.value = current.filter(Boolean).join(', ');
        }
    });

    /* ================================================================
       Gallery
       ================================================================ */
    document.getElementById('blog-add-gallery').addEventListener('click', function () {
        var url = prompt('Enter image URL:');
        if (!url) return;
        var container = document.getElementById('blog-gallery');
        var div = document.createElement('div');
        div.className = 'blog-editor__gallery-item';
        div.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="blog-editor__gallery-remove">&times;</button><input type="hidden" name="gallery[]" value="' + url + '">';
        container.appendChild(div);
        triggerAutoSave();
    });

    editor.parentElement.addEventListener('click', function (e) {
        var remBtn = e.target.closest('.blog-editor__gallery-remove');
        if (remBtn) {
            remBtn.closest('.blog-editor__gallery-item').remove();
            triggerAutoSave();
        }
    });

    function getGalleryUrls() {
        var items = document.querySelectorAll('.blog-editor__gallery-item input[name="gallery[]"]');
        return Array.from(items).map(function (el) { return el.value; });
    }

    /* Featured image preview */
    document.getElementById('blog-featured-image').addEventListener('input', function () {
        var preview = document.getElementById('blog-featured-preview');
        var url = this.value.trim();
        preview.innerHTML = url ? '<img src="' + url + '" alt="">' : '';
    });

    /* ================================================================
       Helpers
       ================================================================ */
    function slugify(text) {
        return text.toLowerCase().trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/^-|-$/g, '');
    }

    function showSaved() {
        if (!saveStatus) return;
        saveStatus.hidden = false;
        setTimeout(function () { saveStatus.hidden = true; }, 2000);
    }

    function getCheckedIds(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return '';
        var checks = container.querySelectorAll('input[type="checkbox"]:checked');
        return Array.from(checks).map(function (cb) { return cb.value; }).join(',');
    }

    function ajaxPost(data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.BLOG_AJAX_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-CSRF-Token', window.BLOG_CSRF_TOKEN || '');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            try { callback(JSON.parse(xhr.responseText)); }
            catch (e) { callback({ ok: false, message: 'Parse error' }); }
        };
        xhr.onerror = function () { callback({ ok: false, message: 'Network error' }); };

        var parts = [];
        for (var k in data) {
            if (!data.hasOwnProperty(k)) continue;
            parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
        }
        xhr.send(parts.join('&'));
    }
})();
