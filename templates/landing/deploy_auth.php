<?php
$pricing = $pricing ?? [];

function render_price(?float $orig, ?float $promo, bool $promoEnabled): string {
    if ($orig === null && $promo === null) {
        return '待定';
    }
    if ($promoEnabled && $promo !== null && $promo > 0 && $promo < ($orig ?? $promo)) {
        return '<span class="text-muted text-decoration-line-through me-1">￥' . rtrim(rtrim(number_format($orig, 2, '.', ''), '0'), '.') . '</span>' .
               '<span class="fw-bold text-danger">￥' . rtrim(rtrim(number_format($promo, 2, '.', ''), '0'), '.') . '</span>';
    }
    $price = $promo ?? $orig ?? 0;
    return '<span class="fw-bold">￥' . rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.') . '</span>';
}

$promoEnabled = isset($pricing['is_promo_active']) ? (bool)$pricing['is_promo_active'] : false;

$success = $success ?? false;
$errorMessage = $errorMessage ?? '';
$paymentQrs = $paymentQrs ?? ['wechat' => '', 'alipay' => '', 'qq' => ''];
$messageSuccess = $messageSuccess ?? false;
$messageError = $messageError ?? '';
$messages = $messages ?? [];
?>
<div class="row">
    <div class="col-lg-7 mb-4">
        <h1 class="h3 fw-bold mb-3">部署授权说明</h1>
        <p class="text-muted">按照下面步骤部署，即可在自己的服务器上运行本系统，数据完全掌控在自己手中。</p>

        <h2 class="h5 mt-4 mb-2">一、部署步骤</h2>
        <ol class="ps-3">
            <li class="mb-2">准备好运行环境：PHP + MySQL / MariaDB + Web 服务器（Nginx / Apache 等）。</li>
            <li class="mb-2">在下方「源码下载」区域下载最新部署包，并上传到你的服务器。</li>
            <li class="mb-2">解压部署包，导入数据库文件，并根据实际环境修改配置文件。</li>
            <li class="mb-2">使用浏览器访问部署后的地址，按页面提示完成初始化安装。</li>
            <li class="mb-2">在系统后台「系统参数」中填写授权码，保持授权在线即可正常使用。</li>
        </ol>

        <h2 class="h5 mt-4 mb-2">二、授权价格</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">按月授权</h5>
                        <p class="card-text small text-muted">短期试用或临时项目，适合先体验。</p>
                        <p class="card-text mb-1">首次授权：<br><?php
                            echo render_price(
                                isset($pricing['first_month_price']) ? (float)$pricing['first_month_price'] : null,
                                isset($pricing['first_month_price_promo']) ? (float)$pricing['first_month_price_promo'] : null,
                                $promoEnabled
                            );
                        ?></p>
                        <p class="card-text small text-muted mb-1">免费更换域名次数：0 次</p>
                        <p class="card-text mb-0">更换授权：<br><?php
                            echo render_price(
                                isset($pricing['change_price']) ? (float)$pricing['change_price'] : null,
                                isset($pricing['change_price_promo']) ? (float)$pricing['change_price_promo'] : null,
                                $promoEnabled
                            );
                        ?> / 次</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h5 class="card-title">按年授权</h5>
                        <p class="card-text small text-muted">性价比更高，适合长期使用。</p>
                        <p class="card-text mb-1">首次授权：<br><?php
                            echo render_price(
                                isset($pricing['first_year_price']) ? (float)$pricing['first_year_price'] : null,
                                isset($pricing['first_year_price_promo']) ? (float)$pricing['first_year_price_promo'] : null,
                                $promoEnabled
                            );
                        ?></p>
                        <p class="card-text small text-muted mb-1">免费更换域名次数：1 次</p>
                        <p class="card-text mb-0">更换授权：<br><?php
                            echo render_price(
                                isset($pricing['change_price']) ? (float)$pricing['change_price'] : null,
                                isset($pricing['change_price_promo']) ? (float)$pricing['change_price_promo'] : null,
                                $promoEnabled
                            );
                        ?> / 次</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <h5 class="card-title">永久授权</h5>
                        <p class="card-text small text-muted">一次授权，长期使用，适合正式环境。</p>
                        <p class="card-text mb-1">首次授权：<br><?php
                            echo render_price(
                                isset($pricing['first_lifetime_price']) ? (float)$pricing['first_lifetime_price'] : null,
                                isset($pricing['first_lifetime_price_promo']) ? (float)$pricing['first_lifetime_price_promo'] : null,
                                $promoEnabled
                            );
                        ?></p>
                        <p class="card-text small text-muted mb-1">免费更换域名次数：3 次</p>
                        <p class="card-text mb-0">更换授权：<br><?php
                            echo render_price(
                                isset($pricing['change_price']) ? (float)$pricing['change_price'] : null,
                                isset($pricing['change_price_promo']) ? (float)$pricing['change_price_promo'] : null,
                                $promoEnabled
                            );
                        ?> / 次</p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h5 mt-4 mb-2">三、源码下载</h2>
        <p class="text-muted mb-2">当前版本部署包可在此下载，下载后请妥善保管，不要随意对外传播。</p>
        <div class="card mb-3">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <div>部署包名称：<span class="fw-bold"><?php echo htmlspecialchars($systemSetting['license_source_name'] ?? 'ssjizhang_source.zip', ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="small text-muted mt-1">下载链接为一次性授权使用，请勿公开分享。</div>
                </div>
                <div class="mt-3 mt-md-0">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#downloadModal">
                        下载部署包
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">我要授权</h2>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#licenseQueryModal">授权查询</button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">提交成功，我们会尽快通过邮箱与您联系，请注意查收邮件。</div>
                <?php elseif ($errorMessage !== ''): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="/public/index.php?route=license-request-submit" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="email" class="form-label">联系邮箱</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="用于接收授权码及后续服务通知">
                    </div>
                    <div class="mb-3" id="domain_first_group">
                        <label for="domain" class="form-label">授权域名</label>
                        <input type="text" class="form-control" id="domain" name="domain" required placeholder="例如：example.com 或 finance.example.com">
                        <div class="form-text">支持一级或二级域名，授权后默认绑定此域名，可按规则更换。</div>
                    </div>
                    <div class="mb-3 d-none" id="domain_change_group">
                        <label class="form-label">更换授权域名</label>
                        <div class="row g-2">
                            <div class="col-12 col-md-6" id="old_domain_wrapper">
                                <input type="text" class="form-control" id="old_domain" name="old_domain" placeholder="原授权域名，例如：old.example.com">
                            </div>
                            <div class="col-12 col-md-6 d-none" id="new_domain_wrapper">
                                <input type="text" class="form-control" id="new_domain" name="new_domain" placeholder="新授权域名，例如：new.example.com">
                            </div>
                        </div>
                        <div class="form-text" id="change_quota_hint">更换授权时，请先填写联系邮箱和原授权域名，我们会自动检测是否在免费更换额度内。</div>
                    </div>
                    <div class="mb-3" id="pay-method-group">
                        <label class="form-label">申请类型</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input class="btn-check" type="radio" name="request_type" id="request_type_first" value="first" autocomplete="off">
                            <label class="btn btn-outline-secondary flex-fill text-start" for="request_type_first">
                                <div class="fw-semibold">首次授权</div>
                                <div class="small text-muted">首次购买部署授权时选择此项。</div>
                            </label>

                            <input class="btn-check" type="radio" name="request_type" id="request_type_change" value="change" autocomplete="off">
                            <label class="btn btn-outline-secondary flex-fill text-start" for="request_type_change">
                                <div class="fw-semibold">更换授权域名</div>
                                <div class="small text-muted">已购买过首次授权，需要更换绑定域名时选择此项。</div>
                            </label>
                        </div>
                        <div class="form-text small">请根据实际情况选择是首次授权还是更换授权。</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">支付方式</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input class="btn-check" type="radio" name="pay_method" id="pay_method_wechat" value="wechat" autocomplete="off">
                            <label class="btn btn-outline-success flex-fill text-start" for="pay_method_wechat">
                                <div class="fw-semibold">微信支付</div>
                                <div class="small text-muted">推荐使用微信扫码付款。</div>
                            </label>

                            <input class="btn-check" type="radio" name="pay_method" id="pay_method_alipay" value="alipay" autocomplete="off">
                            <label class="btn btn-outline-primary flex-fill text-start" for="pay_method_alipay">
                                <div class="fw-semibold">支付宝</div>
                                <div class="small text-muted">使用支付宝扫一扫完成付款。</div>
                            </label>

                            <input class="btn-check" type="radio" name="pay_method" id="pay_method_qq" value="qq" autocomplete="off">
                            <label class="btn btn-outline-info flex-fill text-start" for="pay_method_qq">
                                <div class="fw-semibold">QQ 支付</div>
                                <div class="small text-muted">使用 QQ 扫码完成付款。</div>
                            </label>
                        </div>
                        <div class="form-text small">请选择预计付款方式，我们会按选择的方式核对收款记录。</div>
                    </div>
                    <div class="mb-3" id="pay-qr-group">
                        <label class="form-label">收款码</label>
                        <div id="payQrContainer" class="border rounded bg-light p-3 text-center small">
                            <div id="payQrPlaceholder" class="text-muted">请选择上方支付方式后，将显示对应收款码。</div>
                            <div class="pay-qr-item d-none" data-method="wechat">
                                <?php if (!empty($paymentQrs['wechat'])): ?>
                                    <div class="mb-2">请使用微信扫码付款</div>
                                    <img src="<?= htmlspecialchars($paymentQrs['wechat'], ENT_QUOTES, 'UTF-8') ?>" alt="微信收款码" class="img-fluid" style="max-width:180px;">
                                <?php else: ?>
                                    <div class="text-muted">管理员暂未上传微信收款码，请联系客服确认付款方式。</div>
                                <?php endif; ?>
                            </div>
                            <div class="pay-qr-item d-none" data-method="alipay">
                                <?php if (!empty($paymentQrs['alipay'])): ?>
                                    <div class="mb-2">请使用支付宝扫码付款</div>
                                    <img src="<?= htmlspecialchars($paymentQrs['alipay'], ENT_QUOTES, 'UTF-8') ?>" alt="支付宝收款码" class="img-fluid" style="max-width:180px;">
                                <?php else: ?>
                                    <div class="text-muted">管理员暂未上传支付宝收款码，请联系客服确认付款方式。</div>
                                <?php endif; ?>
                            </div>
                            <div class="pay-qr-item d-none" data-method="qq">
                                <?php if (!empty($paymentQrs['qq'])): ?>
                                    <div class="mb-2">请使用 QQ 扫码付款</div>
                                    <img src="<?= htmlspecialchars($paymentQrs['qq'], ENT_QUOTES, 'UTF-8') ?>" alt="QQ 收款码" class="img-fluid" style="max-width:180px;">
                                <?php else: ?>
                                    <div class="text-muted">管理员暂未上传 QQ 收款码，请联系客服确认付款方式。</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="pay-proof-group">
                        <label class="form-label">付款截图（必填）</label>
                        <input type="file" class="form-control" name="pay_proof" accept="image/*" required>
                        <div class="form-text small">请上传付款成功截图，我们会在后台和邮件中通过截图链接快速核对付款。</div>
                    </div>
                    <div class="mb-3" id="period-group">
                        <label class="form-label">授权周期（仅首次授权需要选择）</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input class="btn-check" type="radio" name="period" id="period_month" value="month" autocomplete="off">
                            <label class="btn btn-outline-secondary flex-fill text-start" for="period_month">
                                <div class="fw-semibold">按月</div>
                                <div class="small text-muted">适合短期试用或临时部署。</div>
                            </label>

                            <input class="btn-check" type="radio" name="period" id="period_year" value="year" autocomplete="off">
                            <label class="btn btn-outline-secondary flex-fill text-start" for="period_year">
                                <div class="fw-semibold">按年</div>
                                <div class="small text-muted">性价比更高，适合持续使用。</div>
                            </label>

                            <input class="btn-check" type="radio" name="period" id="period_lifetime" value="lifetime" autocomplete="off">
                            <label class="btn btn-outline-secondary flex-fill text-start" for="period_lifetime">
                                <div class="fw-semibold">永久</div>
                                <div class="small text-muted">一次授权，长期使用。</div>
                            </label>
                        </div>
                        <div class="form-text small">仅在首次授权时需要选择授权周期，更换授权时无需再选。</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">说明</label>
                        <p class="small text-muted mb-1">· 更换授权前，我们会先核验此邮箱下是否有首次授权记录。</p>
                        <p class="small text-muted mb-1">· 授权码离线超过 7 天系统会自动停用，需重新联系获取授权码。</p>
                        <p class="small text-muted mb-0">· 授权相关问题可直接发送邮件至 <strong>9041708@qq.com</strong> 或 QQ：<strong>9041708</strong> 进行咨询。</p>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">提交授权申请</button>
                </form>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<div id="message-board" class="mt-4">
    <h2 class="h5 mb-3">留言板</h2>
    <p class="small text-muted">如对部署授权有任何疑问，可以在此留言，留言将公开展示，并通过邮件通知管理员。</p>

    <?php if (!empty($messageSuccess)): ?>
        <div class="alert alert-success">留言已提交，我们会尽快查看。</div>
    <?php elseif (!empty($messageError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($messageError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="post" action="/public/index.php?route=license-message-submit">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="msg_email" class="form-label">邮箱</label>
                        <input type="email" class="form-control" id="msg_email" name="msg_email" required placeholder="用于接收回复通知">
                    </div>
                    <div class="col-md-3">
                        <label for="msg_nickname" class="form-label">昵称</label>
                        <input type="text" class="form-control" id="msg_nickname" name="msg_nickname" required placeholder="将展示在留言列表中">
                    </div>
                    <div class="col-md-5">
                        <label for="msg_content" class="form-label">留言内容</label>
                        <textarea class="form-control" id="msg_content" name="msg_content" rows="2" required placeholder="请描述您的问题或建议"></textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-primary">提交留言</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3 class="h6 mb-3">已有留言</h3>
            <?php if (empty($messages)): ?>
                <div class="text-muted small">暂无留言，欢迎率先提问。</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($messages as $msg): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="fw-semibold"><?php echo htmlspecialchars((string)($msg['nickname'] ?? '访客'), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars((string)($msg['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                            <div class="small text-muted mb-1">邮箱：<?php echo htmlspecialchars((string)($msg['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div><?php echo nl2br(htmlspecialchars((string)($msg['content'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 源码下载验证弹窗 -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/public/index.php?route=source-download">
                <div class="modal-header">
                    <h5 class="modal-title">验证后下载部署包</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="download_email" class="form-label">授权邮箱</label>
                        <input type="email" class="form-control" id="download_email" name="download_email" required placeholder="填写购买授权时使用的邮箱">
                    </div>
                    <div class="mb-3">
                        <label for="download_license_code" class="form-label">授权码</label>
                        <input type="text" class="form-control" id="download_license_code" name="download_license_code" required placeholder="请输入收到的授权码">
                    </div>
                    <p class="small text-muted mb-0">为防止部署包被滥用，下载前需验证授权邮箱和授权码，如需帮助请联系 9041708@qq.com。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">确认下载</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 授权查询弹窗 -->
<div class="modal fade" id="licenseQueryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">授权信息查询</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="query_email" class="form-label">授权邮箱</label>
                    <input type="email" class="form-control" id="query_email" placeholder="请输入购买授权时使用的邮箱">
                </div>
                <div class="mb-3">
                    <label for="query_domain" class="form-label">授权域名</label>
                    <input type="text" class="form-control" id="query_domain" placeholder="例如：example.com 或 finance.example.com">
                </div>
                <div id="licenseQueryResult" class="small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" id="licenseQueryBtn">查询授权</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const typeFirst = document.getElementById('request_type_first');
    const typeChange = document.getElementById('request_type_change');
    const periodGroup = document.getElementById('period-group');
    const periodRadios = document.querySelectorAll('input[name="period"]');
    const domainFirstGroup = document.getElementById('domain_first_group');
    const domainChangeGroup = document.getElementById('domain_change_group');
    const oldDomainWrapper = document.getElementById('old_domain_wrapper');
    const newDomainWrapper = document.getElementById('new_domain_wrapper');
    const emailInput = document.getElementById('email');
    const oldDomainInput = document.getElementById('old_domain');
    const newDomainInput = document.getElementById('new_domain');
    const payMethodGroup = document.getElementById('pay-method-group');
    const payQrGroup = document.getElementById('pay-qr-group');
    const payProofGroup = document.getElementById('pay-proof-group');
    const payProofInput = document.querySelector('input[name="pay_proof"]');
    const changeQuotaHint = document.getElementById('change_quota_hint');

    function clearPeriodSelection() {
        if (!periodRadios) return;
        periodRadios.forEach(function (el) { el.checked = false; });
    }

    function updateTypeRelatedVisibility() {
        var isChange = typeChange && typeChange.checked;

        if (periodGroup) {
            if (isChange) {
                periodGroup.style.display = 'none';
                clearPeriodSelection();
            } else {
                periodGroup.style.display = '';
            }
        }

        if (domainFirstGroup && domainChangeGroup) {
            if (isChange) {
                domainFirstGroup.classList.add('d-none');
                domainChangeGroup.classList.remove('d-none');
                if (newDomainWrapper) newDomainWrapper.classList.add('d-none');
                if (newDomainInput) newDomainInput.value = '';
                if (changeQuotaHint) {
                    changeQuotaHint.textContent = '更换授权时，请先填写联系邮箱和原授权域名，我们会自动检测是否在免费更换额度内。';
                    changeQuotaHint.className = 'form-text';
                }
                applyFreeChangeUI(false);
                checkFreeChangeQuota();
            } else {
                domainFirstGroup.classList.remove('d-none');
                domainChangeGroup.classList.add('d-none');
                applyFreeChangeUI(false);
            }
        }
    }

    if (typeFirst && typeChange) {
        typeFirst.addEventListener('change', updateTypeRelatedVisibility);
        typeChange.addEventListener('change', updateTypeRelatedVisibility);
        updateTypeRelatedVisibility();
    }

    let isFreeChange = false;

    function applyFreeChangeUI(isFree) {
        isFreeChange = isFree;
        if (payMethodGroup) payMethodGroup.style.display = isFree ? 'none' : '';
        if (payQrGroup) payQrGroup.style.display = isFree ? 'none' : '';
        if (payProofGroup) payProofGroup.style.display = isFree ? 'none' : '';
        if (payProofInput) {
            if (isFree) {
                payProofInput.removeAttribute('required');
            } else {
                payProofInput.setAttribute('required', 'required');
            }
        }
    }

    function checkFreeChangeQuota() {
        if (!typeChange || !typeChange.checked) {
            applyFreeChangeUI(false);
            return;
        }
        if (!emailInput || !oldDomainInput) {
            applyFreeChangeUI(false);
            return;
        }
        const email = emailInput.value.trim();
        const oldDomain = oldDomainInput.value.trim();
        if (!email || !oldDomain) {
            applyFreeChangeUI(false);
            if (newDomainWrapper) newDomainWrapper.classList.add('d-none');
            return;
        }

        const params = new URLSearchParams();
        params.append('email', email);
        params.append('domain', oldDomain);

        fetch('/public/index.php?route=license-query', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString()
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (!data || !data.ok || !data.data) {
                applyFreeChangeUI(false);
                if (changeQuotaHint) {
                    changeQuotaHint.textContent = (data && data.message) ? data.message : '未找到该邮箱与原域名对应的授权记录，请确认后再提交。';
                    changeQuotaHint.className = 'form-text text-danger';
                }
                if (newDomainWrapper) newDomainWrapper.classList.add('d-none');
                return;
            }
            const d = data.data;
            const quota = parseInt(d.domain_change_quota || 0, 10) || 0;
            const used = parseInt(d.domain_change_used || 0, 10) || 0;
            if (newDomainWrapper) newDomainWrapper.classList.remove('d-none');
            if (quota > 0 && used < quota) {
                applyFreeChangeUI(true);
                if (changeQuotaHint) {
                    changeQuotaHint.textContent = '检测到该授权还有 ' + quota + ' 次免费更换额度，已使用 ' + used + ' 次，本次更换无需付款，请填写新授权域名后提交。';
                    changeQuotaHint.className = 'form-text text-success';
                }
            } else {
                applyFreeChangeUI(false);
                if (changeQuotaHint) {
                    if (quota > 0) {
                        changeQuotaHint.textContent = '检测到该授权免费更换额度为 ' + quota + ' 次，已使用 ' + used + ' 次，本次更换需按价格表付款，请选择支付方式并上传付款截图。';
                    } else {
                        changeQuotaHint.textContent = '该授权不包含免费更换额度，本次更换需按价格表付款，请选择支付方式并上传付款截图。';
                    }
                    changeQuotaHint.className = 'form-text text-muted';
                }
            }
        }).catch(function () {
            applyFreeChangeUI(false);
            if (changeQuotaHint) {
                changeQuotaHint.textContent = '检测免费更换额度失败，请检查网络或稍后再试。';
                changeQuotaHint.className = 'form-text text-danger';
            }
        });
    }

    if (emailInput && oldDomainInput) {
        emailInput.addEventListener('blur', checkFreeChangeQuota);
        oldDomainInput.addEventListener('blur', checkFreeChangeQuota);
        emailInput.addEventListener('input', function () {
            if (typeChange && typeChange.checked) {
                checkFreeChangeQuota();
            }
        });
        oldDomainInput.addEventListener('input', function () {
            if (typeChange && typeChange.checked) {
                checkFreeChangeQuota();
            }
        });
    }

    const queryBtn = document.getElementById('licenseQueryBtn');
    const queryEmail = document.getElementById('query_email');
    const queryDomain = document.getElementById('query_domain');
    const queryResult = document.getElementById('licenseQueryResult');

    function setQueryResult(message, isError) {
        if (!queryResult) return;
        queryResult.textContent = message;
        queryResult.className = 'small ' + (isError ? 'text-danger' : 'text-success');
    }

    if (queryBtn && queryEmail && queryDomain && queryResult) {
        queryBtn.addEventListener('click', function () {
            const email = queryEmail.value.trim();
            const domain = queryDomain.value.trim();

            if (!email) {
                setQueryResult('请先填写授权邮箱', true);
                return;
            }
            if (!domain) {
                setQueryResult('请先填写授权域名', true);
                return;
            }

            setQueryResult('查询中，请稍候…', false);
            queryBtn.disabled = true;

            const params = new URLSearchParams();
            params.append('email', email);
            params.append('domain', domain);

            fetch('/public/index.php?route=license-query', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: params.toString()
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.ok) {
                    setQueryResult((data && data.message) ? data.message : '查询失败，请稍后再试', true);
                    return;
                }
                const d = data.data || {};
                var text = '授权码：' + (d.license_code || '未知') + '\n';
                text += '授权状态：' + (d.status || '未知') + '\n';
                text += '授权周期：' + (d.period || '未知') + '\n';
                if (typeof d.domain_change_quota !== 'undefined') {
                    var quota = parseInt(d.domain_change_quota || 0, 10) || 0;
                    var used = parseInt(d.domain_change_used || 0, 10) || 0;
                    text += '\n免费更换额度：' + quota + ' 次，已用 ' + used + ' 次';
                }
                if (d.expire_at) {
                    text += '到期时间：' + d.expire_at;
                } else {
                    text += '到期时间：-';
                }
                queryResult.textContent = text;
                queryResult.className = 'small text-success';
            }).catch(function () {
                setQueryResult('查询失败，请检查网络或稍后再试', true);
            }).finally(function () {
                queryBtn.disabled = false;
            });
        });
    }
    // 支付方式切换展示收款码
    const payMethodInputs = document.querySelectorAll('input[name="pay_method"]');
    const payQrItems = document.querySelectorAll('#payQrContainer .pay-qr-item');
    const payQrPlaceholder = document.getElementById('payQrPlaceholder');

    function updatePayQr() {
        if (!payMethodInputs.length || !payQrItems.length) return;
        let current = null;
        payMethodInputs.forEach(function (el) {
            if (el.checked) {
                current = el.value;
            }
        });
        if (!current) {
            payQrItems.forEach(function (item) { item.classList.add('d-none'); });
            if (payQrPlaceholder) {
                payQrPlaceholder.classList.remove('d-none');
            }
            return;
        }
        payQrItems.forEach(function (item) {
            if (item.getAttribute('data-method') === current) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
        if (payQrPlaceholder) {
            payQrPlaceholder.classList.add('d-none');
        }
    }

    if (payMethodInputs.length && payQrItems.length) {
        payMethodInputs.forEach(function (el) {
            el.addEventListener('change', updatePayQr);
        });
    }
})();
</script>
