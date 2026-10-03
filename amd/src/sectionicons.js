// This file is part of a 108design source-available software product.

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Modal from 'core/modal';
import CourseEvents from 'core_course/events';
import {add as addToast} from 'core/toast';

let instance = null;
const ICON_RENDER_LIMIT = 120;

const element = (tag, classes = '', text = '') => {
    const node = document.createElement(tag);
    if (classes) {
        node.className = classes;
    }
    if (text) {
        node.textContent = text;
    }
    return node;
};

const escapeHtml = value => {
    const node = document.createElement('div');
    node.textContent = value;
    return node.innerHTML;
};

const fileBase64 = file => new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.addEventListener('load', () => resolve(String(reader.result || '').split(',', 2)[1] || ''));
    reader.addEventListener('error', () => reject(reader.error));
    reader.readAsDataURL(file);
});

class SectionIcons {
    constructor(config) {
        this.config = config;
        this.assignments = new Map(Object.entries(config.assignments || {}));
        this.refreshTimers = [];
        this.onCourseChanged = () => this.scheduleRendering();
        document.addEventListener(CourseEvents.stateChanged, this.onCourseChanged);
        document.addEventListener(CourseEvents.sectionRefreshed, this.onCourseChanged);
        this.bindManager();
        this.scheduleRendering();
        this.updateManagerPreviews();
    }

    merge(config) {
        this.config = {...this.config, ...config};
        Object.entries(config.assignments || {}).forEach(([id, assignment]) => this.assignments.set(id, assignment));
        this.bindManager();
        this.scheduleRendering();
        this.updateManagerPreviews();
    }

    scheduleRendering() {
        this.refreshTimers.forEach(timer => window.clearTimeout(timer));
        this.refreshTimers = [0, 100, 400, 1200].map(delay => window.setTimeout(() => {
            this.applyCourseIndex();
            this.applyCourseContent();
        }, delay));
    }

    bindManager() {
        document.querySelectorAll('[data-local-sectionicons-show-content]').forEach(toggle => {
            toggle.checked = Boolean(this.config.showContent);
            if (toggle.dataset.initialised) {
                return;
            }
            toggle.dataset.initialised = '1';
            toggle.addEventListener('change', async() => {
                const requested = toggle.checked;
                toggle.disabled = true;
                try {
                    const response = await Ajax.call([{
                        methodname: 'local_sectionicons_set_content_display',
                        args: {courseid: this.config.courseid, enabled: requested},
                    }])[0];
                    this.config.showContent = Boolean(response.enabled);
                    toggle.checked = this.config.showContent;
                    this.applyCourseContent();
                    addToast(this.config.strings.contentSaved, {type: 'success'});
                } catch (error) {
                    toggle.checked = !requested;
                    Notification.exception(error);
                } finally {
                    toggle.disabled = false;
                }
            });
        });
        document.querySelectorAll('[data-local-sectionicons-manager]').forEach(root => {
            if (root.dataset.initialised) {
                return;
            }
            root.dataset.initialised = '1';
            root.addEventListener('click', event => {
                const button = event.target.closest('[data-local-sectionicons-edit]');
                if (button && root.contains(button)) {
                    this.openPicker(Number(button.dataset.sectionId), button.dataset.sectionTitle || '', button);
                }
            });
        });
    }

    applyCourseIndex() {
        const editable = this.config.canManage
            && (this.config.inlineEditing || document.body.classList.contains('editing'));
        const roots = new Set(document.querySelectorAll('#courseindex, [data-region="courseindex"]'));
        roots.forEach(root => root.querySelectorAll('[data-for="section"][data-id]').forEach(section => {
            const item = [...section.children].find(child => child.matches('[data-for="section_item"]'));
            const title = item ? [...item.children].find(child => child.matches('[data-for="section_title"]')) : null;
            if (!item || !title) {
                return;
            }
            const sectionId = String(section.dataset.id);
            const assignment = this.assignments.get(sectionId);
            let holder = [...item.children]
                .find(child => child.matches('[data-local-sectionicons-holder]'));
            if (!assignment && !editable) {
                holder?.remove();
                item.classList.remove('local-sectionicons-has-control');
                return;
            }
            const desiredTag = editable ? 'BUTTON' : 'SPAN';
            if (holder && holder.tagName !== desiredTag) {
                const replacement = element(desiredTag.toLocaleLowerCase(), 'local-sectionicons-holder');
                replacement.dataset.localSectioniconsHolder = '1';
                holder.replaceWith(replacement);
                holder = replacement;
            }
            if (!holder) {
                holder = element(editable ? 'button' : 'span', 'local-sectionicons-holder');
                holder.dataset.localSectioniconsHolder = '1';
                item.insertBefore(holder, title);
            }
            if (editable) {
                holder.type = 'button';
                holder.classList.add('local-sectionicons-inline-edit');
                holder.dataset.sectionId = sectionId;
                holder.dataset.sectionTitle = title.textContent.trim();
                holder.setAttribute('aria-label', this.label('edit', holder.dataset.sectionTitle));
                if (!holder.dataset.initialised) {
                    holder.dataset.initialised = '1';
                    holder.addEventListener('click', event => {
                        event.preventDefault();
                        event.stopPropagation();
                        this.openPicker(Number(holder.dataset.sectionId), holder.dataset.sectionTitle, holder);
                    });
                }
                item.classList.add('local-sectionicons-has-control');
            } else {
                holder.setAttribute('aria-hidden', 'true');
                item.classList.remove('local-sectionicons-has-control');
            }
            holder.replaceChildren(this.visual(assignment, true));
        }));
    }

