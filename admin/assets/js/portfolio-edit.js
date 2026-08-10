(function() {
    'use strict';

    /* Tab switching */
    var tabs = document.querySelectorAll('.svc-tab');
    var panels = document.querySelectorAll('.svc-tab-panel');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.dataset.tab;
            tabs.forEach(function(t) { t.classList.remove('svc-tab--active'); });
            tab.classList.add('svc-tab--active');
            panels.forEach(function(p) { p.classList.remove('svc-tab-panel--active'); p.hidden = true; });
            var panel = document.getElementById('tab-' + target);
            if (panel) { panel.classList.add('svc-tab-panel--active'); panel.hidden = false; }
        });
    });

    /* Sub-item: Gallery */
    setupSubList('gallery', 'gallery', ['image_url', 'caption', 'image_type']);

    /* Sub-item: FAQ */
    setupSubList('faqs', 'faq', ['question', 'answer']);

    /* Relation linking */
    setupRelations('testimonial');
    setupRelations('service');
    setupRelations('block');
    setupRelations('blog');

    /* ================================================================
       Auto-Save (debounced, 3 seconds after last change)
       ================================================================ */
    var autoSaveTimer = null;
    var autoSaveDirty = false;
    var autoSaveBtn = document.querySelector('button[type="submit"][name="save_project"]');

    function triggerAutoSave() {
        if (autoSaveDirty) return;
        autoSaveDirty = true;
        if (autoSaveBtn) {
            autoSaveBtn.textContent = autoSaveBtn.textContent.replace(/^/, '');
        }
    }

    document.querySelectorAll('.svc-form-input, .svc-form-textarea').forEach(function(el) {
        ['input', 'change'].forEach(function(ev) {
            el.addEventListener(ev, function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    if (autoSaveDirty && autoSaveBtn) {
                        autoSaveBtn.click();
                    }
                }, 3000);
            });
        });
    });

    /* ================================================================
       Revisions
       ================================================================ */
    var saveRevBtn = document.getElementById('pf-save-revision-btn');
    if (saveRevBtn) {
        saveRevBtn.addEventListener('click', function() {
            var projectId = saveRevBtn.dataset.projectId;
            var note = prompt('Revision note (optional):');
            ajaxPost({action:'save_revision', project_id:projectId, revision_note:note || ''}, function(r) {
                if (r.success) {
                    alert('Revision saved.');
                    location.reload();
                }
            });
        });
    }

    var revisionList = document.getElementById('pf-revision-list');
    if (revisionList) {
        revisionList.addEventListener('click', function(e) {
            var restoreBtn = e.target.closest('.svc-revision-restore');
            if (!restoreBtn) return;
            if (!confirm('Restore this revision? Current data will be overwritten.')) return;
            var revisionId = restoreBtn.dataset.revisionId;
            var projectId = document.querySelector('[data-project-id]') ? document.querySelector('[data-project-id]').dataset.projectId : 0;
            ajaxPost({action:'restore_revision', project_id:projectId, revision_id:revisionId}, function(r) {
                if (r.success) { alert('Revision restored.'); location.reload(); }
                else { alert('Failed to restore revision.'); }
            });
        });
    }

    function setupRelations(type) {
        var panel = document.getElementById('pf-' + type + '-linked');
        if (!panel) return;

        var projectId = 0;
        var subList = document.querySelector('.svc-sub-list');
        if (subList && subList.dataset.projectId) {
            projectId = subList.dataset.projectId;
        } else {
            var revBtn = document.getElementById('pf-save-revision-btn');
            if (revBtn && revBtn.dataset.projectId) projectId = revBtn.dataset.projectId;
        }

        var select = document.getElementById('pf-' + type + '-select');

        panel.addEventListener('click', function(e) {
            if (!e.target.classList.contains('svc-relation-remove')) return;
            var chip = e.target.closest('.svc-relation-chip');
            if (!chip || !confirm('Unlink?')) return;
            var relationId = chip.dataset.relationId;
            ajaxPost({action:'unlink_' + type, project_id:projectId, relation_id:relationId}, function(r) {
                if (r.success) chip.remove();
                if (select) {
                    var opt = select.querySelector('option[value="' + relationId + '"]');
                    if (opt) opt.disabled = false;
                }
            });
        });

        var linkBtn = document.querySelector('.svc-relation-link[data-type="' + type + '"]');
        if (linkBtn && select) {
            linkBtn.addEventListener('click', function() {
                var val = select.value;
                if (!val) return;
                var label = select.options[select.selectedIndex].textContent;
                ajaxPost({action:'link_' + type, project_id:projectId, relation_id:val}, function(r) {
                    if (r.success) {
                        var chip = document.createElement('div');
                        chip.className = 'svc-relation-chip';
                        chip.dataset.relationId = val;
                        chip.innerHTML = label + '<button type="button" class="svc-relation-remove" data-type="' + type + '" data-relation-id="' + val + '">&times;</button>';
                        panel.appendChild(chip);
                        select.options[select.selectedIndex].disabled = true;
                        select.value = '';
                    }
                });
            });
        }
    }
})();
