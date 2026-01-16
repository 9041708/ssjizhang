<div class="accounts-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-1">账户管理</h2>
            <div class="small text-muted">账户用于记录资金流向。“应付账款”类账户的余额建议填写为负数，以表示欠款。</div>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAccountCreate">新增账户</button>
    </div>

    <!-- 新增账户弹窗 -->
    <div class="modal fade" id="modalAccountCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">新增账户</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" class="row g-2 align-items-end" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <div class="col-12">
                            <label class="form-label small">账户大类</label>
                            <select name="group_id" class="form-select form-select-sm" required>
                                <option value="">请选择</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">选择“应付账款”类账户时，初始余额建议填入负数。</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">账户名称</label>
                            <input type="text" name="name" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">账号 / 卡号（可选）</label>
                            <input type="text" name="account_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small d-block">账户图标</label>
                            <div class="form-text small mb-1">可选择上传小图标，或从图标库中复用已有图标（可选）。</div>
                            <div class="btn-group btn-group-sm mb-2" role="group">
                                <input type="radio" class="btn-check" name="icon_mode" id="createAccountIconNone" value="none" checked>
                                <label class="btn btn-outline-secondary" for="createAccountIconNone">不使用图标</label>
                                <input type="radio" class="btn-check" name="icon_mode" id="createAccountIconFile" value="file">
                                <label class="btn btn-outline-secondary" for="createAccountIconFile">上传图标</label>
                                <input type="radio" class="btn-check" name="icon_mode" id="createAccountIconLib" value="library">
                                <label class="btn btn-outline-secondary" for="createAccountIconLib">从图标库选择</label>
                            </div>
                            <div class="icon-input-file d-none mb-2">
                                <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                                <div class="form-text small">建议使用正方形 PNG，小于 512KB。</div>
                            </div>
                            <div class="icon-input-library d-none mb-2">
                                <?php if (!empty($iconLibrary)): ?>
                                    <select name="icon_library_id" class="form-select form-select-sm icon-library-select">
                                        <option value="">请选择图标（图标库）</option>
                                        <?php foreach ($iconLibrary as $lib): ?>
                                            <option value="<?= (int)$lib['id'] ?>" data-file-path="<?= htmlspecialchars($lib['file_path'] ?? '', ENT_QUOTES) ?>">
                                                <?= htmlspecialchars($lib['name'] ?? ('图标 #' . (int)$lib['id'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="mt-2 small icon-library-preview text-muted"></div>
                                <?php else: ?>
                                    <div class="form-text small text-muted">当前图标库中暂无图标，可在图标库页面添加常用图标。</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">初始余额</label>
                            <input type="number" step="0.01" name="initial_balance" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-6 d-grid">
                            <button type="submit" class="btn btn-sm btn-primary">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="h6 mb-3">账户列表</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 table-accounts">
                <thead class="table-light">
                <tr>
                    <th>账户大类</th>
                    <th style="width:80px;">图标</th>
                    <th>账户名称</th>
                    <th>账号 / 卡号</th>
                    <th class="text-end">当前余额</th>
                    <th class="text-center" style="width:200px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($accounts)): ?>
                    <tr><td colspan="5" class="text-center text-muted small">暂无账户</td></tr>
                <?php else: ?>
                    <?php foreach ($accounts as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a['group_name'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($a['icon_type']) && !empty($a['icon_value'])): ?>
                                    <?php if ($a['icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($a['icon_value']) ?>" alt="图标" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                                    <?php elseif ($a['icon_type'] === 'svg'): ?>
                                        <span class="d-inline-block" style="width:24px;height:24px;overflow:hidden;">
                                            <?= $a['icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($a['name']) ?></td>
                            <td><?= htmlspecialchars($a['account_no'] ?? '') ?></td>
                            <td class="text-end">
                                <span class="<?= $a['current_balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                    ¥ <?= number_format($a['current_balance'], 2) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1 flex-wrap">
                                    <a href="/public/index.php?route=transactions&amp;account_id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-secondary">明细</a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalAccountEdit"
                                            data-id="<?= (int)$a['id'] ?>"
                                            data-group-id="<?= (int)$a['group_id'] ?>"
                                            data-name="<?= htmlspecialchars($a['name'], ENT_QUOTES) ?>"
                                            data-account-no="<?= htmlspecialchars($a['account_no'] ?? '', ENT_QUOTES) ?>"
                                            data-icon-type="<?= htmlspecialchars($a['icon_type'] ?? '', ENT_QUOTES) ?>"
                                            data-icon-value="<?= htmlspecialchars($a['icon_value'] ?? '', ENT_QUOTES) ?>">
                                        编辑
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('确定删除该账户吗？已有记账数据的账户无法删除。');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 编辑账户弹窗 -->
<div class="modal fade" id="modalAccountEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑账户</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editAccountId">
                    <div class="col-12">
                        <label class="form-label small">账户大类</label>
                        <select name="group_id" id="editGroupId" class="form-select form-select-sm" required>
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">账户名称</label>
                        <input type="text" name="name" id="editName" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">账号 / 卡号（可选）</label>
                        <input type="text" name="account_no" id="editAccountNo" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">账户图标</label>
                        <div class="form-text small mb-1">可保留原图标，或重新上传 / 从图标库选择 / 清除图标。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="editAccountIconKeep" value="none" checked>
                            <label class="btn btn-outline-secondary" for="editAccountIconKeep">保持不变</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editAccountIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="editAccountIconFile">上传新图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editAccountIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="editAccountIconLib">从图标库选择</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editAccountIconClear" value="clear">
                            <label class="btn btn-outline-danger" for="editAccountIconClear">清除图标</label>
                        </div>
                        <div class="icon-input-file d-none mb-2">
                            <input type="file" name="icon_file" accept="image/*" class="form-control form-control-sm">
                        </div>
                        <div class="icon-input-library d-none mb-2">
                            <?php if (!empty($iconLibrary)): ?>
                                <select name="icon_library_id" class="form-select form-select-sm icon-library-select">
                                    <option value="">请选择图标（图标库）</option>
                                    <?php foreach ($iconLibrary as $lib): ?>
                                        <option value="<?= (int)$lib['id'] ?>" data-file-path="<?= htmlspecialchars($lib['file_path'] ?? '', ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($lib['name'] ?? ('图标 #' . (int)$lib['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2 small icon-library-preview text-muted"></div>
                            <?php else: ?>
                                <div class="form-text small text-muted">当前图标库中暂无图标，可在图标库页面添加常用图标。</div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 small" id="editAccountIconCurrentPreview"></div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-sm btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('modalAccountEdit');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        var id = button.getAttribute('data-id');
        var groupId = button.getAttribute('data-group-id');
        var name = button.getAttribute('data-name') || '';
        var accountNo = button.getAttribute('data-account-no') || '';
        var iconType = button.getAttribute('data-icon-type') || '';
        var iconValue = button.getAttribute('data-icon-value') || '';

        var idInput = document.getElementById('editAccountId');
        var groupSelect = document.getElementById('editGroupId');
        var nameInput = document.getElementById('editName');
        var accountNoInput = document.getElementById('editAccountNo');
        var iconPreview = document.getElementById('editAccountIconCurrentPreview');

        if (idInput) idInput.value = id;
        if (nameInput) nameInput.value = name;
        if (accountNoInput) accountNoInput.value = accountNo;
        if (groupSelect && groupId) {
            groupSelect.value = groupId;
        }

        if (iconPreview) {
            if (iconType === 'file' && iconValue) {
                iconPreview.innerHTML = '<span class="me-2">当前图标：</span><img src="/uploads/' + iconValue + '" alt="账户图标" style="width:24px;height:24px;object-fit:cover;" class="rounded">';
            } else if (iconType === 'svg' && iconValue) {
                iconPreview.innerHTML = '<span class="me-2">当前图标：</span><span class="account-icon" style="width:24px;height:24px;display:inline-block;overflow:hidden;vertical-align:middle;">' + iconValue + '</span>';
            } else {
                iconPreview.innerHTML = '<span class="text-muted">当前无图标</span>';
            }
        }

    });
    function bindIconMode(container) {
        if (!container) return;
        var fileBlock = container.querySelector('.icon-input-file');
        var libBlock = container.querySelector('.icon-input-library');
        var radios = container.querySelectorAll('input[name="icon_mode"]');
        function update(mode) {
            if (fileBlock) fileBlock.classList.toggle('d-none', mode !== 'file');
            if (libBlock) libBlock.classList.toggle('d-none', mode !== 'library');
        }
        radios.forEach(function (r) {
            r.addEventListener('change', function () {
                update(this.value);
            });
            if (r.checked) {
                update(r.value);
            }
        });
    }

    bindIconMode(document.querySelector('#modalAccountCreate .modal-body'));
    bindIconMode(document.querySelector('#modalAccountEdit .modal-body'));

    function bindIconLibrary(container) {
        if (!container) return;
        var select = container.querySelector('.icon-library-select');
        var preview = container.querySelector('.icon-library-preview');
        if (!select || !preview) return;

        function updatePreview() {
            var option = select.options[select.selectedIndex];
            if (!option || !option.value) {
                preview.innerHTML = '<span class="text-muted">未选择图标</span>';
                return;
            }
            var path = option.getAttribute('data-file-path') || '';
            if (path) {
                preview.innerHTML = '<span class="me-2">预览：</span><img src="/uploads/' + path + '" alt="图标预览" style="width:24px;height:24px;object-fit:cover;" class="rounded">';
            } else {
                preview.innerHTML = '<span class="text-muted">未选择图标</span>';
            }
        }

        select.addEventListener('change', updatePreview);
        updatePreview();
    }

    bindIconLibrary(document.querySelector('#modalAccountCreate .modal-body'));
    bindIconLibrary(document.querySelector('#modalAccountEdit .modal-body'));
});
</script>
</div>
