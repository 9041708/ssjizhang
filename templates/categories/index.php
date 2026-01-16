<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-1">分类管理</h2>
        <div class="small text-muted">用于管理支出 / 收入的分类，有记账数据后将无法删除。</div>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoryCreate">新增分类</button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- 新增分类弹窗 -->
<div class="modal fade" id="modalCategoryCreate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">新增分类</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="col-12 col-md-4">
                        <label class="form-label small">类型</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="expense">支出</option>
                            <option value="income">收入</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label small">名称</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">分类图标</label>
                        <div class="form-text small mb-1">可选择上传小图标，或从图标库中复用已有图标（可选）。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="createIconNone" value="none" checked>
                            <label class="btn btn-outline-secondary" for="createIconNone">不使用图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="createIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="createIconFile">上传图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="createIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="createIconLib">从图标库选择</label>
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
                        <label class="form-label small">排序</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="0">
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
        <h3 class="h6 mb-3">分类列表</h3>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 table-accounts">
                <thead class="table-light">
                <tr>
                    <th style="width:120px;">类型</th>
                    <th style="width:80px;">图标</th>
                    <th>名称</th>
                    <th style="width:90px;">排序</th>
                    <th class="text-center" style="width:160px;">操作</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4" class="text-center text-muted small">暂无分类</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>
                                <?php if ($c['type'] === 'expense'): ?>
                                    <span class="badge bg-danger-subtle text-danger">支出</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success">收入</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['icon_type']) && !empty($c['icon_value'])): ?>
                                    <?php if ($c['icon_type'] === 'file'): ?>
                                        <img src="/uploads/<?= htmlspecialchars($c['icon_value']) ?>" alt="图标" class="rounded" style="width:24px;height:24px;object-fit:cover;">
                                    <?php elseif ($c['icon_type'] === 'svg'): ?>
                                        <span class="category-icon d-inline-block" style="width:24px;height:24px;overflow:hidden;">
                                            <?= $c['icon_value'] ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= (int)$c['sort_order'] ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalCategoryEdit"
                                            data-id="<?= (int)$c['id'] ?>"
                                            data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                                            data-sort="<?= (int)$c['sort_order'] ?>"
                                            data-icon-type="<?= htmlspecialchars($c['icon_type'] ?? '', ENT_QUOTES) ?>"
                                            data-icon-value="<?= htmlspecialchars($c['icon_value'] ?? '', ENT_QUOTES) ?>">
                                        编辑
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('确定删除该分类吗？已有记账数据的分类无法删除。');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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

<!-- 编辑分类弹窗 -->
<div class="modal fade" id="modalCategoryEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">编辑分类</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="row g-2 align-items-end" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editCategoryId">
                    <div class="col-12">
                        <label class="form-label small">名称</label>
                        <input type="text" name="name" id="editCategoryName" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small">排序</label>
                        <input type="number" name="sort_order" id="editCategorySort" class="form-control form-control-sm" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label small d-block">分类图标</label>
                        <div class="form-text small mb-1">可保留原图标，或重新上传 / 从图标库选择 / 清除图标。</div>
                        <div class="btn-group btn-group-sm mb-2" role="group">
                            <input type="radio" class="btn-check" name="icon_mode" id="editIconKeep" value="none" checked>
                            <label class="btn btn-outline-secondary" for="editIconKeep">保持不变</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editIconFile" value="file">
                            <label class="btn btn-outline-secondary" for="editIconFile">上传新图标</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editIconLib" value="library">
                            <label class="btn btn-outline-secondary" for="editIconLib">从图标库选择</label>
                            <input type="radio" class="btn-check" name="icon_mode" id="editIconClear" value="clear">
                            <label class="btn btn-outline-danger" for="editIconClear">清除图标</label>
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
                        <div class="mt-2 small" id="editIconCurrentPreview"></div>
                    </div>
                    <div class="col-12 col-md-6 d-flex justify-content-end align-items-end gap-2 mt-2 mt-md-0">
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
    var modalEl = document.getElementById('modalCategoryEdit');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        var id = button.getAttribute('data-id');
        var name = button.getAttribute('data-name') || '';
        var sort = button.getAttribute('data-sort') || '0';
        var iconType = button.getAttribute('data-icon-type') || '';
        var iconValue = button.getAttribute('data-icon-value') || '';

        var idInput = document.getElementById('editCategoryId');
        var nameInput = document.getElementById('editCategoryName');
        var sortInput = document.getElementById('editCategorySort');
        var iconPreview = document.getElementById('editIconCurrentPreview');

        if (idInput) idInput.value = id;
        if (nameInput) nameInput.value = name;
        if (sortInput) sortInput.value = sort;

        if (iconPreview) {
            if (iconType === 'file' && iconValue) {
                iconPreview.innerHTML = '<span class="me-2">当前图标：</span><img src="/uploads/' + iconValue + '" alt="图标" style="width:24px;height:24px;object-fit:cover;" class="rounded">';
            } else if (iconType === 'svg' && iconValue) {
                iconPreview.innerHTML = '<span class="me-2">当前图标：</span><span class="category-icon" style="width:24px;height:24px;display:inline-block;overflow:hidden;vertical-align:middle;">' + iconValue + '</span>';
            } else {
                iconPreview.innerHTML = '<span class="text-muted">当前无图标</span>';
            }
        }

    });
});

document.addEventListener('DOMContentLoaded', function () {
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

    bindIconMode(document.querySelector('#modalCategoryCreate .modal-body'));
    bindIconMode(document.querySelector('#modalCategoryEdit .modal-body'));

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

    bindIconLibrary(document.querySelector('#modalCategoryCreate .modal-body'));
    bindIconLibrary(document.querySelector('#modalCategoryEdit .modal-body'));
});
</script>
