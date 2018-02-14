/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 TwelveTone LLC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// This must be in a function call for remodal to register it
$(function () {
    $('body').append(ADMIN_ADDON_MEDIA_MOVE.MODAL);
});

function onMediaAction_MediaMove(actionId, mediaName, mediaElement) {
    var modal = $.remodal.lookup[$('[data-remodal-id=modal-admin-media-move]').data('remodal')];
    modal.open();

    var $modal = modal.$modal;
    // Populate fields
    $('[name=file_name]', $modal).val(mediaName);
    $('[name=destination_route]', $modal).val("");
    $('[name=destination_page]', $modal).val("");

    // Reset loading state
    $('.loading', $modal).addClass('hidden');
    $('.button', $modal).removeClass('hidden').css('visibility', 'visible');

    $(document).off('click', '[data-remodal-id=modal-admin-media-move] .button');
    $(document).on('click', '[data-remodal-id=modal-admin-media-move] .button', function (e) {
        var destination_route = $('[name=destination_route]').val();
        if (!destination_route) {
            destination_route = $('[name=destination_page]').val();
        }
        if (destination_route) {
            const payload = {
                destination_route
            };
            if (e.target.name === 'move_and_go') {
                payload.go = true;
            }
            const callback = function (result) {
                if (result.error) {
                    alert(result.error.msg);
                } else {
                    $(mediaElement).remove();
                    if (payload.go) {
                        window.location = result.result.destination_url;
                    }
                }
            };
            submitMediaAction(actionId, mediaName, payload, callback, modal);
        }
    });
}