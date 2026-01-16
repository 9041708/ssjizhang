<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-1">预算管理</h2>
        <div class="small text-muted">为不同分类 / 项目设置月度预算，帮助控制支出。</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <form class="d-flex align-items-center" method="get">
            <input type="hidden" name="route" value="budget">
            <label class="me-2 small text-muted">年月</label>
            <input type="month" name="ym" class="form-control form-control-sm" value="<?= sprintf('%04d-%02d', (int)$year, (int)$month) ?>">
            <button class="btn btn-sm btn-outline-primary ms-2" type="submit">切换</button>
        </form>
        <form method="post" onsubmit="return confirm('确定将去年同月的预算复制到当前月份吗？\n\n说明：\n1. 仅复制去年同月已设置的预算；\n2. 若本月已存在相同 类型/分类/项目 的预算，将被覆盖。');">
            <button type="submit" name="copy_prev" value="1" class="btn btn-sm btn-outline-secondary">复制去年同月预算</button>
        </form>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalBudgetCreate">新增预算</button>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">当月总预算（支出）</div>
                <div class="fs-4 fw-semibold text-primary">¥ <?= number_format($totalBudgetExpense, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">当月已支出（按记账统计）</div>
                <div class="fs-4 fw-semibold text-danger">¥ <?= number_format($totalUsedExpense, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">预算剩余</div>
                <div class="fs-4 fw-semibold text-success">¥ <?= number_format($totalBudgetExpense - $totalUsedExpense, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">去年同月总预算（支出）</div>
                <div class="fs-5 fw-semibold text-primary">¥ <?= number_format($totalPrevBudgetExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">去年同月已支出（按记账统计）</div>
                <div class="fs-5 fw-semibold text-danger">¥ <?= number_format($totalPrevUsedExpense ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">去年同月预算剩余</div>
                <div class="fs-5 fw-semibold text-success">¥ <?= number_format(($totalPrevBudgetExpense ?? 0) - ($totalPrevUsedExpense ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 新增预算弹窗 -->
<div class="modal fade" id="modalBudgetCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增预算</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label small">类型</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="expense">支出</option>
                            <option value="income">收入</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">分类</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">全部分类</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"
                                        data-type="<?= htmlspecialchars($c['type'], ENT_QUOTES) ?>"
                                        data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                                        data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>">
                                    <?= htmlspecialchars('[' . ($c['type'] === 'expense' ? '支出' : '收入') . '] ' . $c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small mt-1" id="budgetCreateCategoryIconPreview"></div>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label small">项目</label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">全部项目</option>
                            <?php foreach ($items as $i): ?>
                                <option value="<?= (int)$i['id'] ?>"
                                        data-category-id="<?= (int)$i['category_id'] ?>"
                                        data-icon-type="<?= htmlspecialchars($i['icon_type'] ?? '', ENT_QUOTES) ?>"
                                        data-icon-value="<?= htmlspecialchars($i['icon_value'] ?? '', ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($i['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small mt-1" id="budgetCreateItemIconPreview"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small d-flex justify-content-between align-items-center">
                            <span>预算金额</span>
                            <button type="button" class="btn btn-link btn-sm p-0" id="btnCopyPrevBudgetSingle">复制去年同月</button>
                        </label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-6 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 编辑预算弹窗 -->
<div class="modal fade" id="modalBudgetEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑预算</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end" id="formBudgetEdit">
                    <input type="hidden" name="edit_id" value="">
                    <input type="hidden" name="type" value="">
                    <input type="hidden" name="category_id" value="">
                    <input type="hidden" name="item_id" value="">

                    <div class="col-12 col-md-4">
                        <label class="form-label small">类型</label>
                        <div class="form-control form-control-sm bg-light" id="txtBudgetEditType"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">分类</label>
                        <div class="form-control form-control-sm bg-light" id="txtBudgetEditCategory"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small">项目</label>
                        <div class="form-control form-control-sm bg-light" id="txtBudgetEditItem"></div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small d-flex justify-content-between align-items-center">
                            <span>预算金额</span>
                            <button type="button" class="btn btn-link btn-sm p-0" id="btnCopyPrevBudgetSingleEdit">复制去年同月</button>
                        </label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-6 d-grid">
                        <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var CURRENT_YEAR = <?= (int)$year ?>;
    var CURRENT_MONTH = <?= (int)$month ?>;
    var modalEl = document.getElementById('modalBudgetCreate');
    if (!modalEl) return;

    var typeSelect = modalEl.querySelector('select[name="type"]');
    var categorySelect = modalEl.querySelector('select[name="category_id"]');
    var itemSelect = modalEl.querySelector('select[name="item_id"]');
    var categoryIconPreview = document.getElementById('budgetCreateCategoryIconPreview');
    var itemIconPreview = document.getElementById('budgetCreateItemIconPreview');
    var amountInput = modalEl.querySelector('input[name="amount"]');
    var btnCopyPrevSingle = modalEl.querySelector('#btnCopyPrevBudgetSingle');

    if (!typeSelect || !categorySelect || !itemSelect || !amountInput || !btnCopyPrevSingle) return;

    var defaultCategoryOption = categorySelect.querySelector('option[value=""]');
    var defaultItemOption = itemSelect.querySelector('option[value=""]');

    var allCategoryOptions = [];
    categorySelect.querySelectorAll('option').forEach(function (opt) {
        if (opt.value === '') return;
        allCategoryOptions.push({
            value: opt.value,
            label: opt.textContent,
            type: opt.getAttribute('data-type') || '',
            iconType: opt.getAttribute('data-icon-type') || '',
            iconValue: opt.getAttribute('data-icon-value') || ''
        });
    });

    var allItemOptions = [];
    itemSelect.querySelectorAll('option').forEach(function (opt) {
        if (opt.value === '') return;
        allItemOptions.push({
            value: opt.value,
            label: opt.textContent,
            categoryId: opt.getAttribute('data-category-id') || '',
            iconType: opt.getAttribute('data-icon-type') || '',
            iconValue: opt.getAttribute('data-icon-value') || ''
        });
    });

    function rebuildCategoryOptions() {
        var currentType = typeSelect.value;
        var prevSelected = categorySelect.value;

        categorySelect.innerHTML = '';
        if (defaultCategoryOption) {
            categorySelect.appendChild(defaultCategoryOption.cloneNode(true));
        }

        allCategoryOptions.forEach(function (o) {
            if (!currentType || o.type === currentType) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                opt.setAttribute('data-type', o.type);
                if (o.iconType) opt.setAttribute('data-icon-type', o.iconType);
                if (o.iconValue) opt.setAttribute('data-icon-value', o.iconValue);
                categorySelect.appendChild(opt);
            }
        });

        // 类型切换后默认不保留原分类，重置为全部
        categorySelect.value = '';
    }

    function rebuildItemOptions() {
        var currentCategoryId = categorySelect.value;

        itemSelect.innerHTML = '';
        if (defaultItemOption) {
            itemSelect.appendChild(defaultItemOption.cloneNode(true));
        }

        if (!currentCategoryId) {
            return;
        }

        allItemOptions.forEach(function (o) {
            if (o.categoryId === currentCategoryId) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                opt.setAttribute('data-category-id', o.categoryId);
                if (o.iconType) opt.setAttribute('data-icon-type', o.iconType);
                if (o.iconValue) opt.setAttribute('data-icon-value', o.iconValue);
                itemSelect.appendChild(opt);
            }
        });
    }

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

    typeSelect.addEventListener('change', function () {
        rebuildCategoryOptions();
        rebuildItemOptions();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
    });

    categorySelect.addEventListener('change', function () {
        rebuildItemOptions();
        updateIconPreview(categorySelect, categoryIconPreview);
        updateIconPreview(itemSelect, itemIconPreview);
    });

    itemSelect.addEventListener('change', function () {
        updateIconPreview(itemSelect, itemIconPreview);
    });

    // 初始化时根据默认类型（支出）做一遍筛选
    rebuildCategoryOptions();
    rebuildItemOptions();
    updateIconPreview(categorySelect, categoryIconPreview);
    updateIconPreview(itemSelect, itemIconPreview);

    // 单条复制：根据当前选择的类型 / 分类 / 项目，查询去年同月预算
    btnCopyPrevSingle.addEventListener('click', function () {
        var type = typeSelect.value || 'expense';
        var categoryId = categorySelect.value || '';
        var itemId = itemSelect.value || '';

        var url = 'index.php?route=budget'
            + '&action=get_prev_budget'
            + '&year=' + encodeURIComponent(CURRENT_YEAR)
            + '&month=' + encodeURIComponent(CURRENT_MONTH)
            + '&type=' + encodeURIComponent(type)
            + '&category_id=' + encodeURIComponent(categoryId)
            + '&item_id=' + encodeURIComponent(itemId);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data && data.success) {
                amountInput.value = data.amount;
            } else {
                alert((data && data.message) || '暂无去年同月预算，需要手动填写。');
            }
        }).catch(function () {
            alert('查询去年同月预算失败，请稍后重试。');
        });
    });

    // 编辑预算弹窗逻辑
    var editModalEl = document.getElementById('modalBudgetEdit');
    if (editModalEl) {
        var editForm = editModalEl.querySelector('#formBudgetEdit');
        var editAmountInput = editForm.querySelector('input[name="amount"]');
        var editTypeInput = editForm.querySelector('input[name="type"]');
        var editCategoryInput = editForm.querySelector('input[name="category_id"]');
        var editItemInput = editForm.querySelector('input[name="item_id"]');
        var txtEditType = editModalEl.querySelector('#txtBudgetEditType');
        var txtEditCategory = editModalEl.querySelector('#txtBudgetEditCategory');
        var txtEditItem = editModalEl.querySelector('#txtBudgetEditItem');
        var btnCopyPrevSingleEdit = editModalEl.querySelector('#btnCopyPrevBudgetSingleEdit');

        document.querySelectorAll('.btn-budget-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.getAttribute('data-id') || '';
                var type = this.getAttribute('data-type') || 'expense';
                var categoryId = this.getAttribute('data-category-id') || '';
                var itemId = this.getAttribute('data-item-id') || '';
                var typeLabel = this.getAttribute('data-type-label') || '';
                var categoryName = this.getAttribute('data-category-name') || '全部';
                var itemName = this.getAttribute('data-item-name') || '全部';
                var amount = this.getAttribute('data-amount') || '';

                editForm.querySelector('input[name="edit_id"]').value = id;
                editTypeInput.value = type;
                editCategoryInput.value = categoryId;
                editItemInput.value = itemId;

                txtEditType.textContent = typeLabel;
                txtEditCategory.textContent = categoryName;
                txtEditItem.textContent = itemName;
                editAmountInput.value = amount;
            });
        });

        if (btnCopyPrevSingleEdit) {
            btnCopyPrevSingleEdit.addEventListener('click', function () {
                var type = editTypeInput.value || 'expense';
                var categoryId = editCategoryInput.value || '';
                var itemId = editItemInput.value || '';

                var url = 'index.php?route=budget'
                    + '&action=get_prev_budget'
                    + '&year=' + encodeURIComponent(CURRENT_YEAR)
                    + '&month=' + encodeURIComponent(CURRENT_MONTH)
                    + '&type=' + encodeURIComponent(type)
                    + '&category_id=' + encodeURIComponent(categoryId)
                    + '&item_id=' + encodeURIComponent(itemId);

                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (res) {
                    return res.json();
                }).then(function (data) {
                    if (data && data.success) {
                        editAmountInput.value = data.amount;
                    } else {
                        alert((data && data.message) || '暂无去年同月预算，需要手动填写。');
                    }
                }).catch(function () {
                    alert('查询去年同月预算失败，请稍后重试。');
                });
            });
        }
    }
});
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h6 mb-3">本月预算列表</h3>
        <div class="table-responsive">
                <table class="table table-sm align-middle table-accounts">
                <thead class="table-light">
                <tr>
                    <th>类型</th>
                    <th>分类</th>
                    <th>项目</th>
                    <th class="text-end">预算金额</th>
                    <th class="text-end">已用金额</th>
                    <th class="text-end">剩余金额</th>
                    <th class="text-end">去年同月预算</th>
                    <th class="text-end">去年同月已用</th>
                    <th class="text-center">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($budgets)): ?>
                    <tr><td colspan="9" class="text-center text-muted small">当前月份暂无预算配置</td></tr>
                <?php else: ?>
                    <?php foreach ($budgets as $b): ?>
                        <tr>
                            <td>
                                <?php if ($b['type'] === 'expense'): ?>
                                    <span class="badge bg-danger-subtle text-danger">支出</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">收入</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($b['category_icon_type']) && !empty($b['category_icon_value'])): ?>
                                    <?php if ($b['category_icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($b['category_icon_value'], ENT_QUOTES) ?>" alt="分类图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                    <?php elseif ($b['category_icon_type'] === 'svg'): ?>
                                        <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                            <?= $b['category_icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($b['category_name'] ?? '全部') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($b['item_icon_type']) && !empty($b['item_icon_value'])): ?>
                                    <?php if ($b['item_icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($b['item_icon_value'], ENT_QUOTES) ?>" alt="项目图标" class="me-1 rounded" style="width:18px;height:18px;object-fit:cover;vertical-align:middle;">
                                    <?php elseif ($b['item_icon_type'] === 'svg'): ?>
                                        <span class="tx-icon me-1 d-inline-block" style="width:18px;height:18px;overflow:hidden;vertical-align:middle;">
                                            <?= $b['item_icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($b['item_name'] ?? '全部') ?></span>
                            </td>
                            <td class="text-end">¥ <?= number_format($b['amount'], 2) ?></td>
                            <td class="text-end text-danger">¥ <?= number_format($b['used_amount'], 2) ?></td>
                            <td class="text-end text-success">¥ <?= number_format($b['remain_amount'], 2) ?></td>
                            <td class="text-end text-muted">¥ <?= number_format($b['prev_budget_amount'] ?? 0, 2) ?></td>
                            <td class="text-end text-muted">¥ <?= number_format($b['prev_used_amount'] ?? 0, 2) ?></td>
                            <td class="text-center">
                                <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary me-1 btn-budget-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalBudgetEdit"
                                        data-id="<?= (int)$b['id'] ?>"
                                        data-type="<?= htmlspecialchars($b['type'], ENT_QUOTES) ?>"
                                        data-category-id="<?= $b['category_id'] !== null ? (int)$b['category_id'] : '' ?>"
                                        data-item-id="<?= $b['item_id'] !== null ? (int)$b['item_id'] : '' ?>"
                                        data-type-label="<?= $b['type'] === 'expense' ? '支出' : '收入' ?>"
                                        data-category-name="<?= htmlspecialchars($b['category_name'] ?? '全部', ENT_QUOTES) ?>"
                                        data-item-name="<?= htmlspecialchars($b['item_name'] ?? '全部', ENT_QUOTES) ?>"
                                        data-amount="<?= htmlspecialchars(number_format($b['amount'], 2, '.', ''), ENT_QUOTES) ?>"
                                >编辑</button>
                                <?php if (($b['prev_budget_amount'] ?? 0) > 0): ?>
                                    <form method="post" class="d-inline me-1" onsubmit="return confirm('将本行预算金额更新为去年同月的预算吗？');">
                                        <input type="hidden" name="copy_prev_single" value="1">
                                        <input type="hidden" name="type" value="<?= htmlspecialchars($b['type'], ENT_QUOTES) ?>">
                                        <input type="hidden" name="category_id" value="<?= $b['category_id'] !== null ? (int)$b['category_id'] : '' ?>">
                                        <input type="hidden" name="item_id" value="<?= $b['item_id'] !== null ? (int)$b['item_id'] : '' ?>">
                                        <input type="hidden" name="amount" value="<?= htmlspecialchars(number_format($b['prev_budget_amount'], 2, '.', ''), ENT_QUOTES) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">复制去年</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm('确定要删除该预算配置吗？');" class="d-inline">
                                    <input type="hidden" name="delete_id" value="<?= (int)$b['id'] ?>">
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
