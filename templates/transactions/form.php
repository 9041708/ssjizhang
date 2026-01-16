<?php
$tx = $transaction ?? null;
$type = $tx['type'] ?? ($_POST['type'] ?? 'expense');
$categoryId = $tx['category_id'] ?? ($_POST['category_id'] ?? '');
$itemId = $tx['item_id'] ?? ($_POST['item_id'] ?? '');
$fromAccountId = $tx['from_account_id'] ?? ($_POST['from_account_id'] ?? '');
$toAccountId = $tx['to_account_id'] ?? ($_POST['to_account_id'] ?? '');
$amount = $tx['amount'] ?? ($_POST['amount'] ?? '');
$remark = $tx['remark'] ?? ($_POST['remark'] ?? '');
$transTime = $tx['trans_time'] ?? ($_POST['trans_time'] ?? date('Y-m-d H:i'));
// 转换为 datetime-local 控件可用的值：YYYY-MM-DDTHH:MM（只到分，不显示秒）
if (!empty($transTime)) {
    if (strpos($transTime, 'T') === false) {
        // 兼容旧格式 "Y-m-d H:i:s" 或 "Y-m-d H:i"
        $normalized = substr($transTime, 0, 16);
        $transTimeInput = str_replace(' ', 'T', $normalized);
    } else {
        $transTimeInput = substr($transTime, 0, 16);
    }
} else {
    $transTimeInput = date('Y-m-d\TH:i');
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><?= $mode === 'edit' ? '编辑记账' : '新增记账' ?></h2>
    <a href="/public/index.php?route=transactions" class="btn btn-sm btn-outline-secondary">返回明细</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3 tx-form-card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3 tx-form-grid">
    <div class="col-12 col-md-6">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">类型</label>
            <div class="tx-form-control-wrap">
                <select name="type" class="form-select form-select-sm js-icon-select">
                    <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>支出</option>
                    <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>收入</option>
                </select>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">分类</label>
            <div class="tx-form-control-wrap">
                <select name="category_id" class="form-select form-select-sm tx-select tx-category-select js-icon-select" required>
                    <option value="">请选择</option>
                    <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"
                                    data-type="<?= htmlspecialchars($c['type']) ?>"
                                    data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                                    data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>"
                                    <?= (string)$categoryId === (string)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                    <div class="small mt-1" id="txFormCategoryIconPreview"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">项目</label>
            <div class="tx-form-control-wrap">
                <select name="item_id" class="form-select form-select-sm js-icon-select">
                    <option value="">不选项目</option>
                    <?php foreach ($items as $i): ?>
                            <?php
                            $itemCategoryType = null;
                            foreach ($categories as $cTmp) {
                                if ((int)$cTmp['id'] === (int)$i['category_id']) {
                                    $itemCategoryType = $cTmp['type'];
                                    break;
                                }
                            }
                            ?>
                            <option value="<?= (int)$i['id'] ?>"
                                    data-category="<?= (int)$i['category_id'] ?>"
                                    data-type="<?= htmlspecialchars($itemCategoryType ?? '', ENT_QUOTES) ?>"
                                    data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                                    data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>"
                                    <?= (string)$itemId === (string)$i['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($i['name']) ?>
                            </option>
                    <?php endforeach; ?>
                </select>
                    <div class="small mt-1" id="txFormItemIconPreview"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">金额</label>
            <div class="tx-form-control-wrap">
                <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm tx-amount-input" value="<?= htmlspecialchars((string)$amount) ?>" required>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6" data-role="from-account-group">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">支出账户</label>
            <div class="tx-form-control-wrap">
                <select name="from_account_id" class="form-select form-select-sm js-icon-select">
                    <option value="">不选择</option>
                    <?php foreach ($accounts as $a): ?>
                        <?php
                        $balance = (float)$a['current_balance'];
                        $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                        ?>
                        <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                                data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>"
                                <?= (string)$fromAccountId === (string)$a['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small mt-1" id="txFormFromAccountIconPreview"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6" data-role="to-account-group">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">收入账户</label>
            <div class="tx-form-control-wrap">
                <select name="to_account_id" class="form-select form-select-sm js-icon-select">
                    <option value="">不选择</option>
                    <?php foreach ($accounts as $a): ?>
                        <?php
                        $balance = (float)$a['current_balance'];
                        $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                        ?>
                        <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                                data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>"
                                <?= (string)$toAccountId === (string)$a['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="small mt-1" id="txFormToAccountIconPreview"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">记账时间</label>
            <div class="tx-form-control-wrap">
                <input type="datetime-local" name="trans_time" class="form-control form-control-sm" step="60" value="<?= htmlspecialchars($transTimeInput) ?>">
            </div>
        </div>
        <div class="form-text small ms-md-5 ms-3 mt-1">默认当前时间，可通过日期时间选择器或手动调整。</div>
    </div>

    <div class="col-12">
        <div class="tx-form-row">
            <label class="form-label small mb-0 tx-form-label">备注</label>
            <div class="tx-form-control-wrap">
                <textarea name="remark" rows="2" class="form-control form-control-sm"><?= htmlspecialchars((string)$remark) ?></textarea>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="tx-form-row align-items-center">
            <label class="form-label small mb-0 tx-form-label">图片</label>
            <div class="tx-form-control-wrap">
                <input type="file" name="attachment" accept="image/*" class="form-control form-control-sm">
                <?php if (!empty($tx['attachment_path'])): ?>
                    <div class="form-text small mt-1">
                        已上传：<a href="/uploads/<?= htmlspecialchars($tx['attachment_path']) ?>" target="_blank">查看当前凭证</a>
                    </div>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="removeAttachment">
                        <label class="form-check-label small" for="removeAttachment">删除当前图片</label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">保存</button>
    </div>
    </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.querySelector('select[name="type"]');
    const categorySelect = document.querySelector('select[name="category_id"]');
    const itemSelect = document.querySelector('select[name="item_id"]');
    const fromGroup = document.querySelector('[data-role="from-account-group"]');
    const toGroup = document.querySelector('[data-role="to-account-group"]');
    const categoryIconPreview = document.getElementById('txFormCategoryIconPreview');
    const itemIconPreview = document.getElementById('txFormItemIconPreview');
    const fromAccountIconPreview = document.getElementById('txFormFromAccountIconPreview');
    const toAccountIconPreview = document.getElementById('txFormToAccountIconPreview');

    const initialType = '<?= htmlspecialchars($type, ENT_QUOTES) ?>';
    const initialCategoryId = '<?= htmlspecialchars((string)$categoryId, ENT_QUOTES) ?>';
    const initialItemId = '<?= htmlspecialchars((string)$itemId, ENT_QUOTES) ?>';

    function filterCategories() {
        const currentType = typeSelect.value;
        let hasSelected = false;
        Array.from(categorySelect.options).forEach(function (opt) {
            if (!opt.value) return;
            const t = opt.getAttribute('data-type');
            const match = !currentType || t === currentType;
            opt.hidden = !match;
            if (!match && opt.selected) {
                opt.selected = false;
            }
            if (match && !hasSelected && (opt.value === initialCategoryId || !initialCategoryId)) {
                hasSelected = true;
            }
        });
        if (categorySelect && categorySelect._decorateChoices) {
            categorySelect._decorateChoices();
        }
    }

    function filterItems() {
        const cid = categorySelect.value;
        Array.from(itemSelect.options).forEach(function (opt) {
            if (!opt.value) return;
            const c = opt.getAttribute('data-category');
            const match = !cid || c === cid;
            opt.hidden = !match;
            opt.disabled = !match;
            if (!match && opt.selected) {
                opt.selected = false;
            }
        });
        if (itemSelect && itemSelect._decorateChoices) {
            itemSelect._decorateChoices();
        }
    }

    function updateIconPreview(selectEl, previewEl) {
        if (!selectEl || !previewEl) return;
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) {
            previewEl.textContent = '';
            return;
        }
        const type = opt.getAttribute('data-icon-type') || '';
        const value = opt.getAttribute('data-icon-value') || '';
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

    function updateAccountVisibility() {
        const t = typeSelect.value;
        if (t === 'expense') {
            fromGroup.classList.remove('d-none');
            toGroup.classList.add('d-none');
            const toSelect = toGroup.querySelector('select');
            if (toSelect) toSelect.value = '';
        } else if (t === 'income') {
            fromGroup.classList.add('d-none');
            toGroup.classList.remove('d-none');
            const fromSelect = fromGroup.querySelector('select');
            if (fromSelect) fromSelect.value = '';
        } else {
            fromGroup.classList.remove('d-none');
            toGroup.classList.remove('d-none');
        }
    }

    typeSelect.addEventListener('change', function () {
        filterCategories();
        categorySelect.value = '';
        filterItems();
        updateAccountVisibility();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
        updateIconPreview(document.querySelector('select[name="from_account_id"]'), fromAccountIconPreview);
        updateIconPreview(document.querySelector('select[name="to_account_id"]'), toAccountIconPreview);
    });

    categorySelect.addEventListener('change', function () {
        filterItems();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
    });

    itemSelect.addEventListener('change', function () {
        updateIconPreview(itemSelect, itemIconPreview);
    });

    const fromAccountSelect = document.querySelector('select[name="from_account_id"]');
    const toAccountSelect = document.querySelector('select[name="to_account_id"]');
    if (fromAccountSelect) {
        fromAccountSelect.addEventListener('change', function () {
            updateIconPreview(fromAccountSelect, fromAccountIconPreview);
        });
    }
    if (toAccountSelect) {
        toAccountSelect.addEventListener('change', function () {
            updateIconPreview(toAccountSelect, toAccountIconPreview);
        });
    }

    // 初始化
    filterCategories();
    filterItems();
    updateAccountVisibility();
    updateIconPreview(categorySelect, categoryIconPreview);
    updateIconPreview(itemSelect, itemIconPreview);
    if (fromAccountSelect) updateIconPreview(fromAccountSelect, fromAccountIconPreview);
    if (toAccountSelect) updateIconPreview(toAccountSelect, toAccountIconPreview);

    // 使用 Choices.js 为带图标的下拉增强 UI，并在下拉项中显示图标
    if (window.Choices) {
        function enhanceIconSelect(select) {
            if (!select) return null;

            var instance = new Choices(select, {
                searchEnabled: true,
                shouldSort: false,
                position: 'bottom',
                itemSelectText: '',
                allowHTML: true,
            });

            select._choicesInstance = instance;

            function decorateChoices() {
                var root = select.closest('.choices');
                if (!root) return;
                var dropdown = root.querySelector('.choices__list--dropdown');
                if (!dropdown) return;
                var backing = instance.passedElement && instance.passedElement.element ? instance.passedElement.element : select;
                dropdown.querySelectorAll('.choices__item--choice[data-value]').forEach(function (choiceEl) {
                    var value = choiceEl.getAttribute('data-value');
                    if (!value) return;
                    var opt = backing.querySelector('option[value="' + value.replace(/"/g, '\"') + '"]');
                    if (!opt) return;
                    if (opt.hidden) {
                        choiceEl.style.display = 'none';
                    } else {
                        choiceEl.style.display = '';
                    }
                    var iconType = opt.getAttribute('data-icon-type') || '';
                    var iconValue = opt.getAttribute('data-icon-value') || '';
                    var label = choiceEl.textContent || '';
                    var iconHtml = '';
                    if (iconType && iconValue) {
                        if (iconType === 'file') {
                            iconHtml = '<img src="/uploads/' + iconValue + '" alt="图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">';
                        } else if (iconType === 'svg') {
                            iconHtml = '<span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">' + iconValue + '</span>';
                        }
                    }
                    choiceEl.innerHTML = iconHtml + label;
                });
            }

            select._decorateChoices = decorateChoices;

            decorateChoices();

            select.addEventListener('showDropdown', function () {
                decorateChoices();
            });

            return instance;
        }

        document.querySelectorAll('select.js-icon-select').forEach(function (el) {
            enhanceIconSelect(el);
        });
    }
});
</script>

<?php if ($mode === 'create'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h3 class="h6 mb-3">今日记账明细</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
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
                    <?php if (empty($todayTransactions)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted small">今日暂无记账记录</td>
                        </tr>
                    <?php else: ?>
                    <?php foreach ($todayTransactions as $t): ?>
                        <tr>
                            <td>
                                <?php if ($t['type'] === 'income'): ?>
                                    <span class="text-danger">收入</span>
                                <?php else: ?>
                                    <span class="text-success">支出</span>
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
                            <td class="text-end">¥ <?= number_format($t['amount'], 2) ?></td>
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
                                <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalTxEdit"
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
                                <form method="post" action="/public/index.php?route=transaction-delete" class="d-inline" onsubmit="return confirm('确定删除该记录吗？删除后将同步回滚账户余额。');">
                                    <input type="hidden" name="ids[]" value="<?= (int)$t['id'] ?>">
                                    <input type="hidden" name="from" value="create">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                </form>
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

        <!-- 编辑记账弹窗（用于今日记账明细） -->
        <div class="modal fade" id="modalTxEdit" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑今日记账</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="txEditForm" method="post" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">类型</label>
                                    <select name="type" class="form-select form-select-sm js-icon-select" id="txEditType">
                                        <option value="expense">支出</option>
                                        <option value="income">收入</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">分类</label>
                                    <select name="category_id" class="form-select form-select-sm tx-select tx-category-select js-icon-select" id="txEditCategory">
                                        <option value="">请选择</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= (int)$c['id'] ?>"
                                                    data-type="<?= htmlspecialchars($c['type']) ?>"
                                                    data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditCategoryIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">项目</label>
                                    <select name="item_id" class="form-select form-select-sm js-icon-select" id="txEditItem">
                                        <option value="">不选项目</option>
                                        <?php foreach ($items as $i): ?>
                                            <option value="<?= (int)$i['id'] ?>"
                                                    data-category="<?= (int)$i['category_id'] ?>"
                                                    data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <?= htmlspecialchars($i['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditItemIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label small">金额</label>
                                    <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm tx-amount-input" id="txEditAmount">
                                </div>

                                <div class="col-12 col-md-4" data-role="tx-edit-from-account-group">
                                    <label class="form-label small">支出账户（支出/转出）</label>
                                    <select name="from_account_id" class="form-select form-select-sm js-icon-select" id="txEditFromAccount">
                                        <option value="">不选择</option>
                                        <?php foreach ($accounts as $a): ?>
                                            <?php
                                            $balance = (float)$a['current_balance'];
                                            $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                                            ?>
                                            <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                                                    data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditFromAccountIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-4" data-role="tx-edit-to-account-group">
                                    <label class="form-label small">收入账户（收入/转入）</label>
                                    <select name="to_account_id" class="form-select form-select-sm js-icon-select" id="txEditToAccount">
                                        <option value="">不选择</option>
                                        <?php foreach ($accounts as $a): ?>
                                            <?php
                                            $balance = (float)$a['current_balance'];
                                            $cls = $balance < 0 ? 'balance-neg' : ($balance > 0 ? 'balance-pos' : 'balance-zero');
                                            ?>
                                            <option value="<?= (int)$a['id'] ?>" class="<?= $cls ?>"
                                                    data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                                    data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>">
                                                <?= htmlspecialchars('[' . $a['group_name'] . '] ' . $a['name'] . '    ¥ ' . number_format($balance, 2)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small mt-1" id="txEditToAccountIconPreview"></div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small">记账时间</label>
                                    <input type="datetime-local" name="trans_time" class="form-control form-control-sm" id="txEditTransTime" step="60">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small">备注</label>
                                    <textarea name="remark" rows="2" class="form-control form-control-sm" id="txEditRemark"></textarea>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small">图片凭证（≤10MB）</label>
                                    <input type="file" name="attachment" accept="image/*" class="form-control form-control-sm" id="txEditAttachmentInput">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_attachment" value="1" id="txEditRemoveAttachment">
                                        <label class="form-check-label small" for="txEditRemoveAttachment">删除当前图片</label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 d-flex align-items-end" id="txEditAttachmentPreviewWrapper" style="min-height:2.5rem;">
                                    <div class="small text-muted" id="txEditAttachmentPlaceholder">当前无凭证</div>
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
            var modalEl = document.getElementById('modalTxEdit');
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

                var form = document.getElementById('txEditForm');
                if (form && id) {
                    form.action = '/public/index.php?route=transaction-edit&id=' + encodeURIComponent(id) + '&from=create';
                }

                var typeSelect = document.getElementById('txEditType');
                var categorySelect = document.getElementById('txEditCategory');
                var itemSelect = document.getElementById('txEditItem');
                var fromSelect = document.getElementById('txEditFromAccount');
                var toSelect = document.getElementById('txEditToAccount');
                var amountInput = document.getElementById('txEditAmount');
                var timeInput = document.getElementById('txEditTransTime');
                var remarkInput = document.getElementById('txEditRemark');
                var fromGroup = modalEl.querySelector('[data-role="tx-edit-from-account-group"]');
                var toGroup = modalEl.querySelector('[data-role="tx-edit-to-account-group"]');
                var attachmentWrapper = document.getElementById('txEditAttachmentPreviewWrapper');
                var attachmentPlaceholder = document.getElementById('txEditAttachmentPlaceholder');
                var removeAttachmentCheckbox = document.getElementById('txEditRemoveAttachment');
                var categoryIconPreview = document.getElementById('txEditCategoryIconPreview');
                var itemIconPreview = document.getElementById('txEditItemIconPreview');
                var fromAccountIconPreview = document.getElementById('txEditFromAccountIconPreview');
                var toAccountIconPreview = document.getElementById('txEditToAccountIconPreview');

                if (typeSelect) {
                    typeSelect.value = type;
                    if (typeSelect._choicesInstance && typeof typeSelect._choicesInstance.setChoiceByValue === 'function') {
                        typeSelect._choicesInstance.setChoiceByValue(type);
                    }
                }
                if (categorySelect) {
                    categorySelect.value = categoryId;
                    if (categorySelect._choicesInstance && typeof categorySelect._choicesInstance.setChoiceByValue === 'function') {
                        categorySelect._choicesInstance.setChoiceByValue(categoryId);
                    }
                }
                if (itemSelect) {
                    itemSelect.value = itemId;
                    if (itemSelect._choicesInstance && typeof itemSelect._choicesInstance.setChoiceByValue === 'function') {
                        itemSelect._choicesInstance.setChoiceByValue(itemId);
                    }
                }
                if (fromSelect) {
                    fromSelect.value = fromAccountId;
                    if (fromSelect._choicesInstance && typeof fromSelect._choicesInstance.setChoiceByValue === 'function') {
                        fromSelect._choicesInstance.setChoiceByValue(fromAccountId);
                    }
                }
                if (toSelect) {
                    toSelect.value = toAccountId;
                    if (toSelect._choicesInstance && typeof toSelect._choicesInstance.setChoiceByValue === 'function') {
                        toSelect._choicesInstance.setChoiceByValue(toAccountId);
                    }
                }
                if (amountInput) amountInput.value = amount;
                if (timeInput) {
                    if (transTime && transTime.indexOf('T') === -1) {
                        timeInput.value = transTime.replace(' ', 'T');
                    } else {
                        timeInput.value = transTime;
                    }
                }
                if (remarkInput) remarkInput.value = remark;

                function updateIconPreview(selectEl, previewEl) {
                    if (!selectEl || !previewEl) return;
                    var opt = selectEl.options[selectEl.selectedIndex];
                    if (!opt || !opt.value) {
                        previewEl.textContent = '';
                        return;
                    }
                    var itype = opt.getAttribute('data-icon-type') || '';
                    var ivalue = opt.getAttribute('data-icon-value') || '';
                    if (!itype || !ivalue) {
                        previewEl.textContent = '';
                        return;
                    }
                    if (itype === 'file') {
                        previewEl.innerHTML = '<img src="/uploads/' + ivalue + '" alt="图标" style="width:18px;height:18px;object-fit:cover;" class="rounded">';
                    } else if (itype === 'svg') {
                        previewEl.innerHTML = '<span class="tx-icon d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">' + ivalue + '</span>';
                    } else {
                        previewEl.textContent = '';
                    }
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
                            if (itemSelect && itemSelect._decorateChoices) {
                                itemSelect._decorateChoices();
                            }
                    }
                    categorySelect.onchange = function () {
                        filterItems();
                        updateIconPreview(categorySelect, categoryIconPreview);
                        updateIconPreview(itemSelect, itemIconPreview);
                    };
                    filterItems();
                }

                if (itemSelect) {
                    itemSelect.onchange = function () {
                        updateIconPreview(itemSelect, itemIconPreview);
                    };
                }

                if (fromSelect) {
                    fromSelect.onchange = function () {
                        updateIconPreview(fromSelect, fromAccountIconPreview);
                    };
                }

                if (toSelect) {
                    toSelect.onchange = function () {
                        updateIconPreview(toSelect, toAccountIconPreview);
                    };
                }

                // 初始化图标预览
                updateIconPreview(categorySelect, categoryIconPreview);
                updateIconPreview(itemSelect, itemIconPreview);
                updateIconPreview(fromSelect, fromAccountIconPreview);
                updateIconPreview(toSelect, toAccountIconPreview);
            });
        });
        </script>
