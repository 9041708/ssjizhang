<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">记账明细</h2>
    <div class="small text-muted">
        共 <?= isset($totalCount) ? (int)$totalCount : (is_countable($transactions ?? null) ? count($transactions) : 0) ?> 条记录
    </div>
</div>
<form method="get" action="/public/index.php" class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <input type="hidden" name="route" value="transactions">

        <div class="col-6 col-md-2">
            <label class="form-label small">类型</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">全部</option>
                <option value="expense" <?= $filters['type'] === 'expense' ? 'selected' : '' ?>>支出</option>
                <option value="income" <?= $filters['type'] === 'income' ? 'selected' : '' ?>>收入</option>
            </select>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small">分类</label>
            <select name="category_id" class="form-select form-select-sm tx-select tx-category-select">
                <option value="">所有分类</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                            data-type="<?= htmlspecialchars($c['type']) ?>"
                            data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                            data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>"
                            <?= (string)$filters['category_id'] === (string)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="small mt-1" id="txFilterCategoryIconPreview"></div>
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label small">项目</label>
            <select name="item_id" class="form-select form-select-sm">
                <option value="">所有项目</option>
                <?php
                // 为项目选项附加分类和类型、图标信息
                $categoryTypeMap = [];
                foreach ($categories as $c) {
                    $categoryTypeMap[$c['id']] = $c['type'];
                }
                ?>
                <?php foreach ($items as $i): ?>
                    <?php $itemType = $categoryTypeMap[$i['category_id']] ?? ''; ?>
                    <option value="<?= (int)$i['id'] ?>"
                            data-category-id="<?= (int)$i['category_id'] ?>"
                            data-type="<?= htmlspecialchars($itemType, ENT_QUOTES) ?>"
                            data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                            data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>"
                            <?= (string)$filters['item_id'] === (string)$i['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($i['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="small mt-1" id="txFilterItemIconPreview"></div>
        </div>

        <div class="col-6 col-md-4">
            <label class="form-label small">账户</label>
            <select name="account_id" class="form-select form-select-sm">
                <option value="">所有账户</option>
                <?php foreach ($accounts as $a): ?>
                    <?php
                    $balance = (float)$a['current_balance'];
                    $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                    ?>
                    <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                            data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                            data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>"
                            <?= (string)$filters['account_id'] === (string)$a['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="small mt-1" id="txFilterAccountIconPreview"></div>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label small">起始日期</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small">结束日期</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small">起始金额</label>
            <input type="number" step="0.01" name="amount_min" value="<?= htmlspecialchars($filters['amount_min']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small">结束金额</label>
            <input type="number" step="0.01" name="amount_max" value="<?= htmlspecialchars($filters['amount_max']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label small">备注（模糊）</label>
            <input type="text" name="remark" value="<?= htmlspecialchars($filters['remark']) ?>" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-1 d-grid">
            <button type="submit" class="btn btn-sm btn-primary">筛选</button>
        </div>
    </div>
</form>

<form id="txBulkForm" method="post" action="/public/index.php?route=transaction-delete" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th style="width:32px;"></th>
                    <th>类型</th>
                    <th>分类</th>
                    <th>项目</th>
                    <th>账户</th>
                    <th class="text-end">金额</th>
                    <th>时间</th>
                    <th>备注</th>
                    <th>凭证</th>
                    <th class="text-center">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="10" class="text-center text-muted small">暂无记录</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input tx-check" name="ids[]" value="<?= (int)$t['id'] ?>"></td>
                            <td>
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="text-danger">● 收入</span>
                                <?php else: ?>
                                    <span class="text-success">● 支出</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($t['category_icon_type']) && !empty($t['category_icon_value'])): ?>
                                    <?php if ($t['category_icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($t['category_icon_value'], ENT_QUOTES) ?>" alt="分类图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                    <?php elseif ($t['category_icon_type'] === 'svg'): ?>
                                        <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                            <?= $t['category_icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($t['category_name'] ?? '') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($t['item_icon_type']) && !empty($t['item_icon_value'])): ?>
                                    <?php if ($t['item_icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($t['item_icon_value'], ENT_QUOTES) ?>" alt="项目图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                    <?php elseif ($t['item_icon_type'] === 'svg'): ?>
                                        <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                            <?= $t['item_icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($t['item_name'] ?? '') ?></span>
                            </td>
                            <td>
                                <?php if ($t['type'] === 'expense'): ?>
                                    <?php if (!empty($t['from_account_icon_type']) && !empty($t['from_account_icon_value'])): ?>
                                        <?php if ($t['from_account_icon_type'] === 'file'): ?>
                                            <img src="/uploads/<?= htmlspecialchars($t['from_account_icon_value'], ENT_QUOTES) ?>" alt="账户图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                        <?php elseif ($t['from_account_icon_type'] === 'svg'): ?>
                                            <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                                <?= $t['from_account_icon_value'] ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($t['from_account_name'] ?? '') ?></span>
                                <?php else: ?>
                                    <?php if (!empty($t['to_account_icon_type']) && !empty($t['to_account_icon_value'])): ?>
                                        <?php if ($t['to_account_icon_type'] === 'file'): ?>
                                            <img src="/uploads/<?= htmlspecialchars($t['to_account_icon_value'], ENT_QUOTES) ?>" alt="账户图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                        <?php elseif ($t['to_account_icon_type'] === 'svg'): ?>
                                            <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                                <?= $t['to_account_icon_value'] ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($t['to_account_name'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="text-danger">+<?= number_format($t['amount'], 2) ?></span>
                                <?php else: ?>
                                    <span class="text-success">-<?= number_format($t['amount'], 2) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($t['trans_time']) ?></td>
                            <td><?= htmlspecialchars($t['remark'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($t['attachment_path'])): ?>
                                    <img src="/uploads/<?= htmlspecialchars($t['attachment_path'], ENT_QUOTES) ?>"
                                         alt="凭证"
                                         class="attachment-thumb"
                                         style="max-width:60px;max-height:60px;object-fit:cover;border-radius:4px;cursor:zoom-in;"
                                         data-attachment-preview="/uploads/<?= htmlspecialchars($t['attachment_path'], ENT_QUOTES) ?>">
                                <?php else: ?>
                                    <span class="text-muted small">无</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#txDetailModal"
                                        data-id="<?= (int)$t['id'] ?>"
                                        data-type="<?= htmlspecialchars($t['type'], ENT_QUOTES) ?>"
                                        data-category-name="<?= htmlspecialchars($t['category_name'] ?? '', ENT_QUOTES) ?>"
                                        data-item-name="<?= htmlspecialchars($t['item_name'] ?? '', ENT_QUOTES) ?>"
                                        data-account-name="<?= htmlspecialchars($t['type'] === 'expense' ? ($t['from_account_name'] ?? '') : ($t['to_account_name'] ?? ''), ENT_QUOTES) ?>"
                                        data-account-balance="<?= htmlspecialchars((string)($t['type'] === 'expense' ? ($t['from_account_balance'] ?? '') : ($t['to_account_balance'] ?? '')), ENT_QUOTES) ?>"
                                        data-amount="<?= htmlspecialchars((string)$t['amount'], ENT_QUOTES) ?>"
                                        data-trans-time="<?= htmlspecialchars($t['trans_time'], ENT_QUOTES) ?>"
                                        data-remark="<?= htmlspecialchars($t['remark'] ?? '', ENT_QUOTES) ?>"
                                        data-attachment-url="<?= !empty($t['attachment_path']) ? '/uploads/' . htmlspecialchars($t['attachment_path'], ENT_QUOTES) : '' ?>">
                                    详情
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalTxEditFromList"
                                        data-id="<?= (int)$t['id'] ?>"
                                        data-type="<?= htmlspecialchars($t['type'], ENT_QUOTES) ?>"
                                        data-category-id="<?= (int)($t['category_id'] ?? 0) ?>"
                                        data-item-id="<?= (int)($t['item_id'] ?? 0) ?>"
                                        data-from-account-id="<?= (int)($t['from_account_id'] ?? 0) ?>"
                                        data-to-account-id="<?= (int)($t['to_account_id'] ?? 0) ?>"
                                        data-amount="<?= htmlspecialchars((string)$t['amount'], ENT_QUOTES) ?>"
                                        data-trans-time="<?= htmlspecialchars($t['trans_time'], ENT_QUOTES) ?>"
                                        data-remark="<?= htmlspecialchars($t['remark'] ?? '', ENT_QUOTES) ?>"
                                        data-attachment-path="<?= htmlspecialchars($t['attachment_path'] ?? '', ENT_QUOTES) ?>">
                                    编辑
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="deleteSingleTransaction(<?= (int)$t['id'] ?>)">
                                    删除
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">支持批量删除，删除后对应账户余额会同步回滚。</div>
        <button type="submit" class="btn btn-sm btn-outline-danger">删除选中</button>
    </div>
</form>

<!-- 流水详情弹窗 -->
<div class="modal fade" id="txDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">流水详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">类型</dt>
                    <dd class="col-sm-9" id="txDetailType"></dd>

                    <dt class="col-sm-3">分类 / 项目</dt>
                    <dd class="col-sm-9" id="txDetailCategoryItem"></dd>

                    <dt class="col-sm-3">账户</dt>
                    <dd class="col-sm-9" id="txDetailAccount"></dd>

                    <dt class="col-sm-3">金额</dt>
                    <dd class="col-sm-9" id="txDetailAmount"></dd>

                    <dt class="col-sm-3">账户当前余额</dt>
                    <dd class="col-sm-9" id="txDetailAccountBalance"></dd>

                    <dt class="col-sm-3">时间</dt>
                    <dd class="col-sm-9" id="txDetailTransTime"></dd>

                    <dt class="col-sm-3">备注</dt>
                    <dd class="col-sm-9"><pre id="txDetailRemark" class="mb-0" style="white-space:pre-wrap;font-size:.875rem;"></pre></dd>

                    <dt class="col-sm-3">图片凭证</dt>
                    <dd class="col-sm-9" id="txDetailAttachmentWrapper">
                        <span class="text-muted small">无</span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- 凭证大图预览弹窗（仅支持点击空白区域关闭，移除右上角关闭按钮） -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title small">图片凭证预览</h6>
            </div>
            <div class="modal-body text-center">
                <img id="attachmentPreviewImage" src="" alt="凭证预览" class="img-fluid rounded shadow-sm">
            </div>
        </div>
    </div>
</div>

<!-- 记账明细页编辑弹窗 -->
<div class="modal fade" id="modalTxEditFromList" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑记账</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="txEditFormFromList" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label small">类型</label>
                            <select name="type" class="form-select form-select-sm" id="txEditTypeList">
                                <option value="expense">支出</option>
                                <option value="income">收入</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small">分类</label>
                            <select name="category_id" class="form-select form-select-sm tx-select tx-category-select" id="txEditCategoryList">
                                <option value="">请选择</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" data-type="<?= htmlspecialchars($c['type']) ?>"><?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small">项目</label>
                            <select name="item_id" class="form-select form-select-sm" id="txEditItemList">
                                <option value="">不选项目</option>
                                <?php foreach ($items as $i): ?>
                                    <option value="<?= (int)$i['id'] ?>" data-category="<?= (int)$i['category_id'] ?>"><?= htmlspecialchars($i['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small">金额</label>
                            <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm" id="txEditAmountList">
                        </div>

                        <div class="col-12 col-md-4" data-role="tx-edit-from-account-group-list">
                            <label class="form-label small">支出账户（支出/转出）</label>
                            <select name="from_account_id" class="form-select form-select-sm" id="txEditFromAccountList">
                                <option value="">不选择</option>
                                <?php foreach ($accounts as $a): ?>
                                    <?php
                                    $balance = (float)$a['current_balance'];
                                    $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                                    ?>
                                    <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"><?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4" data-role="tx-edit-to-account-group-list">
                            <label class="form-label small">收入账户（收入/转入）</label>
                            <select name="to_account_id" class="form-select form-select-sm" id="txEditToAccountList">
                                <option value="">不选择</option>
                                <?php foreach ($accounts as $a): ?>
                                    <?php
                                    $balance = (float)$a['current_balance'];
                                    $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                                    ?>
                                    <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"><?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small">记账时间</label>
                            <input type="datetime-local" name="trans_time" class="form-control form-control-sm" id="txEditTransTimeList" step="60">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">备注</label>
                            <textarea name="remark" rows="2" class="form-control form-control-sm" id="txEditRemarkList"></textarea>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small">图片凭证（≤10MB）</label>
                            <input type="file" name="attachment" accept="image/*" class="form-control form-control-sm" id="txEditAttachmentInputList">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="txEditRemoveAttachmentList">
                                <label class="form-check-label small" for="txEditRemoveAttachmentList">删除当前图片</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end" id="txEditAttachmentPreviewWrapperList" style="min-height:2.5rem;">
                            <div class="small text-muted" id="txEditAttachmentPlaceholderList">当前无凭证</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 流水详情弹窗
    (function () {
        var modalEl = document.getElementById('txDetailModal');
        if (!modalEl || !window.bootstrap) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var type = button.getAttribute('data-type') || '';
            var categoryName = button.getAttribute('data-category-name') || '';
            var itemName = button.getAttribute('data-item-name') || '';
            var accountName = button.getAttribute('data-account-name') || '';
            var accountBalance = button.getAttribute('data-account-balance') || '';
            var amount = button.getAttribute('data-amount') || '';
            var transTime = button.getAttribute('data-trans-time') || '';
            var remark = button.getAttribute('data-remark') || '';
            var attachmentUrl = button.getAttribute('data-attachment-url') || '';

            var typeLabel = type === 'income' ? '收入' : '支出';
            document.getElementById('txDetailType').textContent = typeLabel;

            var catItem = categoryName || '';
            if (itemName) {
                catItem += (catItem ? ' · ' : '') + itemName;
            }
            document.getElementById('txDetailCategoryItem').textContent = catItem || '-';

            document.getElementById('txDetailAccount').textContent = accountName || '-';

            document.getElementById('txDetailAmount').textContent = (type === 'income' ? '+' : '-') + (amount || '0.00');

            var balEl = document.getElementById('txDetailAccountBalance');
            if (accountBalance !== '') {
                balEl.textContent = accountBalance;
            } else {
                balEl.textContent = '—';
            }

            document.getElementById('txDetailTransTime').textContent = transTime || '-';
            document.getElementById('txDetailRemark').textContent = remark || '';

            var attachWrapper = document.getElementById('txDetailAttachmentWrapper');
            if (attachWrapper) {
                attachWrapper.innerHTML = '';
                if (attachmentUrl) {
                    var img = document.createElement('img');
                    img.src = attachmentUrl;
                    img.alt = '凭证';
                    img.className = 'attachment-thumb';
                    img.style.maxWidth = '120px';
                    img.style.maxHeight = '120px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '4px';
                    img.style.cursor = 'zoom-in';
                    img.setAttribute('data-attachment-preview', attachmentUrl);
                    attachWrapper.appendChild(img);
                } else {
                    var span = document.createElement('span');
                    span.className = 'text-muted small';
                    span.textContent = '无';
                    attachWrapper.appendChild(span);
                }
            }
        });
    })();

    // 列表中凭证缩略图点击预览大图
    (function () {
        var previewModalEl = document.getElementById('attachmentPreviewModal');
        var previewImg = document.getElementById('attachmentPreviewImage');
        if (!previewModalEl || !previewImg || !window.bootstrap) {
            return;
        }

        var bsModal = new bootstrap.Modal(previewModalEl);

        document.body.addEventListener('click', function (e) {
            var target = e.target || e.srcElement;
            if (!target || !target.classList || !target.classList.contains('attachment-thumb')) {
                return;
            }

            var url = target.getAttribute('data-attachment-preview') || target.getAttribute('src');
            if (!url) {
                return;
            }

            previewImg.src = url;
            bsModal.show();
        });
    })();

    // 过滤表单：类型 / 分类 / 项目联动 + 图标预览
    (function () {
        var typeSelect = document.querySelector('form[action="/public/index.php"] select[name="type"]');
        var categorySelect = document.querySelector('form[action="/public/index.php"] select[name="category_id"]');
        var itemSelect = document.querySelector('form[action="/public/index.php"] select[name="item_id"]');
        var accountSelect = document.querySelector('form[action="/public/index.php"] select[name="account_id"]');
        var categoryIconPreview = document.getElementById('txFilterCategoryIconPreview');
        var itemIconPreview = document.getElementById('txFilterItemIconPreview');
        var accountIconPreview = document.getElementById('txFilterAccountIconPreview');

        if (!typeSelect || !categorySelect || !itemSelect || !accountSelect) {
            return;
        }

        var allCategoryOptions = Array.prototype.slice.call(categorySelect.options);
        var allItemOptions = Array.prototype.slice.call(itemSelect.options);

        function updateIconPreview(selectEl, previewEl) {
            if (!selectEl || !previewEl) return;
            var opt = selectEl.options[selectEl.selectedIndex];
            if (!opt || !opt.value) {
                previewEl.textContent = '';
                return;
            }
            var type = opt.getAttribute('data-icon-type') || '';
            var value = opt.getAttribute('data-icon-value') || '';
            if (!type || !value) {
                previewEl.textContent = '';
                return;
            }
            if (type === 'file') {
                previewEl.innerHTML = '<img src="/uploads/' + value + '" alt="图标" style="width:18px;height:18px;object-fit:cover;" class="rounded">';
            } else if (type === 'svg') {
                previewEl.innerHTML = '<span class="tx-icon d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">' + value + '</span>';
            } else {
                previewEl.textContent = '';
            }
        }

        function filterCategories() {
            var t = typeSelect.value;
            allCategoryOptions.forEach(function (opt) {
                if (!opt.value) return;
                var type = opt.getAttribute('data-type') || '';
                var match = !t || type === t;
                opt.hidden = !match;
                opt.disabled = !match;
            });
        }

        function filterItems() {
            var t = typeSelect.value;
            var cid = categorySelect.value;
            allItemOptions.forEach(function (opt) {
                if (!opt.value) return;
                var itemType = opt.getAttribute('data-type') || '';
                var itemCategoryId = opt.getAttribute('data-category-id') || '';
                var match;
                if (!t && !cid) {
                    match = true; // 全部
                } else if (cid) {
                    match = itemCategoryId === cid;
                } else { // 只选了类型
                    match = itemType === t;
                }
                opt.hidden = !match;
                opt.disabled = !match;
            });
        }

        typeSelect.addEventListener('change', function () {
            filterCategories();
            categorySelect.value = '';
            filterItems();
            updateIconPreview(categorySelect, categoryIconPreview);
            updateIconPreview(itemSelect, itemIconPreview);
        });

        categorySelect.addEventListener('change', function () {
            filterItems();
            updateIconPreview(categorySelect, categoryIconPreview);
            updateIconPreview(itemSelect, itemIconPreview);
        });

        itemSelect.addEventListener('change', function () {
            updateIconPreview(itemSelect, itemIconPreview);
        });

        accountSelect.addEventListener('change', function () {
            updateIconPreview(accountSelect, accountIconPreview);
        });

        // 初始化
        filterCategories();
        filterItems();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
        updateIconPreview(accountSelect, accountIconPreview);
    })();

    // 明细列表编辑弹窗
    var modalEl = document.getElementById('modalTxEditFromList');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        var id = button.getAttribute('data-id');
        var type = button.getAttribute('data-type') || 'expense';
        var categoryId = button.getAttribute('data-category-id') || '';
        var itemId = button.getAttribute('data-item-id') || '';
        var fromAccountId = button.getAttribute('data-from-account-id') || '';
        var toAccountId = button.getAttribute('data-to-account-id') || '';
        var amount = button.getAttribute('data-amount') || '';
        var transTime = button.getAttribute('data-trans-time') || '';
        var remark = button.getAttribute('data-remark') || '';
        var attachmentPath = button.getAttribute('data-attachment-path') || '';

        var form = document.getElementById('txEditFormFromList');
        if (form && id) {
            form.action = '/public/index.php?route=transaction-edit&id=' + encodeURIComponent(id);
        }

        var typeSelect = document.getElementById('txEditTypeList');
        var categorySelect = document.getElementById('txEditCategoryList');
        var itemSelect = document.getElementById('txEditItemList');
        var fromSelect = document.getElementById('txEditFromAccountList');
        var toSelect = document.getElementById('txEditToAccountList');
        var amountInput = document.getElementById('txEditAmountList');
        var timeInput = document.getElementById('txEditTransTimeList');
        var remarkInput = document.getElementById('txEditRemarkList');
        var fromGroup = modalEl.querySelector('[data-role="tx-edit-from-account-group-list"]');
        var toGroup = modalEl.querySelector('[data-role="tx-edit-to-account-group-list"]');
        var attachmentWrapper = document.getElementById('txEditAttachmentPreviewWrapperList');
        var attachmentPlaceholder = document.getElementById('txEditAttachmentPlaceholderList');
        var removeAttachmentCheckbox = document.getElementById('txEditRemoveAttachmentList');

        if (typeSelect) typeSelect.value = type;
        if (categorySelect) categorySelect.value = categoryId;
        if (itemSelect) itemSelect.value = itemId;
        if (fromSelect) fromSelect.value = fromAccountId;
        if (toSelect) toSelect.value = toAccountId;
        if (amountInput) amountInput.value = amount;
        if (timeInput) {
            if (transTime && transTime.indexOf('T') === -1) {
                timeInput.value = transTime.replace(' ', 'T');
            } else {
                timeInput.value = transTime;
            }
        }
        if (remarkInput) remarkInput.value = remark;

        function updateAccountVisibility() {
            var t = typeSelect ? typeSelect.value : 'expense';
            if (!fromGroup || !toGroup) return;
            if (t === 'expense') {
                fromGroup.classList.remove('d-none');
                toGroup.classList.add('d-none');
                if (toSelect) toSelect.value = '';
            } else if (t === 'income') {
                fromGroup.classList.add('d-none');
                toGroup.classList.remove('d-none');
                if (fromSelect) fromSelect.value = '';
            } else {
                fromGroup.classList.remove('d-none');
                toGroup.classList.remove('d-none');
            }
        }

        if (typeSelect) {
            typeSelect.onchange = updateAccountVisibility;
        }
        updateAccountVisibility();

        if (categorySelect && itemSelect) {
            var allItemOptions = Array.prototype.slice.call(itemSelect.querySelectorAll('option'));
            function filterItems() {
                var cid = categorySelect.value;
                allItemOptions.forEach(function (opt) {
                    if (!opt.value) return;
                    var c = opt.getAttribute('data-category');
                    var match = !cid || c === cid;
                    opt.hidden = !match;
                    opt.disabled = !match;
                });
            }
            categorySelect.onchange = filterItems;
            filterItems();
        }

        // 处理当前凭证预览
        if (attachmentWrapper && attachmentPlaceholder) {
            attachmentWrapper.innerHTML = '';
            if (attachmentPath) {
                var img = document.createElement('img');
                img.src = '/uploads/' + attachmentPath;
                img.alt = '凭证';
                img.className = 'attachment-thumb';
                img.setAttribute('data-attachment-preview', '/uploads/' + attachmentPath);
                attachmentWrapper.appendChild(img);
            } else {
                attachmentWrapper.appendChild(attachmentPlaceholder);
                attachmentPlaceholder.textContent = '当前无凭证';
            }
        }

        if (removeAttachmentCheckbox) {
            removeAttachmentCheckbox.checked = false;
            removeAttachmentCheckbox.disabled = !attachmentPath;
        }
    });
});

// 单条删除：勾选对应复选框并提交批量删除表单
function deleteSingleTransaction(id) {
    if (!window.confirm('确定删除该记录吗？删除后将同步回滚账户余额。')) {
        return;
    }
    var form = document.getElementById('txBulkForm');
    if (!form) return;

    var checkboxes = form.querySelectorAll('input.tx-check');
    checkboxes.forEach(function (c) { c.checked = false; });

    var selector = 'input.tx-check[value="' + id + '"]';
    var cb = form.querySelector(selector);
    if (!cb) return;

    cb.checked = true;
    form.submit();
}
</script>
