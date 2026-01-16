<h2 class="h5 mb-3">第二步：绑定小程序（可跳过）</h2>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <h3 class="h6 mb-2">微信小程序一键绑定</h3>
    <div class="text-muted small mb-2">1）先在微信中搜索并打开小程序「三石记账」，或扫描下方小程序码进入小程序；2）在小程序内进入「绑定」页面，选择「扫码绑定」，再扫描绑定二维码。</div>
    <div class="d-flex align-items-start gap-4 flex-wrap mb-3">
      <div>
        <div class="small mb-1">小程序码：</div>
        <img src="/xiaochengxu.png" alt="三石记账小程序码" style="width:160px;height:160px;" loading="lazy">
      </div>
      <div>
        <div class="small mb-1">绑定二维码：</div>
        <div id="bindQr" class="border" style="width:160px;height:160px;"></div>
        <canvas id="bindQrCanvas" class="border d-none" width="160" height="160"></canvas>
        <?php if (!empty($bindToken)): ?>
        <div class="small text-muted mt-2">备用绑定码：<span class="badge bg-secondary"><?= htmlspecialchars($bindToken) ?></span></div>
        <?php else: ?>
        <div class="small text-muted mt-2 text-danger">未获取到绑定码，请返回注册成功页重新进入本步骤。</div>
        <?php endif; ?>
      </div>
    </div>
    <div class="text-muted small mt-1">有效期倒计时：<span id="bindCountdown">--:--</span></div>
    <div class="mt-3 d-flex flex-wrap gap-2">
      <a href="/public/index.php?route=login" class="btn btn-sm btn-outline-secondary">暂不绑定，直接登录</a>
      <a href="/public/index.php?route=login" class="btn btn-sm btn-primary">我已在小程序完成绑定，去登录</a>
    </div>
  </div>
</div>
<script>
(function(){
  function loadScript(src, onload, onerror){var s=document.createElement('script');s.async=true;s.src=src;s.onload=onload;s.onerror=onerror||function(){};document.head.appendChild(s);}  
  var payload = <?= json_encode($bindQrPayload ?? '{}', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  function renderQR(){
    var el=document.getElementById('bindQr'); var cvs=document.getElementById('bindQrCanvas');
    try{
      if (window.QRCode && typeof window.QRCode.toCanvas==='function' && cvs){ el.classList.add('d-none'); cvs.classList.remove('d-none'); window.QRCode.toCanvas(cvs, payload, {width:160, margin:1}, function(err){ if(err)console.error(err);}); return; }
      if (window.QRCode && typeof window.QRCode==='function' && el){ cvs.classList.add('d-none'); el.classList.remove('d-none'); new window.QRCode(el, {text: payload, width:160, height:160}); return; }
    }catch(e){ console.error(e); }
  }
  (function(){
    var c=document.getElementById('bindCountdown'); var exp=<?= json_encode($bindExpiresAt ?? '') ?>; var ts=Date.parse((exp||'').replace(/-/g,'/'))||0; function tick(){ var now=Date.now(); var r=Math.max(0, Math.floor((ts-now)/1000)); var m=Math.floor(r/60), s=r%60; c.textContent=(m<10?'0'+m:m)+':'+(s<10?'0'+s:s);} tick(); setInterval(tick,1000);
  })();
  loadScript('/assets/vendor/qrcode/qrcode.min.js', function(){renderQR();}, function(){
    loadScript('/assets/js/qrcode.min.js', function(){renderQR();}, function(){
      loadScript('https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js', function(){renderQR();});
    });
  });
})();
</script>
