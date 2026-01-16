<h2 class="h5 mb-3">扫码登录</h2>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<div class="mb-3 small text-muted">请使用已登录的小程序，打开“设置 → 扫码登录PC”，扫码本页二维码完成登录。</div>
<div class="d-flex justify-content-center mb-3">
  <canvas id="qrcodeCanvas" class="border rounded" width="220" height="220"></canvas>
</div>
<div class="mb-2 small">二维码有效期至：<?= htmlspecialchars($expiresAt ?? '') ?></div>
<div class="mb-3">
  <label class="form-label small">或手动输入token（用于无法识别二维码的情况）</label>
  <div class="input-group">
    <input type="text" id="qr_token_input" class="form-control" value="<?= htmlspecialchars($token ?? '') ?>" readonly>
    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('qr_token_input').value)">复制</button>
  </div>
</div>
<div class="d-flex justify-content-center align-items-center mb-3 gap-2">
  <span class="small text-muted">将于 <span id="countdown">120</span> 秒后过期</span>
  <button class="btn btn-sm btn-outline-primary" id="btnRefresh" type="button">刷新二维码</button>
  <span class="small text-muted" id="refreshStatus"></span>
</div>
<form id="completeForm" method="post" action="/public/index.php?route=qr-login-complete" class="d-none">
  <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
</form>
<div id="statusBox" class="alert alert-info small">等待确认中…</div>
<script>
(function(){
  function loadScript(src, onload, onerror){
    var s = document.createElement('script');
    s.src = src; s.async = true;
    s.onload = onload; s.onerror = onerror || function(){};
    document.head.appendChild(s);
  }
  var statusBox = document.getElementById('statusBox');
  function init(){
    var payload = <?= json_encode($qrPayload ?? '{}') ?>;
      var el = document.getElementById('qrcodeCanvas');
    if (typeof QRCode === 'undefined') {
      statusBox.className = 'alert alert-danger small';
      statusBox.textContent = '二维码库加载失败，请刷新重试';
      return;
    }
      QRCode.toCanvas(el, payload, { width: 220, margin: 1 }, function (err) {
      if (err) {
        statusBox.textContent = '二维码渲染失败，请刷新重试';
      }
    });
    var token = <?= json_encode($token ?? '') ?>;
    // 倒计时与刷新
    var countdownEl = document.getElementById('countdown');
    var refreshBtn = document.getElementById('btnRefresh');
    var refreshStatus = document.getElementById('refreshStatus');
    var remain = 120;
    function tick(){
      if (remain > 0) { remain--; countdownEl.textContent = remain; }
      else { refreshStatus.textContent = '已过期，请刷新二维码'; }
    }
    setInterval(tick, 1000);
    refreshBtn.addEventListener('click', function(){
      refreshStatus.textContent = '刷新中…';
      window.location.reload();
    });
    function poll(){
      fetch('/public/api.php?route=qr-login/status&token=' + encodeURIComponent(token), {
        headers: { 'Cache-Control': 'no-cache' }
      }).then(function(r){return r.json()}).then(function(d){
        if (!d || !d.success) {
          statusBox.className = 'alert alert-danger small';
          statusBox.textContent = (d && d.error) || '查询失败';
          return;
        }
        if (d.status === 'confirmed') {
          statusBox.className = 'alert alert-success small';
          statusBox.textContent = '已确认，正在登录…';
          document.getElementById('completeForm').submit();
          return;
        }
        if (d.status === 'pending') {
          statusBox.className = 'alert alert-info small';
          statusBox.textContent = '等待确认中…';
          setTimeout(poll, 2000);
        } else {
          statusBox.className = 'alert alert-warning small';
          statusBox.textContent = '二维码已过期或取消，请刷新本页生成新的二维码';
        }
      }).catch(function(){
        setTimeout(poll, 3000);
      });
    }
    poll();
  }
  // 先尝试加载本地文件（vendor），失败再尝试 assets/js，最后回退到 CDN
  loadScript('/assets/vendor/qrcode/qrcode.min.js', init, function(){
    loadScript('/assets/js/qrcode.min.js', init, function(){
      loadScript('https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js', init, function(){
      statusBox.className = 'alert alert-danger small';
      statusBox.textContent = '二维码库加载失败，请检查网络或稍后重试';
      });
    });
  });
})();
</script>
