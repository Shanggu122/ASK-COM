(function () {
    const config = document.querySelector('[data-subject-manager]');
    if (!config) return;

    const urls = {
        index: config.getAttribute('data-index-url') || '',
        store: config.getAttribute('data-store-url') || '',
        destroyBase: config.getAttribute('data-destroy-base') || '',
    };
    if (!urls.index || !urls.store || !urls.destroyBase) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const modal = document.getElementById('subjectManagerModal');
    const listEl = modal ? modal.querySelector('[data-manager-list]') : null;
    const form = modal ? modal.querySelector('form') : null;
    const input = form ? form.querySelector('input[name="subject_name"]') : null;
    const openBtns = document.querySelectorAll('[data-manage-subjects]');
    const closeBtns = modal ? modal.querySelectorAll('[data-close-subjects]') : [];
    let subjects = [];
    let loading = false;

    function showToast(message, isError) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, !!isError);
        } else {
            alert(message);
        }
    }

    function buildDestroyUrl(id) {
        return urls.destroyBase.replace(/\/$/, '') + '/' + id;
    }

    function openModal() {
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        fetchSubjects();
        setTimeout(() => {
            input?.focus();
        }, 50);
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (input) {
            input.value = '';
        }
    }

    function renderList() {
        if (!listEl) return;
        if (loading) {
            listEl.innerHTML = '<div class="subject-manager-empty">Loading...</div>';
            return;
        }
        if (!subjects.length) {
            listEl.innerHTML = '<div class="subject-manager-empty">No subjects yet.</div>';
            return;
        }
        const rows = subjects
            .map(function (item) {
                var safeName = escapeHtml(item.name);
                return (
                    '<div class="subject-manager-row" data-subject-id="' +
                    item.id +
                    '">' +
                    '<span class="subject-manager-name">' +
                    safeName +
                    '</span>' +
                    '<button type="button" class="subject-manager-delete" data-delete-subject="' +
                    item.id +
                    '" aria-label="Remove ' +
                    safeName +
                    '" title="Remove ' +
                    safeName +
                    '">X</button>' +
                    '</div>'
                );
            })
            .join('');
        listEl.innerHTML = rows;
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (m) {
            switch (m) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                case "'":
                    return '&#39;';
                default:
                    return m;
            }
        });
    }

    function fetchSubjects() {
        if (!urls.index) return;
        loading = true;
        renderList();
        fetch(urls.index, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (data && data.success) {
                    subjects = Array.isArray(data.subjects) ? data.subjects : [];
                    rebuildCheckboxLists();
                } else {
                    showToast(
                        data && data.message ? data.message : 'Failed to load subjects.',
                        true
                    );
                }
            })
            .catch(function () {
                showToast('Failed to load subjects.', true);
            })
            .finally(function () {
                loading = false;
                renderList();
            });
    }

    function rebuildCheckboxLists() {
        const addContainer = document.querySelector('[data-subject-list]');
        const editContainer = document.querySelector('[data-edit-subject-list]');

        if (addContainer) {
            const selected = new Set(
                Array.from(addContainer.querySelectorAll('input[type="checkbox"]:checked')).map(
                    function (cb) {
                        return cb.value;
                    }
                )
            );
            addContainer.innerHTML = subjects
                .map(function (item) {
                    return (
                        '<div class="subject-item">' +
                        '<input type="checkbox" name="subjects[]" value="' +
                        item.id +
                        '" id="subject_' +
                        item.id +
                        '">' +
                        '<label for="subject_' +
                        item.id +
                        '" class="subject-name">' +
                        escapeHtml(item.name) +
                        '</label>' +
                        '</div>'
                    );
                })
                .join('');
            Array.from(addContainer.querySelectorAll('input[type="checkbox"]')).forEach(
                function (cb) {
                    if (selected.has(cb.value)) cb.checked = true;
                }
            );
        }

        if (editContainer) {
            const selectedEdit = new Set(
                Array.from(editContainer.querySelectorAll('input[type="checkbox"]:checked')).map(
                    function (cb) {
                        return cb.value;
                    }
                )
            );
            editContainer.innerHTML = subjects
                .map(function (item) {
                    return (
                        '<div class="subject-item">' +
                        '<input type="checkbox" name="subjects[]" value="' +
                        item.id +
                        '" id="edit_subject_' +
                        item.id +
                        '">' +
                        '<label for="edit_subject_' +
                        item.id +
                        '" class="subject-name">' +
                        escapeHtml(item.name) +
                        '</label>' +
                        '</div>'
                    );
                })
                .join('');
            Array.from(editContainer.querySelectorAll('input[type="checkbox"]')).forEach(
                function (cb) {
                    if (selectedEdit.has(cb.value)) cb.checked = true;
                }
            );
            try {
                if (typeof window.refreshEditDirty === 'function') {
                    window.refreshEditDirty();
                }
            } catch (_) {}
        }
    }

    function handleAdd(evt) {
        evt.preventDefault();
        if (!input) return;
        const raw = input.value || '';
        const name = raw.trim();
        if (name === '') {
            showToast('Subject name is required.', true);
            input.focus();
            return;
        }
        fetch(urls.store, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ name: name }),
        })
            .then(function (res) {
                return res.json().then(function (body) {
                    return { status: res.status, body: body };
                });
            })
            .then(function (result) {
                if (
                    result.status >= 200 &&
                    result.status < 300 &&
                    result.body &&
                    result.body.success
                ) {
                    input.value = '';
                    subjects.push(result.body.subject);
                    subjects.sort(function (a, b) {
                        return a.name.localeCompare(b.name);
                    });
                    renderList();
                    rebuildCheckboxLists();
                    showToast('Subject added.', false);
                } else {
                    const msg =
                        result.body && result.body.message
                            ? result.body.message
                            : 'Failed to add subject.';
                    showToast(msg, true);
                }
            })
            .catch(function () {
                showToast('Failed to add subject.', true);
            });
    }

    function askDeleteConfirm(name) {
        return new Promise(function (resolve) {
            if (typeof window.adminConfirm === 'function') {
                const safeName = escapeHtml(name || 'this subject');
                const message =
                    '<p>Are you sure you want to remove <strong>' +
                    safeName +
                    '</strong> from the subject list?</p><p style="margin-top:8px; font-size:0.9rem; color:#4b5563;">Faculty assignments that currently use this subject will lose it unless reselected.</p>';
                window
                    .adminConfirm('Delete Subject', message, 'Yes, remove', 'Cancel')
                    .then(resolve);
            } else if (window.confirm) {
                resolve(window.confirm('Remove this subject?'));
            } else {
                resolve(true);
            }
        });
    }

    function handleDelete(id) {
        if (!id) return;
        var subject = subjects.find(function (item) {
            return String(item.id) === String(id);
        });
        askDeleteConfirm(subject ? subject.name : '').then(function (ok) {
            if (!ok) return;
            fetch(buildDestroyUrl(id), {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            })
                .then(function (res) {
                    return res.json().then(function (body) {
                        return { status: res.status, body: body };
                    });
                })
                .then(function (result) {
                    if (
                        result.status >= 200 &&
                        result.status < 300 &&
                        result.body &&
                        result.body.success
                    ) {
                        subjects = subjects.filter(function (item) {
                            return String(item.id) !== String(id);
                        });
                        renderList();
                        rebuildCheckboxLists();
                        showToast('Subject removed.', false);
                    } else {
                        const msg =
                            result.body && result.body.message
                                ? result.body.message
                                : 'Failed to delete subject.';
                        showToast(msg, true);
                    }
                })
                .catch(function () {
                    showToast('Failed to delete subject.', true);
                });
        });
    }

    if (form) {
        form.addEventListener('submit', handleAdd);
    }
    if (modal && listEl) {
        listEl.addEventListener('click', function (evt) {
            const btn = evt.target.closest('[data-delete-subject]');
            if (!btn) return;
            const id = btn.getAttribute('data-delete-subject');
            handleDelete(id);
        });
    }
    openBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal();
        });
    });
    closeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal();
        });
    });
    if (modal) {
        modal.addEventListener('click', function (evt) {
            if (evt.target === modal) {
                closeModal();
            }
        });
    }
})();