    /** Render assigned visuals in Moodle's main course-content section headings. */
    applyCourseContent() {
        if (!this.config.showContent) {
            document.querySelectorAll('[data-local-sectionicons-content-holder]').forEach(holder => {
                holder.parentElement?.classList.remove('local-sectionicons-content-title');
                holder.remove();
            });
            return;
        }
        const editable = this.config.canManage
            && (this.config.inlineEditing || document.body.classList.contains('editing'));
        document.querySelectorAll('[data-for="section"][data-id]').forEach(section => {
            if (section.closest('#courseindex, [data-region="courseindex"]')) {
                return;
            }
            const sectionId = String(section.dataset.id);
            const title = [...section.querySelectorAll('[data-for="section_title"], .sectionname')]
                .find(candidate => candidate.classList.contains('sectionname')
                    && candidate.closest('[data-for="section"][data-id]') === section
                    && (!candidate.dataset.id || String(candidate.dataset.id) === sectionId));
            if (!title) {
                return;
            }
            const assignment = this.assignments.get(sectionId);
            let holder = [...title.children]
                .find(child => child.matches('[data-local-sectionicons-content-holder]'));
            if (!assignment) {
                holder?.remove();
                title.classList.remove('local-sectionicons-content-title');
                return;
            }
            const desiredTag = editable ? 'BUTTON' : 'SPAN';
            if (holder && holder.tagName !== desiredTag) {
                const replacement = element(desiredTag.toLowerCase(), 'local-sectionicons-content-holder');
                replacement.dataset.localSectioniconsContentHolder = '1';
                holder.replaceWith(replacement);
                holder = replacement;
            }
            if (!holder) {
                holder = element(editable ? 'button' : 'span', 'local-sectionicons-content-holder');
                holder.dataset.localSectioniconsContentHolder = '1';
                title.insertBefore(holder, title.firstChild);
            }
            if (editable) {
                holder.type = 'button';
                holder.classList.add('local-sectionicons-content-edit');
                holder.removeAttribute('aria-hidden');
                holder.dataset.sectionId = sectionId;
                holder.dataset.sectionTitle = title.textContent.trim();
                holder.setAttribute('aria-label', this.label('edit', holder.dataset.sectionTitle));
                if (!holder.dataset.initialised) {
                    holder.dataset.initialised = '1';
                    holder.addEventListener('click', event => {
                        event.preventDefault();
                        event.stopPropagation();
                        this.openPicker(Number(holder.dataset.sectionId), holder.dataset.sectionTitle, holder);
                    });
                }
            } else {
                holder.setAttribute('aria-hidden', 'true');
            }
            holder.replaceChildren(this.visual(assignment));
            title.classList.add('local-sectionicons-content-title');
        });
    }

    updateManagerPreviews() {
        document.querySelectorAll('[data-local-sectionicons-section-row]').forEach(row => {
            const preview = row.querySelector('[data-local-sectionicons-preview]');
            if (!preview) {
                return;
            }
            const assignment = this.assignments.get(String(row.dataset.sectionId));
            preview.dataset.hasIcon = assignment ? '1' : '0';
            preview.replaceChildren(this.visual(assignment, true));
        });
    }

