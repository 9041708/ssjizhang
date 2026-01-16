<?php
/** @var array $pricing */
/** @var array $users */
/** @var array $requests */
/** @var string $tab */
/** @var string $userSearch */

use App\Model\LicenseUser;
use App\Service\Config;

function license_status_label(?string $status): string {
    switch ($status) {
        case 'unused': return '未使用';
        case 'normal': return '正常';
        case 'expired': return '已过期';
    }
    return '未知';
}

function request_status_label(?string $status): string {
    switch ($status) {
        case 'pending':
            return '待处理';
        case 'processed':
            return '已生成授权';
        case 'rejected':
            return '已拒绝';
        case 'failed':
            return '处理失败';
    }
    return $status !== null && $status !== '' ? $status : '未知';
}

function period_label($period): string {
    if ($period === 'month') return '按月';
    if ($period === 'year') return '按年';
    if ($period === 'lifetime') return '永久';
    return '未知';
}

$uploadDir = Config::get('app.upload_dir', __DIR__ . '/../../uploads');
$systemDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . 'system';
$wechatQrUrl = is_file($systemDir . DIRECTORY_SEPARATOR . 'pay_wechat.png') ? '/uploads/system/pay_wechat.png' : '';
$alipayQrUrl = is_file($systemDir . DIRECTORY_SEPARATOR . 'pay_alipay.png') ? '/uploads/system/pay_alipay.png' : '';
$qqQrUrl = is_file($systemDir . DIRECTORY_SEPARATOR . 'pay_qq.png') ? '/uploads/system/pay_qq.png' : '';

$tab = $tab ?? 'users';
$userSearch = $userSearch ?? '';
?>
<div class="row mb-3">
    <div class="col">
        <h1 class="h4 mb-0">授权管理</h1>
        <div class="text-muted small mt-1">仅主站管理员可见，用于管理授权用户、授权价格及授权申请。</div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'users' ? 'active' : '' ?>" href="/public/index.php?route=license-admin&tab=users">授权用户</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'requests' ? 'active' : '' ?>" href="/public/index.php?route=license-admin&tab=requests">授权申请</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'pricing' ? 'active' : '' ?>" href="/public/index.php?route=license-admin&tab=pricing">价格配置</a>
    </li>
</ul>

