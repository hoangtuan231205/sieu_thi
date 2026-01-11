<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>TEST AUTOCOMPLETE SEARCH</title>
    <!-- Config Base URL -->
    <?php require_once 'config/config.php'; ?>
    <style>
        body { font-family: sans-serif; padding: 50px; }
        .box { position: relative; width: 500px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; }
        input { width: 100%; padding: 10px; font-size: 16px; }
        #suggestions {
            position: absolute; width: 100%; left: 0;
            background: #fff; border: 1px solid #ddd;
            max-height: 300px; overflow-y: auto;
            z-index: 9999; display: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
        .item:hover { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="box">
        <h3>Test Tìm kiếm sản phẩm</h3>
        <p>Gõ "sữa" vào ô bên dưới:</p>
        <div style="position: relative;">
            <input type="text" id="search" placeholder="Tìm sản phẩm..." autocomplete="off">
            <div id="suggestions"></div>
        </div>
        <div id="log" style="margin-top: 20px; font-size: 12px; color: gray;"></div>
    </div>

    <script>
        const baseUrl = '<?= BASE_URL ?>';
        const input = document.getElementById('search');
        const list = document.getElementById('suggestions');
        const log = document.getElementById('log');
        let timeout;

        function addLog(msg) {
            log.innerHTML += '<div>' + new Date().toLocaleTimeString() + ': ' + msg + '</div>';
        }

        input.addEventListener('input', function() {
            const q = this.value.trim();
            addLog('Input: ' + q);
            
            if (q.length < 2) { list.style.display = 'none'; return; }

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const url = baseUrl + '/admin/search-product-for-disposal?q=' + encodeURIComponent(q);
                addLog('Fetching: ' + url);

                fetch(url)
                .then(r => {
                    if(!r.ok) throw new Error(r.statusText);
                    return r.text().then(text => {
                        try { return JSON.parse(text); }
                        catch(e) { throw new Error('Not JSON: ' + text.substring(0, 50) + '...'); }
                    });
                })
                .then(data => {
                    addLog('Result: ' + data.length + ' items');
                    if (data.length === 0) {
                        list.innerHTML = '<div class="item">Không tìm thấy</div>';
                    } else {
                        list.innerHTML = data.map(p => `
                            <div class="item">
                                <strong>${p.Ma_hien_thi}</strong> - ${p.Ten}
                                <br><small>Tồn: ${p.So_luong_ton}</small>
                            </div>
                        `).join('');
                    }
                    list.style.display = 'block';
                })
                .catch(e => {
                    addLog('ERROR: ' + e.message);
                    list.innerHTML = '<div class="item" style="color:red">Lỗi: ' + e.message + '</div>';
                    list.style.display = 'block';
                });
            }, 300);
        });
    </script>
</body>
</html>