    visual(assignment, placeholder = false) {
        const size = ['small', 'normal', 'large', 'max'].includes(assignment?.size)
            ? assignment.size : 'normal';
        if (assignment?.kind === 'image' && assignment.url) {
            const image = element('img', `local-sectionicons-image local-sectionicons-size-${size}`);
            image.src = assignment.url;
            image.alt = '';
            image.setAttribute('aria-hidden', 'true');
            return image;
        }
        if (assignment?.kind === 'font' && assignment.class) {
            const icon = element('i', `${assignment.class} local-sectionicons-font local-sectionicons-size-${size}`);
            icon.setAttribute('aria-hidden', 'true');
            return icon;
        }
        const empty = element('i', placeholder ? 'fa fa-plus local-sectionicons-placeholder' : 'fa fa-ban');
        empty.setAttribute('aria-hidden', 'true');
        return empty;
    }

    label(key, title) {
        return String(this.config.strings[key] || '').replace('__TITLE__', title);
    }

    async openPicker(sectionId, title, returnElement) {
        if (!this.config.canManage || !sectionId) {
            return;
        }
        const body = this.pickerBody(sectionId);
        const close = element('button', 'btn btn-primary', this.config.strings.close);
        close.type = 'button';
        close.dataset.localSectioniconsClose = '1';
        const modal = await Modal.create({
            title: escapeHtml(this.label('choose', title)),
            body: body.outerHTML,
            footer: close.outerHTML,
            large: false,
            removeOnClose: true,
            returnElement,
        });
        const root = modal.getRoot()[0];
        root.querySelector('[data-local-sectionicons-close]').addEventListener('click', () => modal.hide());
        const picker = root.querySelector('[data-local-sectionicons-picker]');
        const search = picker.querySelector('[data-local-sectionicons-search]');
        const choices = picker.querySelector('[data-local-sectionicons-choices]');
        const upload = picker.querySelector('[data-local-sectionicons-upload]');
        const size = picker.querySelector('[data-local-sectionicons-size]');
        const sizeField = picker.querySelector('[data-local-sectionicons-size-field]');
        const current = this.assignments.get(String(sectionId));
        let currentValue = current?.value || '';
        let searchTimer = null;

        search.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => {
                this.renderChoices(choices, search.value, currentValue);
            }, 80);
        });
        choices.addEventListener('click', async event => {
            const button = event.target.closest('[data-icon-value]');
            if (!button || button.disabled) {
                return;
            }
            this.setBusy(picker, true);
            try {
                const response = await Ajax.call([{
                    methodname: 'local_sectionicons_set_icon',
                    args: {
                        courseid: this.config.courseid,
                        sectionid: sectionId,
                        icon: button.dataset.iconValue,
                        size: size.value,
                    },
                }])[0];
                const assignment = this.acceptAssignment(sectionId, response.assignmentjson);
                currentValue = assignment.value || '';
                if (assignment.kind === 'none') {
                    modal.hide();
                    addToast(this.config.strings.removed, {type: 'success'});
                } else {
                    size.value = assignment.size || 'normal';
                    sizeField.hidden = false;
                    this.renderChoices(choices, search.value, currentValue);
                    this.setBusy(picker, false);
                    addToast(this.config.strings.saved, {type: 'success'});
                }
            } catch (error) {
                this.setBusy(picker, false);
                Notification.exception(error);
            }
        });
        upload.addEventListener('change', async() => {
            const file = upload.files?.[0];
            if (!file) {
                return;
            }
            this.setBusy(picker, true, this.config.strings.uploading);
            try {
                const response = await Ajax.call([{
                    methodname: 'local_sectionicons_upload_image',
                    args: {
                        courseid: this.config.courseid,
                        sectionid: sectionId,
                        filename: file.name,
                        content: await fileBase64(file),
                        size: size.value,
                    },
                }])[0];
                const assignment = this.acceptAssignment(sectionId, response.assignmentjson);
                currentValue = assignment.value || '';
                size.value = assignment.size || 'normal';
                sizeField.hidden = false;
                this.setBusy(picker, false);
                addToast(this.config.strings.saved, {type: 'success'});
            } catch (error) {
                upload.value = '';
                this.setBusy(picker, false);
                Notification.exception(error);
            }
        });
        size.addEventListener('change', async() => {
            if (!this.assignments.has(String(sectionId))) {
                return;
            }
            this.setBusy(picker, true);
            try {
                const response = await Ajax.call([{
                    methodname: 'local_sectionicons_set_size',
                    args: {courseid: this.config.courseid, sectionid: sectionId, size: size.value},
                }])[0];
                this.acceptAssignment(sectionId, response.assignmentjson);
                this.setBusy(picker, false);
                addToast(this.config.strings.saved, {type: 'success'});
            } catch (error) {
                this.setBusy(picker, false);
                Notification.exception(error);
            }
        });
        modal.show();
        window.requestAnimationFrame(() => search.focus());
    }

    pickerBody(sectionId) {
        const body = element('div', 'local-sectionicons-picker');
        body.dataset.localSectioniconsPicker = '1';
        const search = element('input', 'form-control mb-2');
        search.type = 'search';
        search.placeholder = this.config.strings.search;
        search.setAttribute('aria-label', this.config.strings.search);
        search.dataset.localSectioniconsSearch = '1';
        const current = this.assignments.get(String(sectionId));
        const choices = element('div', 'local-sectionicons-choices');
        choices.dataset.localSectioniconsChoices = '1';
        choices.setAttribute('role', 'radiogroup');
        this.renderChoices(choices, '', current?.value || '');
        const more = element('div', 'small text-muted local-sectionicons-more', this.config.strings.moreicons);
        const sizeWrap = element('div', 'local-sectionicons-size-field');
        sizeWrap.dataset.localSectioniconsSizeField = '1';
        sizeWrap.hidden = !current;
        const sizeLabel = element('label', 'form-label mb-0', this.config.strings.size);
        const size = element('select', 'form-select custom-select form-select-sm');
        size.id = `local-sectionicons-size-${sectionId}`;
        sizeLabel.htmlFor = size.id;
        size.dataset.localSectioniconsSize = '1';
        [['small', this.config.strings.sizesmall], ['normal', this.config.strings.sizenormal],
            ['large', this.config.strings.sizelarge], ['max', this.config.strings.sizemax]].forEach(([value, label]) => {
            const option = element('option', '', label);
            option.value = value;
            option.selected = (current?.size || 'normal') === value;
            option.toggleAttribute('selected', option.selected);
            size.append(option);
        });
        sizeWrap.append(sizeLabel, size);
        const uploadWrap = element('div', 'local-sectionicons-upload-wrap mt-3 pt-3 border-top');
        const uploadLabel = element('label', 'btn btn-outline-primary mb-1', this.config.strings.upload);
        const upload = element('input', 'sr-only visually-hidden');
        upload.type = 'file';
        upload.accept = '.svg,.png,.webp,image/svg+xml,image/png,image/webp';
        upload.dataset.localSectioniconsUpload = '1';
        uploadLabel.append(upload);
        uploadWrap.append(uploadLabel, element('div', 'small text-muted', this.config.strings.uploadhelp));
        const status = element('div', 'small text-muted mt-2');
        status.dataset.localSectioniconsStatus = '1';
        uploadWrap.append(status);
        body.append(search, choices, more, sizeWrap, uploadWrap);
        return body;
    }

    renderChoices(choices, search, currentValue) {
        const query = search.trim().toLocaleLowerCase();
        const matching = (this.config.icons || []).filter(option => query === ''
            || `${option.label} ${option.group || ''}`.toLocaleLowerCase().includes(query));
        const visible = matching.slice(0, ICON_RENDER_LIMIT);
        const selected = matching.find(option => option.value === currentValue);
        if (selected && !visible.includes(selected)) {
            visible[visible.length === ICON_RENDER_LIMIT ? ICON_RENDER_LIMIT - 1 : visible.length] = selected;
        }
        const fragment = document.createDocumentFragment();
        visible.forEach(option => {
            const button = element('button', 'btn local-sectionicons-choice');
            button.type = 'button';
            button.dataset.iconValue = option.value;
            button.setAttribute('role', 'radio');
            button.setAttribute('aria-checked', currentValue === option.value ? 'true' : 'false');
            button.title = option.label;
            const icon = element('i', `${option.class} local-sectionicons-choice-icon`);
            icon.setAttribute('aria-hidden', 'true');
            button.setAttribute('aria-label', option.label);
            button.append(icon);
            fragment.append(button);
        });
        choices.replaceChildren(fragment);
    }

    setBusy(root, busy, status = '') {
        root.querySelectorAll('button, input, select').forEach(control => {
            control.disabled = busy;
        });
        const statusNode = root.querySelector('[data-local-sectionicons-status]');
        if (statusNode) {
            statusNode.textContent = status;
        }
    }

    acceptAssignment(sectionId, json) {
        const assignment = JSON.parse(json);
        if (assignment.kind === 'none') {
            this.assignments.delete(String(sectionId));
        } else {
            this.assignments.set(String(sectionId), assignment);
        }
        this.applyCourseIndex();
        this.applyCourseContent();
        this.updateManagerPreviews();
        return assignment;
    }
}

export const init = config => {
    if (instance) {
        instance.merge(config);
    } else {
        instance = new SectionIcons(config);
    }
};
