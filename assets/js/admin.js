(function () {
    'use strict';

    var settings = window.RejoyanCRMAdmin || {};

    function confirmSubmit(selector, message) {
        document.addEventListener('submit', function (event) {
            if (!event.target.querySelector(selector)) {
                return;
            }
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    confirmSubmit('.rejoyan-crm-confirm-send', settings.confirmSend || 'Queue this campaign?');
    confirmSubmit('.rejoyan-crm-confirm-offer-send', settings.confirmOfferSend || 'Queue this offer?');
    confirmSubmit('.rejoyan-crm-confirm-cancel', settings.confirmCancel || 'Cancel this campaign?');
    confirmSubmit('.rejoyan-crm-confirm-invoice', settings.confirmInvoice || 'Send invoice email?');

    function flashCopied(button) {
        var original = button.textContent;
        button.textContent = settings.copied || 'Copied';
        window.setTimeout(function () { button.textContent = original; }, 1200);
    }

    function copyText(value, button) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                flashCopied(button);
            }).catch(function () {
                window.prompt('Copy this text:', value);
            });
            return;
        }
        window.prompt('Copy this text:', value);
    }

    document.addEventListener('click', function (event) {
        var copyButton = event.target.closest('.rejoyan-crm-copy');
        if (copyButton) {
            copyText(copyButton.getAttribute('data-copy') || '', copyButton);
            return;
        }

        var captionButton = event.target.closest('.rejoyan-crm-copy-caption');
        if (captionButton) {
            var card = captionButton.closest('.rejoyan-crm-social-product');
            var caption = card ? card.querySelector('.rejoyan-crm-social-caption') : null;
            copyText(caption ? caption.value : '', captionButton);
            return;
        }

        var shareButton = event.target.closest('.rejoyan-crm-social-share');
        if (shareButton) {
            var shareCard = shareButton.closest('.rejoyan-crm-social-product');
            var shareCaption = shareCard ? shareCard.querySelector('.rejoyan-crm-social-caption') : null;
            var text = shareCaption ? shareCaption.value : '';
            var url = shareButton.getAttribute('data-url') || '';
            var platform = shareButton.getAttribute('data-platform') || '';
            var shareUrl = '';

            if (platform === 'native') {
                if (navigator.share) {
                    navigator.share({ title: shareCard ? (shareCard.querySelector('h3') || {}).textContent || '' : '', text: text.replace(url, '').trim(), url: url }).catch(function () {});
                } else {
                    copyText(text || url, shareButton);
                }
                return;
            }

            if (platform === 'facebook') {
                shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
            } else if (platform === 'x') {
                shareUrl = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text.replace(url, '').trim()) + '&url=' + encodeURIComponent(url);
            } else if (platform === 'linkedin') {
                shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url);
            } else if (platform === 'whatsapp') {
                shareUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(text);
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'noopener,noreferrer,width=760,height=720');
            }
        }
    });

    var presetData = document.getElementById('rejoyan-crm-offer-template-data');
    var offerBuilderForm = document.getElementById('rejoyan-crm-offer-builder-form');
    var offerTemplates = {};

    if (presetData) {
        try {
            offerTemplates = JSON.parse(presetData.getAttribute('data-templates') || '{}');
        } catch (error) {
            offerTemplates = {};
        }
    }

    function setOfferField(name, value) {
        if (!offerBuilderForm) {
            return;
        }
        var field = offerBuilderForm.querySelector('[name="' + name + '"]');
        if (!field) {
            return;
        }
        field.value = value || '';
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.addEventListener('click', function (event) {
        var templateButton = event.target.closest('.rejoyan-crm-apply-offer-template');
        if (!templateButton || !offerBuilderForm) {
            return;
        }
        var key = templateButton.getAttribute('data-template-key') || '';
        var preset = offerTemplates[key];
        if (!preset) {
            return;
        }

        setOfferField('title', preset.name || '');
        setOfferField('subject', preset.subject || '');
        setOfferField('badge', preset.badge || '');
        setOfferField('headline', preset.headline || '');
        setOfferField('description', preset.body || '');
        setOfferField('preheader', preset.preheader || '');
        setOfferField('cta_text', preset.cta_text || '');
        setOfferField('expiry_text', preset.expiry_text || '');
        setOfferField('template_style', preset.template_style || 'classic');
        setOfferField('accent_color', preset.accent_color || '#5b4cf0');

        document.querySelectorAll('.rejoyan-crm-template-card.is-selected').forEach(function (card) {
            card.classList.remove('is-selected');
        });
        var card = templateButton.closest('.rejoyan-crm-template-card');
        if (card) {
            card.classList.add('is-selected');
        }
        var original = templateButton.textContent;
        templateButton.textContent = 'Applied';
        window.setTimeout(function () { templateButton.textContent = original; }, 1200);
        offerBuilderForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    var offerSelect = document.getElementById('rejoyan-crm-offer-select');
    var offerPreview = document.getElementById('rejoyan-crm-offer-preview');

    function setPreview(key, value) {
        if (!offerPreview) {
            return;
        }
        var target = offerPreview.querySelector('[data-preview="' + key + '"]');
        if (!target) {
            return;
        }
        target.textContent = value || '';
        target.style.display = value ? '' : 'none';
    }

    function refreshOfferPreview() {
        if (!offerSelect || !offerSelect.options.length) {
            return;
        }
        var option = offerSelect.options[offerSelect.selectedIndex];
        setPreview('subject', option.getAttribute('data-subject'));
        setPreview('badge', option.getAttribute('data-badge'));
        setPreview('headline', option.getAttribute('data-headline'));
        setPreview('description', option.getAttribute('data-description'));
        var productName = option.getAttribute('data-product') || '';
        var productPrice = option.getAttribute('data-product-price') || '';
        setPreview('product', productName ? productName + (productPrice ? ' — ' + productPrice : '') : '');
        var coupon = option.getAttribute('data-coupon') || '';
        setPreview('coupon', coupon ? 'Coupon: ' + coupon : '');
        setPreview('cta', option.getAttribute('data-cta'));
        setPreview('expiry', option.getAttribute('data-expiry'));
        var accent = option.getAttribute('data-accent') || '#5b4cf0';
        var template = option.getAttribute('data-template') || 'classic';
        offerPreview.setAttribute('data-template', template);
        offerPreview.style.setProperty('--mf-offer-accent', accent);
        var previewTop = offerPreview.querySelector('.rejoyan-crm-mini-email-top');
        var previewCta = offerPreview.querySelector('.rejoyan-crm-mini-cta');
        var previewBadge = offerPreview.querySelector('.rejoyan-crm-mini-badge');
        if (previewTop) {
            previewTop.style.background = template === 'minimal' ? '#ffffff' : (template === 'spotlight' ? accent : '#111827');
            previewTop.style.color = template === 'minimal' ? '#111827' : '#ffffff';
        }
        if (previewCta) {
            previewCta.style.background = accent;
        }
        if (previewBadge) {
            previewBadge.style.color = accent;
        }
    }

    if (offerSelect) {
        offerSelect.addEventListener('change', refreshOfferPreview);
        refreshOfferPreview();
    }

    var master = document.getElementById('rejoyan-crm-select-all-customers');
    var allMode = document.getElementById('rejoyan-crm-select-all-mode');
    var idsCsv = document.getElementById('rejoyan-crm-customer-ids-csv');
    var selectedCount = document.getElementById('rejoyan-crm-selected-count');
    var search = document.getElementById('rejoyan-crm-customer-search');
    var sendForm = document.getElementById('rejoyan-crm-offer-send-form');
    var segmentSelect = document.getElementById('rejoyan-crm-audience-segment');

    function customerCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('.rejoyan-crm-customer-checkbox:not(:disabled)'));
    }

    function updateSelectedCount() {
        var boxes = customerCheckboxes();
        var checked = boxes.filter(function (box) { return box.checked; });
        if (selectedCount) {
            selectedCount.textContent = checked.length + ' selected';
        }
        if (master) {
            master.checked = boxes.length > 0 && checked.length === boxes.length;
            master.indeterminate = checked.length > 0 && checked.length < boxes.length;
        }
        if (allMode) {
            allMode.value = master && master.checked ? '1' : '0';
        }
    }

    if (master) {
        master.addEventListener('change', function () {
            customerCheckboxes().forEach(function (box) {
                box.checked = master.checked;
            });
            if (allMode) {
                allMode.value = master.checked ? '1' : '0';
            }
            updateSelectedCount();
        });

        customerCheckboxes().forEach(function (box) {
            box.addEventListener('change', updateSelectedCount);
        });
        updateSelectedCount();
    }

    if (search) {
        search.addEventListener('input', function () {
            var needle = search.value.toLowerCase().trim();
            document.querySelectorAll('.rejoyan-crm-customer-row').forEach(function (row) {
                row.style.display = !needle || (row.getAttribute('data-search') || '').indexOf(needle) !== -1 ? '' : 'none';
            });
        });
    }

    if (sendForm) {
        sendForm.addEventListener('submit', function (event) {
            var boxes = customerCheckboxes();
            var anyChecked = boxes.some(function (box) { return box.checked; });
            var hasSegment = segmentSelect && segmentSelect.value;
            if (!hasSegment && !anyChecked && (!allMode || allMode.value !== '1')) {
                event.preventDefault();
                window.alert(settings.selectCustomers || 'Select at least one customer.');
                return;
            }

            if (hasSegment) {
                if (allMode) { allMode.value = '0'; }
                if (idsCsv) { idsCsv.value = ''; }
            }

            // When every customer is selected, the server can snapshot the full audience itself.
            // Disable individual checkboxes so large stores do not hit PHP max_input_vars.
            if (idsCsv && !hasSegment) {
                idsCsv.value = boxes.filter(function (box) { return box.checked; }).map(function (box) { return box.value; }).join(',');
            }

            // IDs are carried in one compact field so a large selection does not hit PHP max_input_vars.
            boxes.forEach(function (box) { box.disabled = true; });
        });
    }


    var headerMenuToggle = document.querySelector('.rejoyan-crm-menu-toggle');
    var headerNav = document.getElementById('rejoyan-crm-header-nav');
    if (headerMenuToggle && headerNav) {
        headerMenuToggle.addEventListener('click', function () {
            var open = headerNav.classList.toggle('is-open');
            headerMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        headerNav.addEventListener('click', function (event) {
            if (window.matchMedia && window.matchMedia('(max-width: 782px)').matches && event.target.closest('a')) {
                headerNav.classList.remove('is-open');
                headerMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function initDeferredCustomerCenter(root) {
        root = root || document;
        var localOfferSelect = root.querySelector('#rejoyan-crm-offer-select') || document.getElementById('rejoyan-crm-offer-select');
        var localOfferPreview = root.querySelector('#rejoyan-crm-offer-preview') || document.getElementById('rejoyan-crm-offer-preview');

        function localSetPreview(key, value) {
            if (!localOfferPreview) { return; }
            var target = localOfferPreview.querySelector('[data-preview="' + key + '"]');
            if (!target) { return; }
            target.textContent = value || '';
            target.style.display = value ? '' : 'none';
        }

        function localRefreshPreview() {
            if (!localOfferSelect || !localOfferSelect.options.length || !localOfferPreview) { return; }
            var option = localOfferSelect.options[localOfferSelect.selectedIndex];
            localSetPreview('subject', option.getAttribute('data-subject'));
            localSetPreview('badge', option.getAttribute('data-badge'));
            localSetPreview('headline', option.getAttribute('data-headline'));
            localSetPreview('description', option.getAttribute('data-description'));
            var productName = option.getAttribute('data-product') || '';
            var productPrice = option.getAttribute('data-product-price') || '';
            localSetPreview('product', productName ? productName + (productPrice ? ' — ' + productPrice : '') : '');
            var coupon = option.getAttribute('data-coupon') || '';
            localSetPreview('coupon', coupon ? 'Coupon: ' + coupon : '');
            localSetPreview('cta', option.getAttribute('data-cta'));
            localSetPreview('expiry', option.getAttribute('data-expiry'));
            var accent = option.getAttribute('data-accent') || '#5b4cf0';
            var template = option.getAttribute('data-template') || 'classic';
            localOfferPreview.setAttribute('data-template', template);
            localOfferPreview.style.setProperty('--mf-offer-accent', accent);
            var top = localOfferPreview.querySelector('.rejoyan-crm-mini-email-top');
            var cta = localOfferPreview.querySelector('.rejoyan-crm-mini-cta');
            var badge = localOfferPreview.querySelector('.rejoyan-crm-mini-badge');
            if (top) {
                top.style.background = template === 'minimal' ? '#ffffff' : (template === 'spotlight' ? accent : '#111827');
                top.style.color = template === 'minimal' ? '#111827' : '#ffffff';
            }
            if (cta) { cta.style.background = accent; }
            if (badge) { badge.style.color = accent; }
        }

        if (localOfferSelect && !localOfferSelect.dataset.rejoyanCrmBound) {
            localOfferSelect.dataset.rejoyanCrmBound = '1';
            localOfferSelect.addEventListener('change', localRefreshPreview);
            localRefreshPreview();
        }

        var localMaster = root.querySelector('#rejoyan-crm-select-all-customers');
        var localAllMode = root.querySelector('#rejoyan-crm-select-all-mode');
        var localIdsCsv = root.querySelector('#rejoyan-crm-customer-ids-csv');
        var localSelectedCount = root.querySelector('#rejoyan-crm-selected-count');
        var localSearch = root.querySelector('#rejoyan-crm-customer-search');
        var localSendForm = root.querySelector('#rejoyan-crm-offer-send-form');
        var localSegmentSelect = root.querySelector('#rejoyan-crm-audience-segment');

        function localBoxes() {
            return Array.prototype.slice.call(document.querySelectorAll('.rejoyan-crm-customer-checkbox:not(:disabled)'));
        }

        function localUpdateCount() {
            var boxes = localBoxes();
            var checked = boxes.filter(function (box) { return box.checked; });
            if (localSelectedCount) { localSelectedCount.textContent = checked.length + ' selected'; }
            if (localMaster) {
                localMaster.checked = boxes.length > 0 && checked.length === boxes.length;
                localMaster.indeterminate = checked.length > 0 && checked.length < boxes.length;
            }
            if (localAllMode) { localAllMode.value = localMaster && localMaster.checked ? '1' : '0'; }
        }

        if (localMaster && !localMaster.dataset.rejoyanCrmBound) {
            localMaster.dataset.rejoyanCrmBound = '1';
            localMaster.addEventListener('change', function () {
                localBoxes().forEach(function (box) { box.checked = localMaster.checked; });
                if (localAllMode) { localAllMode.value = localMaster.checked ? '1' : '0'; }
                localUpdateCount();
            });
            localBoxes().forEach(function (box) {
                if (!box.dataset.rejoyanCrmBound) {
                    box.dataset.rejoyanCrmBound = '1';
                    box.addEventListener('change', localUpdateCount);
                }
            });
            localUpdateCount();
        }

        if (localSearch && !localSearch.dataset.rejoyanCrmBound) {
            localSearch.dataset.rejoyanCrmBound = '1';
            localSearch.addEventListener('input', function () {
                var needle = localSearch.value.toLowerCase().trim();
                document.querySelectorAll('.rejoyan-crm-customer-row').forEach(function (row) {
                    row.style.display = !needle || (row.getAttribute('data-search') || '').indexOf(needle) !== -1 ? '' : 'none';
                });
            });
        }

        if (localSendForm && !localSendForm.dataset.rejoyanCrmBound) {
            localSendForm.dataset.rejoyanCrmBound = '1';
            localSendForm.addEventListener('submit', function (event) {
                var boxes = localBoxes();
                var anyChecked = boxes.some(function (box) { return box.checked; });
                var hasSegment = localSegmentSelect && localSegmentSelect.value;
                if (!hasSegment && !anyChecked && (!localAllMode || localAllMode.value !== '1')) {
                    event.preventDefault();
                    window.alert(settings.selectCustomers || 'Select at least one customer.');
                    return;
                }
                if (hasSegment) {
                    if (localAllMode) { localAllMode.value = '0'; }
                    if (localIdsCsv) { localIdsCsv.value = ''; }
                } else if (localIdsCsv) {
                    localIdsCsv.value = boxes.filter(function (box) { return box.checked; }).map(function (box) { return box.value; }).join(',');
                }
                boxes.forEach(function (box) { box.disabled = true; });
            });
        }
    }

    function loadLazyPanel(panel) {
        if (!panel || panel.dataset.rejoyanCrmLoading || panel.dataset.rejoyanCrmLoaded) { return; }
        if (!settings.ajaxUrl || !settings.lazyNonce) { return; }
        panel.dataset.rejoyanCrmLoading = '1';

        var body = new URLSearchParams();
        body.set('action', 'rejoyan_crm_lazy_panel');
        body.set('nonce', settings.lazyNonce);
        body.set('panel', panel.getAttribute('data-rejoyan-crm-lazy') || '');
        try {
            var params = new URLSearchParams(window.location.search);
            if (params.get('offer_id')) { body.set('offer_id', params.get('offer_id')); }
        } catch (error) {}

        window.fetch(settings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (response) {
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            return response.json();
        }).then(function (payload) {
            if (!payload || !payload.success || !payload.data || typeof payload.data.html !== 'string') {
                throw new Error('Invalid response');
            }
            panel.innerHTML = payload.data.html;
            panel.dataset.rejoyanCrmLoaded = '1';
            delete panel.dataset.rejoyanCrmLoading;
            panel.classList.add('is-loaded');
            initDeferredCustomerCenter(panel);
            document.dispatchEvent(new CustomEvent('rejoyan-crm:content-loaded', { detail: { root: panel } }));
        }).catch(function () {
            delete panel.dataset.rejoyanCrmLoading;
            panel.classList.add('has-error');
            panel.innerHTML = '<section class="rejoyan-crm-card rejoyan-crm-lazy-error"><strong>' + (settings.lazyError || 'This section could not be loaded.') + '</strong><button type="button" class="button rejoyan-crm-lazy-retry">Retry</button></section>';
        });
    }

    function initLazyPanels(root) {
        var panels = Array.prototype.slice.call((root || document).querySelectorAll('[data-rejoyan-crm-lazy]'));
        if (!panels.length) { return; }
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        observer.unobserve(entry.target);
                        loadLazyPanel(entry.target);
                    }
                });
            }, { rootMargin: '220px 0px' });
            panels.forEach(function (panel) { observer.observe(panel); });
        } else {
            panels.forEach(function (panel) { window.setTimeout(function () { loadLazyPanel(panel); }, 0); });
        }
    }

    document.addEventListener('click', function (event) {
        var retry = event.target.closest('.rejoyan-crm-lazy-retry');
        if (!retry) { return; }
        var panel = retry.closest('[data-rejoyan-crm-lazy]');
        if (!panel) { return; }
        panel.classList.remove('has-error');
        panel.innerHTML = '<div class="rejoyan-crm-skeleton-grid" aria-hidden="true"><span class="rejoyan-crm-skeleton-card"></span><span class="rejoyan-crm-skeleton-card"></span><span class="rejoyan-crm-skeleton-card"></span></div>';
        loadLazyPanel(panel);
    });



    function initSocialBrowser(root) {
        var browsers = Array.prototype.slice.call((root || document).querySelectorAll('.rejoyan-crm-social-browser'));
        browsers.forEach(function (browser) {
            if (browser.dataset.rejoyanCrmSocialReady === '1') { return; }
            browser.dataset.rejoyanCrmSocialReady = '1';

            var grid = browser.querySelector('[data-rejoyan-crm-social-grid]');
            var search = browser.querySelector('[data-rejoyan-crm-social-search]');
            var category = browser.querySelector('[data-rejoyan-crm-social-category]');
            var filter = browser.querySelector('[data-rejoyan-crm-social-filter]');
            var loadMore = browser.querySelector('[data-rejoyan-crm-social-load-more]');
            var spinner = browser.querySelector('[data-rejoyan-crm-social-spinner]');
            var total = browser.querySelector('[data-rejoyan-crm-product-total]');
            var timer = null;
            var requestId = 0;

            if (!navigator.share) {
                Array.prototype.forEach.call(browser.querySelectorAll('.rejoyan-crm-share-native'), function (button) {
                    button.classList.add('is-fallback');
                });
            }

            function setBusy(isBusy) {
                browser.classList.toggle('is-loading-products', isBusy);
                if (spinner) { spinner.classList.toggle('is-active', isBusy); }
                if (loadMore) { loadMore.disabled = isBusy; }
                if (search) { search.disabled = isBusy && !grid.children.length; }
                if (category) { category.disabled = isBusy && !grid.children.length; }
                if (filter) { filter.disabled = isBusy && !grid.children.length; }
            }

            function fetchProducts(page, append) {
                if (!settings.ajaxUrl || !settings.lazyNonce || !grid) { return; }
                var currentRequest = ++requestId;
                setBusy(true);

                var body = new URLSearchParams();
                body.set('action', 'rejoyan_crm_social_products');
                body.set('nonce', settings.lazyNonce);
                body.set('page', String(page));
                body.set('search', search ? search.value.trim() : '');
                body.set('category', category ? category.value : '');
                body.set('filter', filter ? filter.value : 'all');

                window.fetch(settings.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                }).then(function (response) {
                    if (!response.ok) { throw new Error('HTTP ' + response.status); }
                    return response.json();
                }).then(function (payload) {
                    if (currentRequest !== requestId) { return; }
                    if (!payload || !payload.success || !payload.data || typeof payload.data.html !== 'string') {
                        throw new Error('Invalid response');
                    }
                    if (append) {
                        grid.insertAdjacentHTML('beforeend', payload.data.html);
                    } else {
                        grid.innerHTML = payload.data.html;
                    }
                    browser.dataset.page = String(payload.data.page || page);
                    if (total && typeof payload.data.total !== 'undefined') {
                        total.textContent = Number(payload.data.total || 0).toLocaleString();
                    }
                    if (loadMore) {
                        loadMore.hidden = !payload.data.has_more;
                    }
                    if (!navigator.share) {
                        Array.prototype.forEach.call(grid.querySelectorAll('.rejoyan-crm-share-native'), function (button) {
                            button.classList.add('is-fallback');
                        });
                    }
                }).catch(function () {
                    if (!append) {
                        grid.innerHTML = '<section class="rejoyan-crm-card rejoyan-crm-social-empty"><strong>' + (settings.lazyError || 'Products could not be loaded.') + '</strong></section>';
                    }
                }).finally(function () {
                    if (currentRequest === requestId) { setBusy(false); }
                });
            }

            function refreshSoon() {
                window.clearTimeout(timer);
                timer = window.setTimeout(function () { fetchProducts(1, false); }, 320);
            }

            if (search) { search.addEventListener('input', refreshSoon); }
            if (category) { category.addEventListener('change', function () { fetchProducts(1, false); }); }
            if (filter) { filter.addEventListener('change', function () { fetchProducts(1, false); }); }
            if (loadMore) {
                loadMore.addEventListener('click', function () {
                    fetchProducts((parseInt(browser.dataset.page || '1', 10) || 1) + 1, true);
                });
            }
        });
    }

    document.addEventListener('rejoyan-crm:content-loaded', function (event) {
        initSocialBrowser(event.detail && event.detail.root ? event.detail.root : document);
    });

    initDeferredCustomerCenter(document);
    initSocialBrowser(document);
    initLazyPanels(document);

}());
