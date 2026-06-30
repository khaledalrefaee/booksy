{{--
    Client-side image compression before upload.
    Usage: include this partial and call initImageCompressor('input-id', 'preview-box-id')

    - Compresses any image to max 800x800 JPEG at 75% quality
    - Typical output: 50KB–200KB regardless of original size
    - Shows compression info to user
    - No external libraries needed
--}}
<script>
function initImageCompressor(inputId, previewBoxId) {
    var MAX_DIM = 800;
    var QUALITY = 0.75;
    var input = document.getElementById(inputId);
    var box   = document.getElementById(previewBoxId);
    if (!input) return;

    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        var originalSize = (file.size / 1024 / 1024).toFixed(2);
        var hint = this.closest('.flex-grow-1') || this.parentElement;
        var infoEl = hint.querySelector('.js-compress-info');
        if (!infoEl) {
            infoEl = document.createElement('div');
            infoEl.className = 'js-compress-info';
            infoEl.style.cssText = 'font-size:11px;margin-top:6px;padding:6px 10px;border-radius:8px;background:rgba(102,126,234,.08);border:1px solid rgba(102,126,234,.15);';
            hint.appendChild(infoEl);
        }
        infoEl.innerHTML = '<span style="color:#667eea;">⏳ {{ __("Compressing image...") }}</span>';

        var reader = new FileReader();
        reader.onload = function (ev) {
            var img = new Image();
            img.onload = function () {
                var w = img.width, h = img.height;
                if (w > MAX_DIM || h > MAX_DIM) {
                    if (w > h) { h = Math.round(h * MAX_DIM / w); w = MAX_DIM; }
                    else       { w = Math.round(w * MAX_DIM / h); h = MAX_DIM; }
                }

                var canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                var ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, w, h);
                ctx.drawImage(img, 0, 0, w, h);

                canvas.toBlob(function (blob) {
                    if (!blob) return;

                    var compressedSize = (blob.size / 1024).toFixed(0);
                    var dt = new DataTransfer();
                    var compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' });
                    dt.items.add(compressedFile);
                    input.files = dt.files;

                    infoEl.innerHTML =
                        '<span style="color:#43e97b;">✓ {{ __("Compressed successfully") }}</span>' +
                        '<span style="opacity:.5;margin-inline-start:8px;">' + originalSize + ' MB → ' + compressedSize + ' KB</span>' +
                        '<span style="opacity:.5;margin-inline-start:8px;">(' + w + '×' + h + ')</span>';

                    if (box) {
                        box.innerHTML = '<img src="' + URL.createObjectURL(blob) + '" style="width:100%;height:100%;object-fit:cover;">';
                    }
                }, 'image/jpeg', QUALITY);
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });
}
</script>
