<?php
namespace Vendor\CompanyAttribute\Plugin\Block\Adminhtml\Catalog\Product\Attribute\Edit\Tab;

use Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Options as OriginalOptions;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Vendor\CompanyAttribute\Model\ResourceModel\OptionImage as OptionImageResource;

class Options
{
    private OptionImageResource   $optionImageResource;
    private StoreManagerInterface $storeManager;
    private UrlInterface          $urlBuilder;

    public function __construct(
        OptionImageResource $optionImageResource,
        StoreManagerInterface $storeManager,
        UrlInterface $urlBuilder
    ) {
        $this->optionImageResource = $optionImageResource;
        $this->storeManager        = $storeManager;
        $this->urlBuilder          = $urlBuilder;
    }

    /**
     * After getHtml: append JS that adds Image column to the options table
     */
    public function afterToHtml(OriginalOptions $subject, string $html): string
    {
        $attribute   = $subject->getAttributeObject();
        $attributeId = $attribute ? (int)$attribute->getId() : 0;
        $images      = $attributeId
            ? $this->optionImageResource->getImagesByAttributeId($attributeId)
            : [];

        $mediaUrl       = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $uploadUrl      = $this->urlBuilder->getUrl('vendor_company/option/uploadImage');
        $saveImagesUrl  = $this->urlBuilder->getUrl('vendor_company/option/saveImages');
        $imagesJson     = json_encode($images, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $mediaUrlJs     = json_encode($mediaUrl);

        $script = <<<JS
<script>
require(['jquery', 'mage/adminhtml/form'], function($) {
    'use strict';

    var existingImages  = {$imagesJson};
    var mediaUrl        = {$mediaUrlJs};
    var uploadUrl       = '{$uploadUrl}';
    var saveImagesUrl   = '{$saveImagesUrl}';
    var pendingImages   = {};   // rowId|optionId => { path, url }
    var deletedImages   = {};   // optionId => true

    // ── Inject column header ──────────────────────────────────────────
    function addImageHeader() {
        var thead = $('#manage-options-panel table thead tr');
        if (thead.length && !thead.find('th.vendor-img-th').length) {
            thead.find('th:last').before(
                '<th class="vendor-img-th" style="width:100px">Logo</th>'
            );
        }
    }

    // ── Add image cell to a row ────────────────────────────────────────
    function addImageCell(row) {
        var \$row   = $(row);
        if (\$row.find('td.vendor-img-td').length) return;

        // Determine option_id from existing row id (option_id_N) or new row
        var rowId    = \$row.attr('id') || '';
        var optionId = rowId.replace('option_id_', '').replace('option_', '');
        var existing = existingImages[parseInt(optionId)] || null;
        var imgHtml  = '';

        if (existing) {
            imgHtml = '<img class="opt-img-preview" src="' + mediaUrl + existing + '" '
                    + 'style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;border-radius:3px;margin-bottom:4px"/><br/>';
        }

        var cell = $('<td class="vendor-img-td" style="vertical-align:middle;padding:4px 8px;min-width:90px">'
            + imgHtml
            + '<input type="file" class="opt-img-upload" accept="image/*" data-row="' + rowId + '" data-option="' + optionId + '" style="font-size:11px;width:80px"/>'
            + '<input type="hidden" class="opt-img-path" value="' + (existing || '') + '"/>'
            + (existing ? '<br/><label style="font-size:10px;cursor:pointer"><input type="checkbox" class="opt-img-delete" data-option="' + optionId + '"/> Del</label>' : '')
            + '</td>');

        \$row.find('td:last').before(cell);
    }

    // ── Upload file via AJAX ──────────────────────────────────────────
    $(document).on('change', '.opt-img-upload', function () {
        var file   = this.files[0];
        if (!file) return;

        var \$input = $(this);
        var rowId  = \$input.data('row');
        var formData = new FormData();
        formData.append('image', file);
        formData.append('form_key', FORM_KEY);

        \$input.after('<span class="opt-uploading" style="font-size:10px;color:#888"> uploading...</span>');

        $.ajax({
            url:         uploadUrl,
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (res) {
                \$input.siblings('.opt-uploading').remove();
                if (res.success) {
                    pendingImages[rowId] = { path: res.path, url: res.url };
                    \$input.closest('td').find('.opt-img-path').val(res.path);

                    // Show preview
                    var prev = \$input.closest('td').find('.opt-img-preview');
                    if (prev.length) {
                        prev.attr('src', res.url);
                    } else {
                        \$input.before('<img class="opt-img-preview" src="' + res.url + '" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;border-radius:3px;display:block;margin-bottom:4px"/>');
                    }
                } else {
                    alert('Upload failed: ' + res.error);
                }
            }
        });
    });

    // ── Delete checkbox ────────────────────────────────────────────────
    $(document).on('change', '.opt-img-delete', function () {
        var optId = $(this).data('option');
        if (this.checked) {
            deletedImages[optId] = true;
            $(this).closest('td').find('.opt-img-preview').css('opacity', 0.3);
        } else {
            delete deletedImages[optId];
            $(this).closest('td').find('.opt-img-preview').css('opacity', 1);
        }
    });

    // ── Watch for new rows added by Magento ──────────────────────────
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType === 1 && node.tagName === 'TR') {
                    addImageCell(node);
                }
            });
        });
        addImageHeader();
    });

    // ── Sync hidden inputs so image paths travel with the form POST ────
    // We add hidden inputs named option_image[<rowId>] and option_image_delete[<optionId>]
    // so the server can read them after Magento has already saved the options.
    function syncHiddenInputs() {
        // Remove previous hidden inputs we injected
        $('#edit_form').find('.vendor-img-hidden').remove();

        $('#manage-options-panel tbody tr').each(function () {
            var rowId = $(this).attr('id') || '';
            var path  = $(this).find('.opt-img-path').val();
            if (path && rowId) {
                $('#edit_form').append(
                    $('<input type="hidden" class="vendor-img-hidden"/>')
                        .attr('name', 'option_image[' + rowId + ']')
                        .val(path)
                );
            }
        });

        $.each(deletedImages, function (optId) {
            $('#edit_form').append(
                $('<input type="hidden" class="vendor-img-hidden"/>')
                    .attr('name', 'option_image_delete[' + optId + ']')
                    .val('1')
            );
        });
    }

    $(document).on('submit', '#edit_form', function () {
        syncHiddenInputs();
    });

    // ── Init ──────────────────────────────────────────────────────────
    function init() {
        addImageHeader();
        $('#manage-options-panel tbody tr').each(function () { addImageCell(this); });

        var tbody = document.querySelector('#manage-options-panel tbody');
        if (tbody) {
            observer.observe(tbody, { childList: true });
        }
    }

    // Wait for Magento to render options table
    var checkReady = setInterval(function () {
        if ($('#manage-options-panel').length) {
            clearInterval(checkReady);
            init();
        }
    }, 300);
});
</script>
JS;

        return $html . $script;
    }
}