<?php if ($tab === 'pricing'): ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">授权价格配置</h2>
            <form method="post" action="/public/index.php?route=license-admin&tab=pricing">
                <input type="hidden" name="action" value="save_pricing">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">按月 - 首次授权原价</label>
                        <input type="number" step="0.01" class="form-control" name="first_month_price" value="<?= htmlspecialchars((string)($pricing['first_month_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">按月 - 首次授权活动价</label>
                        <input type="number" step="0.01" class="form-control" name="first_month_price_promo" value="<?= htmlspecialchars((string)($pricing['first_month_price_promo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">按年 - 首次授权原价</label>
                        <input type="number" step="0.01" class="form-control" name="first_year_price" value="<?= htmlspecialchars((string)($pricing['first_year_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">按年 - 首次授权活动价</label>
                        <input type="number" step="0.01" class="form-control" name="first_year_price_promo" value="<?= htmlspecialchars((string)($pricing['first_year_price_promo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">永久 - 首次授权原价</label>
                        <input type="number" step="0.01" class="form-control" name="first_lifetime_price" value="<?= htmlspecialchars((string)($pricing['first_lifetime_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">永久 - 首次授权活动价</label>
                        <input type="number" step="0.01" class="form-control" name="first_lifetime_price_promo" value="<?= htmlspecialchars((string)($pricing['first_lifetime_price_promo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <label class="form-label">更换授权 - 单次原价</label>
                        <input type="number" step="0.01" class="form-control" name="change_price" value="<?= htmlspecialchars((string)($pricing['change_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">更换授权 - 单次活动价</label>
                        <input type="number" step="0.01" class="form-control" name="change_price_promo" value="<?= htmlspecialchars((string)($pricing['change_price_promo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_promo_active" name="is_promo_active" <?= !empty($pricing['is_promo_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_promo_active">启用活动价（前台授权价格优先展示活动价）</label>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">保存价格配置</button>
                </div>
            </form>

            <hr class="my-4">

            <h2 class="h5 mb-3">收款码配置</h2>
            <form method="post" action="/public/index.php?route=license-admin&tab=pricing" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_pay_qr">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">微信收款码</label>
                        <?php if ($wechatQrUrl): ?>
                            <div class="mb-2 text-center">
                                <img src="<?= htmlspecialchars($wechatQrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="微信收款码" class="img-fluid border rounded" style="max-width:260px;height:auto;">
                            </div>
                        <?php else: ?>
                            <div class="mb-2 small text-muted">当前尚未上传微信收款码。</div>
                        <?php endif; ?>
                        <input type="file" name="wechat_qr" accept="image/*" class="form-control">
                        <div class="form-text small">上传后将覆盖原有图片，前台授权页将直接引用固定路径。</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">支付宝收款码</label>
                        <?php if ($alipayQrUrl): ?>
                            <div class="mb-2 text-center">
                                <img src="<?= htmlspecialchars($alipayQrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="支付宝收款码" class="img-fluid border rounded" style="max-width:260px;height:auto;">
                            </div>
                        <?php else: ?>
                            <div class="mb-2 small text-muted">当前尚未上传支付宝收款码。</div>
                        <?php endif; ?>
                        <input type="file" name="alipay_qr" accept="image/*" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">QQ 收款码</label>
                        <?php if ($qqQrUrl): ?>
                            <div class="mb-2 text-center">
                                <img src="<?= htmlspecialchars($qqQrUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QQ 收款码" class="img-fluid border rounded" style="max-width:260px;height:auto;">
                            </div>
                        <?php else: ?>
                            <div class="mb-2 small text-muted">当前尚未上传 QQ 收款码。</div>
                        <?php endif; ?>
                        <input type="file" name="qq_qr" accept="image/*" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-outline-primary">保存收款码</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($tab === 'requests'): ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">授权申请列表（全部）</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>时间</th>
                        <th>邮箱</th>
                        <th>域名</th>
                        <th>类型</th>
                        <th>周期</th>
                        <th>付款凭证</th>
                        <th>备注/说明</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="9" class="text-center text-muted">暂无授权申请</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <?php
                                $reqId = (int)$r['id'];
                                $reqEmail = (string)$r['email'];
                                $reqDomain = (string)$r['domain'];
                                $reqType = (string)($r['request_type'] ?? 'first');
                                $reqTypeLabel = $reqType === 'change' ? '更换授权' : '首次授权';
                                $reqPeriodLabel = $reqType === 'first' ? period_label($r['period'] ?? null) : '-';
                                $reqStatusRaw = (string)($r['status'] ?? '');
                                $reqStatusLabel = request_status_label($reqStatusRaw);
                                $reqNote = (string)($r['note'] ?? '');
                                $reqCreatedAt = (string)($r['created_at'] ?? '');
                                $canGenerate = ($reqStatusRaw !== 'processed' && $reqType === 'first');
                                ?>
                                <td><?= $reqId ?></td>
                                <td><?= htmlspecialchars($reqCreatedAt, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($reqEmail, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($reqDomain, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($reqTypeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($reqPeriodLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                    $payProofPath = (string)($r['pay_proof_path'] ?? '');
                                    if ($payProofPath !== ''):
                                        $payProofUrl = '/uploads/' . ltrim($payProofPath, '/\\');
                                    ?>
                                        <a href="#" class="pay-proof-thumb" data-img="<?= htmlspecialchars($payProofUrl, ENT_QUOTES, 'UTF-8') ?>" title="点击查看大图">
                                            <img src="<?= htmlspecialchars($payProofUrl, ENT_QUOTES, 'UTF-8') ?>" alt="付款截图" class="img-thumbnail" style="max-width:60px;max-height:60px;">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($reqNote !== ''): ?>
                                        <span class="small"><?= htmlspecialchars($reqNote, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($reqStatusLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#requestManageModal"
                                            data-id="<?= $reqId ?>"
                                            data-email="<?= htmlspecialchars($reqEmail, ENT_QUOTES, 'UTF-8') ?>"
                                            data-domain="<?= htmlspecialchars($reqDomain, ENT_QUOTES, 'UTF-8') ?>"
                                            data-created-at="<?= htmlspecialchars($reqCreatedAt, ENT_QUOTES, 'UTF-8') ?>"
                                            data-type="<?= htmlspecialchars($reqType, ENT_QUOTES, 'UTF-8') ?>"
                                            data-type-label="<?= htmlspecialchars($reqTypeLabel, ENT_QUOTES, 'UTF-8') ?>"
                                            data-period-label="<?= htmlspecialchars($reqPeriodLabel, ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars($reqStatusRaw, ENT_QUOTES, 'UTF-8') ?>"
                                            data-status-label="<?= htmlspecialchars($reqStatusLabel, ENT_QUOTES, 'UTF-8') ?>"
                                            data-note="<?= htmlspecialchars($reqNote, ENT_QUOTES, 'UTF-8') ?>"
                                            data-can-generate="<?= $canGenerate ? '1' : '0' ?>">
                                        管理
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">授权用户（最近 50 条）</h2>
            <form class="row g-2 mb-3" method="get" action="/public/index.php">
                <input type="hidden" name="route" value="license-admin">
                <input type="hidden" name="tab" value="users">
                <div class="col-sm-4 col-md-3">
                    <input type="text" class="form-control" name="user_q" placeholder="搜索邮箱或域名" value="<?= htmlspecialchars($userSearch, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-sm-2 col-md-2">
                    <button type="submit" class="btn btn-outline-primary">搜索</button>
                </div>
                <?php if ($userSearch !== ''): ?>
                    <div class="col-12 col-md-auto align-self-center small text-muted">
                        当前搜索：<?= htmlspecialchars($userSearch, ENT_QUOTES, 'UTF-8') ?>
                        <a href="/public/index.php?route=license-admin&amp;tab=users" class="ms-1">清除</a>
                    </div>
                <?php endif; ?>
            </form>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>邮箱</th>
                        <th>授权域名</th>
                        <th>授权码</th>
                        <th>周期</th>
                        <th>状态</th>
                        <th>免费更换/已用</th>
                        <th>创建时间</th>
                        <th>激活时间</th>
                        <th>最后在线</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="11" class="text-center text-muted"><?= $userSearch === '' ? '暂无授权用户' : '未找到匹配的授权用户' ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$u['domain'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars((string)$u['license_code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= period_label($u['period_type'] ?? ($u['license_period'] ?? null)) ?></td>
                                <td><?= license_status_label($u['license_status'] ?? ($u['status'] ?? null)) ?></td>
                                <td><?= (int)($u['domain_change_quota'] ?? 0) ?>/<?= (int)($u['domain_change_used'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($u['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($u['activated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($u['last_online_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#userManageModal"
                                            data-id="<?= (int)$u['id'] ?>"
                                            data-email="<?= htmlspecialchars((string)$u['email'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-domain="<?= htmlspecialchars((string)$u['domain'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= htmlspecialchars((string)($u['license_status'] ?? ($u['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            data-status-label="<?= htmlspecialchars(license_status_label($u['license_status'] ?? ($u['status'] ?? null)), ENT_QUOTES, 'UTF-8') ?>"
                                            data-period-label="<?= htmlspecialchars(period_label($u['period_type'] ?? ($u['license_period'] ?? null)), ENT_QUOTES, 'UTF-8') ?>"
                                            data-quota="<?= (int)($u['domain_change_quota'] ?? 0) ?>"
                                            data-used="<?= (int)($u['domain_change_used'] ?? 0) ?>">
                                        管理
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 付款凭证大图预览弹窗 -->
<div class="modal fade" id="payProofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">付款凭证预览</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="付款截图" id="payProofModalImg" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<!-- 授权用户管理弹窗 -->
<div class="modal fade" id="userManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">管理授权用户</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="small text-muted">授权概览</div>
                    <div class="fw-semibold" id="userManageEmail"></div>
                    <div class="small" id="userManageDomain"></div>
                    <div class="small text-muted" id="userManageMeta"></div>
                </div>

                <div class="border rounded p-2 mb-3">
                    <div class="small text-muted mb-1">授权状态</div>
                    <form method="post" action="/public/index.php?route=license-admin&amp;tab=users" class="row g-2 align-items-center">
                        <input type="hidden" name="action" value="update_user_status">
                        <input type="hidden" name="id" id="userIdStatus" value="">
                        <div class="col-auto">
                            <select name="status" id="userStatusSelect" class="form-select form-select-sm">
                                <option value="unused">未使用</option>
                                <option value="normal">正常</option>
                                <option value="expired">已停用</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-outline-primary">更新状态</button>
                        </div>
                    </form>
                </div>

                <div class="border rounded p-2 mb-3">
                    <div class="small text-muted mb-1">联系邮箱</div>
                    <form method="post" action="/public/index.php?route=license-admin&amp;tab=users" class="row g-2 align-items-center">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="id" id="userIdEmail" value="">
                        <input type="hidden" name="domain" id="userCurrentDomain" value="">
                        <div class="col-sm-6 col-md-5">
                            <input type="email" name="email" id="userEmailInput" class="form-control form-control-sm" placeholder="编辑邮箱">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">保存邮箱</button>
                        </div>
                    </form>
                </div>

                <div class="border rounded p-2 mb-3">
                    <div class="small text-muted mb-1">授权域名</div>
                    <form method="post" action="/public/index.php?route=license-admin&amp;tab=users" class="row g-2 align-items-center">
                        <input type="hidden" name="action" value="change_domain">
                        <input type="hidden" name="id" id="userIdDomain" value="">
                        <div class="col-sm-6 col-md-5">
                            <input type="text" name="new_domain" id="userDomainInput" class="form-control form-control-sm" placeholder="更换授权域名">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">更换域名</button>
                        </div>
                    </form>
                    <div class="small text-muted mt-1" id="userDomainQuotaHint"></div>
                </div>

                <div class="border rounded p-2">
                    <div class="small text-muted mb-2">其他操作</div>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="/public/index.php?route=license-admin&amp;tab=users" class="d-inline">
                            <input type="hidden" name="action" value="resend_license">
                            <input type="hidden" name="id" id="userIdResend" value="">
                            <button type="submit" class="btn btn-sm btn-outline-success">重发授权码</button>
                        </form>
                        <form method="post" action="/public/index.php?route=license-admin&amp;tab=users" class="d-inline" onsubmit="return confirm('确定要停止该授权吗？');">
                            <input type="hidden" name="action" value="stop_user">
                            <input type="hidden" name="id" id="userIdStop" value="">
                            <button type="submit" class="btn btn-sm btn-outline-warning">停止授权</button>
                        </form>
                        <form method="post" action="/public/index.php?route=license-admin&amp;tab=users" class="d-inline" onsubmit="return confirm('确定要删除该授权用户吗？此操作不可恢复。');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="id" id="userIdDelete" value="">
                            <button type="submit" class="btn btn-sm btn-outline-danger">删除用户</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 授权申请管理弹窗 -->
<div class="modal fade" id="requestManageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">管理授权申请</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="small text-muted">申请概览</div>
                    <div class="fw-semibold" id="requestManageEmail"></div>
                    <div class="small" id="requestManageDomain"></div>
                    <div class="small text-muted" id="requestManageMeta"></div>
                </div>

                <div class="border rounded p-2 mb-3">
                    <div class="small text-muted mb-1">备注 / 处理说明</div>
                    <form method="post" action="/public/index.php?route=license-admin&amp;tab=requests">
                        <input type="hidden" name="action" value="update_request_note">
                        <input type="hidden" name="id" id="requestIdNote" value="">
                        <textarea name="note" id="requestNoteInput" rows="3" class="form-control form-control-sm" placeholder="填写该申请的处理说明或备注"></textarea>
                        <div class="mt-2 text-end">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">保存备注</button>
                        </div>
                    </form>
                </div>

                <div class="border rounded p-2">
                    <div class="small text-muted mb-2">其他操作</div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <form method="post" action="/public/index.php?route=license-admin&amp;tab=requests" class="d-inline" id="requestGenerateForm">
                            <input type="hidden" name="action" value="generate_license">
                            <input type="hidden" name="request_id" id="requestIdGenerate" value="">
                            <button type="submit" class="btn btn-sm btn-primary">生成授权用户</button>
                        </form>
                        <form method="post" action="/public/index.php?route=license-admin&amp;tab=requests" class="d-inline ms-auto" onsubmit="return confirm('确定要删除该授权申请吗？此操作不可恢复。');">
                            <input type="hidden" name="action" value="delete_request">
                            <input type="hidden" name="id" id="requestIdDelete" value="">
                            <button type="submit" class="btn btn-sm btn-outline-danger">删除申请</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined') {
        return;
    }

    // 付款凭证大图预览
    var modalEl = document.getElementById('payProofModal');
    if (modalEl) {
        var modalImg = document.getElementById('payProofModalImg');
        var modal = new bootstrap.Modal(modalEl);

        document.querySelectorAll('.pay-proof-thumb').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                var src = this.getAttribute('data-img');
                if (modalImg && src) {
                    modalImg.src = src;
                    modal.show();
                }
            });
        });
    }

    // 授权用户管理弹窗数据填充
    var userModalEl = document.getElementById('userManageModal');
    if (userModalEl) {
        userModalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var id = button.getAttribute('data-id') || '';
            var email = button.getAttribute('data-email') || '';
            var domain = button.getAttribute('data-domain') || '';
            var status = button.getAttribute('data-status') || '';
            var statusLabel = button.getAttribute('data-status-label') || '';
            var periodLabel = button.getAttribute('data-period-label') || '';
            var quota = button.getAttribute('data-quota') || '0';
            var used = button.getAttribute('data-used') || '0';

            var overviewEmail = document.getElementById('userManageEmail');
            var overviewDomain = document.getElementById('userManageDomain');
            var overviewMeta = document.getElementById('userManageMeta');

            if (overviewEmail) overviewEmail.textContent = email + '（ID ' + id + '）';
            if (overviewDomain) overviewDomain.textContent = '授权域名：' + domain;
            if (overviewMeta) overviewMeta.textContent = '周期：' + (periodLabel || '未知') + '；状态：' + (statusLabel || status || '未知') + '；免费更换/已用：' + quota + '/' + used;

            var statusSelect = document.getElementById('userStatusSelect');
            if (statusSelect) {
                statusSelect.value = status || 'normal';
            }

            var emailInput = document.getElementById('userEmailInput');
            if (emailInput) emailInput.value = email;

            var domainInput = document.getElementById('userDomainInput');
            if (domainInput) domainInput.value = domain;

            var domainQuotaHint = document.getElementById('userDomainQuotaHint');
            if (domainQuotaHint) domainQuotaHint.textContent = '当前免费更换额度：' + quota + ' 次，已用：' + used + ' 次。';

            var idStatus = document.getElementById('userIdStatus');
            if (idStatus) idStatus.value = id;
            var idEmail = document.getElementById('userIdEmail');
            if (idEmail) idEmail.value = id;
            var idDomain = document.getElementById('userIdDomain');
            if (idDomain) idDomain.value = id;
            var idResend = document.getElementById('userIdResend');
            if (idResend) idResend.value = id;
            var idStop = document.getElementById('userIdStop');
            if (idStop) idStop.value = id;
            var idDelete = document.getElementById('userIdDelete');
            if (idDelete) idDelete.value = id;

            var currentDomain = document.getElementById('userCurrentDomain');
            if (currentDomain) currentDomain.value = domain;
        });
    }

    // 授权申请管理弹窗数据填充
    var requestModalEl = document.getElementById('requestManageModal');
    if (requestModalEl) {
        requestModalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var id = button.getAttribute('data-id') || '';
            var email = button.getAttribute('data-email') || '';
            var domain = button.getAttribute('data-domain') || '';
            var createdAt = button.getAttribute('data-created-at') || '';
            var typeLabel = button.getAttribute('data-type-label') || '';
            var periodLabel = button.getAttribute('data-period-label') || '';
            var statusLabel = button.getAttribute('data-status-label') || '';
            var note = button.getAttribute('data-note') || '';
            var canGenerate = button.getAttribute('data-can-generate') === '1';

            var overviewEmail = document.getElementById('requestManageEmail');
            var overviewDomain = document.getElementById('requestManageDomain');
            var overviewMeta = document.getElementById('requestManageMeta');

            if (overviewEmail) overviewEmail.textContent = email + '（ID ' + id + '）';
            if (overviewDomain) overviewDomain.textContent = '授权域名：' + domain;
            if (overviewMeta) overviewMeta.textContent = '时间：' + createdAt + '；类型：' + (typeLabel || '未知') + '；周期：' + (periodLabel || '未知') + '；状态：' + (statusLabel || '未知');

            var noteInput = document.getElementById('requestNoteInput');
            if (noteInput) noteInput.value = note;

            var idNote = document.getElementById('requestIdNote');
            if (idNote) idNote.value = id;
            var idDelete = document.getElementById('requestIdDelete');
            if (idDelete) idDelete.value = id;
            var idGenerate = document.getElementById('requestIdGenerate');
            if (idGenerate) idGenerate.value = id;

            var generateForm = document.getElementById('requestGenerateForm');
            if (generateForm) {
                generateForm.style.display = canGenerate ? '' : 'none';
            }
        });
    }
});
</script>
